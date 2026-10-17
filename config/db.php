<?php
// config/db.php - Database connection and application helper utilities

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'canteen_backend');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get or initialize PDO database connection
 *
 * @return PDO
 */
function get_db_connection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please ensure MySQL is running.'
            ]));
        }
    }
    return $pdo;
}

// CSRF Protection
/**
 * @return string
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * @param mixed $token
 * @return bool
 */
function verify_csrf_token($token): bool {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication Helpers
/**
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * @return array{id: int|string, name: string, email: string}|null
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}

/**
 * @return array|null
 */
function get_current_user_profile(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT id, name, email, phone, address, allergen_profile_completed FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    return $profile ?: null;
}

/**
 * @param string $return_url
 * @return void
 */
function require_login($return_url = ''): void {
    if (!is_logged_in()) {
        $target = 'login.php';
        if (!empty($return_url)) {
            $target .= '?return=' . urlencode($return_url);
        }
        header("Location: " . $target);
        exit;
    }
}

// Cart Synchronization Helpers
// A cart is stored in the database for logged in users, or in $_SESSION['cart'] for guests.
/**
 * @param int|string $userId
 * @return int
 */
function get_or_create_user_cart_id($userId): int {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cart = $stmt->fetch();
    if ($cart) {
        return (int)$cart['id'];
    }
    $stmt = $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    return (int)$pdo->lastInsertId();
}

/**
 * Total Food Units Count (e.g. 2 burgers + 3 sandwiches = 5)
 *
 * @return int
 */
function get_cart_total_units(): int {
    $pdo = get_db_connection();
    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0) AS total_units
            FROM carts c
            JOIN cart_items ci ON c.id = ci.cart_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return (int)($row['total_units'] ?? 0);
    } else {
        $total = 0;
        if (!empty($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as $item) {
                $total += (int)($item['quantity'] ?? 0);
            }
        }
        return $total;
    }
}

/**
 * Full active cart items with ingredients and allergen flags
 *
 * @return array{items: array, total_units: int, subtotal: float, total: float}
 */
function get_active_cart(): array {
    $pdo = get_db_connection();
    $items = [];
    $subtotal = 0;

    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $cartId = get_or_create_user_cart_id($userId);

        $stmt = $pdo->prepare("
            SELECT ci.id AS cart_item_id, ci.food_id, ci.quantity, ci.allergen_warning, ci.allergen_details,
                   f.name AS food_name, f.price, f.image, f.is_available, f.ingredients_verified
            FROM cart_items ci
            JOIN food_items f ON ci.food_id = f.id
            WHERE ci.cart_id = ?
            ORDER BY ci.id ASC
        ");
        $stmt->execute([$cartId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $line_total = $row['price'] * $row['quantity'];
            $subtotal += $line_total;

            // Fetch removable ingredients for this food item
            $stmtIng = $pdo->prepare("
                SELECT i.id, i.name, fi.is_optional_removable
                FROM food_ingredients fi
                JOIN ingredients i ON fi.ingredient_id = i.id
                WHERE fi.food_id = ?
                ORDER BY i.name ASC
            ");
            $stmtIng->execute([$row['food_id']]);
            $ingredients = $stmtIng->fetchAll();

            // Fetch saved modifications for this cart item
            $stmtMod = $pdo->prepare("
                SELECT id, ingredient_id, ingredient_name, modification_type, instructions
                FROM cart_item_modifications
                WHERE cart_item_id = ?
            ");
            $stmtMod->execute([$row['cart_item_id']]);
            $modifications = $stmtMod->fetchAll();

            $items[] = [
                'cart_item_id'         => $row['cart_item_id'],
                'food_id'              => $row['food_id'],
                'food_name'            => $row['food_name'],
                'price'                => (float)$row['price'],
                'quantity'             => (int)$row['quantity'],
                'line_total'           => (float)$line_total,
                'image'                => $row['image'],
                'is_available'         => (bool)$row['is_available'],
                'ingredients_verified' => (bool)$row['ingredients_verified'],
                'allergen_warning'     => (bool)$row['allergen_warning'],
                'allergen_details'     => $row['allergen_details'] ? json_decode($row['allergen_details'], true) : [],
                'ingredients'          => $ingredients,
                'modifications'        => $modifications,
            ];
        }
    } else {
        // Fallback for guest cart in session
        if (!empty($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as $foodId => $gItem) {
                $stmt = $pdo->prepare("SELECT id, name, price, image, is_available, ingredients_verified FROM food_items WHERE id = ?");
                $stmt->execute([$foodId]);
                $food = $stmt->fetch();
                if ($food) {
                    $qty = (int)$gItem['quantity'];
                    $line_total = $food['price'] * $qty;
                    $subtotal += $line_total;

                    // Ingredients
                    $stmtIng = $pdo->prepare("
                        SELECT i.id, i.name, fi.is_optional_removable
                        FROM food_ingredients fi
                        JOIN ingredients i ON fi.ingredient_id = i.id
                        WHERE fi.food_id = ?
                    ");
                    $stmtIng->execute([$foodId]);
                    $ingredients = $stmtIng->fetchAll();

                    $items[] = [
                        'cart_item_id'         => 'guest_' . $foodId,
                        'food_id'              => $food['id'],
                        'food_name'            => $food['name'],
                        'price'                => (float)$food['price'],
                        'quantity'             => $qty,
                        'line_total'           => (float)$line_total,
                        'image'                => $food['image'],
                        'is_available'         => (bool)$food['is_available'],
                        'ingredients_verified' => (bool)$food['ingredients_verified'],
                        'allergen_warning'     => !empty($gItem['allergen_warning']),
                        'allergen_details'     => $gItem['allergen_details'] ?? [],
                        'ingredients'          => $ingredients,
                        'modifications'        => $gItem['modifications'] ?? [],
                    ];
                }
            }
        }
    }

    return [
        'items'       => $items,
        'total_units' => get_cart_total_units(),
        'subtotal'    => (float)$subtotal,
        'total'       => (float)$subtotal,
    ];
}

/**
 * Clear cart on successful order
 *
 * @return void
 */
function clear_active_cart(): void {
    $pdo = get_db_connection();
    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        if ($cart) {
            $stmtDel = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmtDel->execute([$cart['id']]);
        }
    }
    unset($_SESSION['guest_cart']);
}

/**
 * Escape HTML utility
 *
 * @param mixed $str
 * @return string
 */
function e($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
