<?php
// api/auth.php - Authentication API (Login, Register, Logout, Status)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
          || (isset($_GET['format']) && $_GET['format'] === 'json');

// Helper to send response or redirect
function send_auth_response($success, $message, $data = [], $redirectUrl = '') {
    global $isAjax;
    if ($isAjax || isset($_POST['ajax'])) {
        echo json_encode([
            'success'  => $success,
            'message'  => $message,
            'data'     => $data,
            'redirect' => $redirectUrl,
        ]);
        exit;
    } else {
        if (!$success) {
            $_SESSION['auth_error'] = $message;
            $referer = $_SERVER['HTTP_REFERER'] ?? '../login.php';
            header("Location: " . $referer);
            exit;
        } else {
            $_SESSION['auth_success'] = $message;
            $target = $redirectUrl ?: '../index.php';
            header("Location: " . $target);
            exit;
        }
    }
}

// 1. Status Check
if ($action === 'status') {
    if (is_logged_in()) {
        $profile = get_current_user_profile();
        echo json_encode([
            'success'   => true,
            'logged_in' => true,
            'user'      => [
                'id'                         => $profile['id'],
                'name'                       => $profile['name'],
                'email'                      => $profile['email'],
                'phone'                      => $profile['phone'],
                'address'                    => $profile['address'],
                'allergen_profile_completed' => (bool)$profile['allergen_profile_completed'],
            ],
            'cart_units' => get_cart_total_units(),
        ]);
    } else {
        echo json_encode([
            'success'    => true,
            'logged_in'  => false,
            'cart_units' => get_cart_total_units(),
        ]);
    }
    exit;
}

// 2. Login
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $returnUrl = trim($_POST['return_url'] ?? $_GET['return'] ?? '');

    if (empty($email) || empty($password)) {
        send_auth_response(false, 'Please provide both email and password.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_auth_response(false, 'Please enter a valid email address.');
    }

    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, allergen_profile_completed FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        send_auth_response(false, 'Invalid email or password. Please try again.');
    }

    // Set Session securely
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    // Transfer guest cart to user cart in database if exists
    if (!empty($_SESSION['guest_cart'])) {
        $cartId = get_or_create_user_cart_id($user['id']);
        foreach ($_SESSION['guest_cart'] as $foodId => $gItem) {
            $stmtCheck = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND food_id = ?");
            $stmtCheck->execute([$cartId, $foodId]);
            $existing = $stmtCheck->fetch();
            if ($existing) {
                $newQty = $existing['quantity'] + $gItem['quantity'];
                $stmtUp = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                $stmtUp->execute([$newQty, $existing['id']]);
            } else {
                $stmtAdd = $pdo->prepare("
                    INSERT INTO cart_items (cart_id, food_id, quantity, allergen_warning, allergen_details)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtAdd->execute([
                    $cartId,
                    $foodId,
                    $gItem['quantity'],
                    !empty($gItem['allergen_warning']) ? 1 : 0,
                    !empty($gItem['allergen_details']) ? json_encode($gItem['allergen_details']) : null,
                ]);
            }
        }
        unset($_SESSION['guest_cart']);
    }

    // Check allergen profile status
    $needsAllergenSetup = (int)$user['allergen_profile_completed'] === 0;
    $destination = '../index.php';
    if ($needsAllergenSetup) {
        $destination = '../profile.php?setup=1' . ($returnUrl ? '&return=' . urlencode($returnUrl) : '');
    } elseif (!empty($returnUrl)) {
        $destination = $returnUrl;
    }

    send_auth_response(true, 'Welcome back, ' . htmlspecialchars($user['name']) . '!', [
        'user_id' => $user['id'],
        'name'    => $user['name'],
        'allergen_profile_completed' => (bool)$user['allergen_profile_completed'],
    ], $destination);
}

// 3. Register
if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $allergenIds = $_POST['allergens'] ?? [];
    $returnUrl = trim($_POST['return_url'] ?? $_GET['return'] ?? '');

    if (empty($name)) {
        send_auth_response(false, 'Please enter your full name.');
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_auth_response(false, 'Please provide a valid email address.');
    }
    if (strlen($password) < 6) {
        send_auth_response(false, 'Password must be at least 6 characters long.');
    }

    $pdo = get_db_connection();

    // Check if email already registered
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        send_auth_response(false, 'An account with this email address already exists. Please sign in instead.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    // If allergen IDs submitted right away, profile is completed; otherwise pending setup
    $hasAllergensSubmitted = !empty($_POST['allergen_setup_completed']) || !empty($allergenIds);
    $allergenProfileCompleted = $hasAllergensSubmitted ? 1 : 0;

    $stmtInsert = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, phone, address, allergen_profile_completed)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([$name, $email, $passwordHash, $phone, $address, $allergenProfileCompleted]);
    $newUserId = $pdo->lastInsertId();

    // Save allergens if supplied
    if (is_array($allergenIds) && count($allergenIds) > 0) {
        $stmtAllergen = $pdo->prepare("INSERT IGNORE INTO user_allergens (user_id, allergen_id) VALUES (?, ?)");
        foreach ($allergenIds as $aId) {
            $aId = (int)$aId;
            if ($aId > 0) {
                $stmtAllergen->execute([$newUserId, $aId]);
            }
        }
    }

    // Auto-login newly registered user
    session_regenerate_id(true);
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    // Migrate any guest cart
    if (!empty($_SESSION['guest_cart'])) {
        $cartId = get_or_create_user_cart_id($newUserId);
        foreach ($_SESSION['guest_cart'] as $foodId => $gItem) {
            $stmtAdd = $pdo->prepare("
                INSERT INTO cart_items (cart_id, food_id, quantity, allergen_warning, allergen_details)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtAdd->execute([
                $cartId,
                $foodId,
                $gItem['quantity'],
                !empty($gItem['allergen_warning']) ? 1 : 0,
                !empty($gItem['allergen_details']) ? json_encode($gItem['allergen_details']) : null,
            ]);
        }
        unset($_SESSION['guest_cart']);
    }

    // Destination
    $destination = '../index.php';
    if (!$allergenProfileCompleted) {
        $destination = '../profile.php?setup=1' . ($returnUrl ? '&return=' . urlencode($returnUrl) : '');
    } elseif (!empty($returnUrl)) {
        $destination = $returnUrl;
    }

    send_auth_response(true, 'Account created successfully!', [
        'user_id' => $newUserId,
        'name'    => $name,
        'allergen_profile_completed' => (bool)$allergenProfileCompleted,
    ], $destination);
}

// 4. Logout
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    session_start();
    $_SESSION['auth_success'] = 'You have been safely logged out.';
    send_auth_response(true, 'Logged out successfully', [], '../login.php');
}

send_auth_response(false, 'Invalid action specified.');
