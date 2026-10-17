<?php
// api/allergens.php - Allergen Management API
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_all';
$pdo = get_db_connection();

// 1. Get all available allergens
if ($action === 'get_all') {
    $stmt = $pdo->query("SELECT id, name, slug, description, icon FROM allergens ORDER BY id ASC");
    $allergens = $stmt->fetchAll();
    echo json_encode([
        'success'   => true,
        'allergens' => $allergens,
    ]);
    exit;
}

// 2. Get user's allergen profile
if ($action === 'get_user') {
    if (!is_logged_in()) {
        echo json_encode([
            'success'   => false,
            'message'   => 'User is not logged in.',
            'logged_in' => false,
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT a.id, a.name, a.slug, a.description, a.icon
        FROM user_allergens ua
        JOIN allergens a ON ua.allergen_id = a.id
        WHERE ua.user_id = ?
        ORDER BY a.name ASC
    ");
    $stmt->execute([$userId]);
    $userAllergens = $stmt->fetchAll();

    $userProfile = get_current_user_profile();

    echo json_encode([
        'success'                    => true,
        'logged_in'                  => true,
        'allergen_profile_completed' => (bool)$userProfile['allergen_profile_completed'],
        'allergens'                  => $userAllergens,
        'allergen_ids'               => array_map(function($a) { return (int)$a['id']; }, $userAllergens),
    ]);
    exit;
}

// 3. Save / Update User's Allergen Profile
if ($action === 'save') {
    if (!is_logged_in()) {
        echo json_encode([
            'success' => false,
            'message' => 'Please log in to save your allergen profile.',
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    // Accepts JSON payload or POST array
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    $allergenIds = [];
    if (isset($jsonData['allergens']) && is_array($jsonData['allergens'])) {
        $allergenIds = $jsonData['allergens'];
    } elseif (isset($_POST['allergens']) && is_array($_POST['allergens'])) {
        $allergenIds = $_POST['allergens'];
    }

    // Begin Transaction
    $pdo->beginTransaction();
    try {
        // Clear previous selections for this user
        $stmtDel = $pdo->prepare("DELETE FROM user_allergens WHERE user_id = ?");
        $stmtDel->execute([$userId]);

        // Insert new selections
        if (!empty($allergenIds)) {
            $stmtInsert = $pdo->prepare("INSERT INTO user_allergens (user_id, allergen_id) VALUES (?, ?)");
            foreach ($allergenIds as $aid) {
                $aid = (int)$aid;
                if ($aid > 0) {
                    $stmtInsert->execute([$userId, $aid]);
                }
            }
        }

        // Mark profile completed in users table
        $stmtUser = $pdo->prepare("UPDATE users SET allergen_profile_completed = 1 WHERE id = ?");
        $stmtUser->execute([$userId]);

        $pdo->commit();

        // Fetch refreshed list
        $stmtList = $pdo->prepare("
            SELECT a.id, a.name, a.slug, a.icon
            FROM user_allergens ua
            JOIN allergens a ON ua.allergen_id = a.id
            WHERE ua.user_id = ?
            ORDER BY a.name ASC
        ");
        $stmtList->execute([$userId]);
        $updatedAllergens = $stmtList->fetchAll();

        echo json_encode([
            'success'      => true,
            'message'      => 'Allergen profile saved successfully!',
            'allergens'    => $updatedAllergens,
            'allergen_ids' => array_map(function($a) { return (int)$a['id']; }, $updatedAllergens),
        ]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save allergen profile: ' . $e->getMessage(),
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
