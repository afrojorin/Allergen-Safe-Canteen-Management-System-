<?php
// api/cart.php - Synchronized Shared Cart API
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$pdo = get_db_connection();

// 1. Get Cart
if ($action === 'get') {
    $cartData = get_active_cart();
    echo json_encode([
        'success' => true,
        'cart'    => $cartData,
    ]);
    exit;
}

// 2. Add to Cart
if ($action === 'add') {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    $foodId          = (int)($jsonData['food_id'] ?? $_POST['food_id'] ?? 0);
    $quantity        = (int)($jsonData['quantity'] ?? $_POST['quantity'] ?? 1);
    $allergenWarning = !empty($jsonData['allergen_warning']) || !empty($_POST['allergen_warning']) ? 1 : 0;
    $allergenDetails = $jsonData['allergen_details'] ?? $_POST['allergen_details'] ?? null;

    if ($foodId <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid food ID or quantity.']);
        exit;
    }

    // Verify food exists in DB
    $stmtFood = $pdo->prepare("SELECT id, name, price, is_available FROM food_items WHERE id = ?");
    $stmtFood->execute([$foodId]);
    $food = $stmtFood->fetch();

    if (!$food) {
        echo json_encode(['success' => false, 'message' => 'Food item not found.']);
        exit;
    }

    if (!$food['is_available']) {
        echo json_encode(['success' => false, 'message' => 'Sorry, this food item is currently out of stock.']);
        exit;
    }

    if (is_string($allergenDetails)) {
        $allergenDetailsArr = json_decode($allergenDetails, true) ?: [$allergenDetails];
    } else {
        $allergenDetailsArr = is_array($allergenDetails) ? $allergenDetails : [];
    }

    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $cartId = get_or_create_user_cart_id($userId);

        // Check if item already exists in user's cart
        $stmtCheck = $pdo->prepare("SELECT id, quantity, allergen_warning, allergen_details FROM cart_items WHERE cart_id = ? AND food_id = ?");
        $stmtCheck->execute([$cartId, $foodId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            $newWarn = ($existing['allergen_warning'] || $allergenWarning) ? 1 : 0;
            
            // Merge details
            $existingDetails = $existing['allergen_details'] ? json_decode($existing['allergen_details'], true) : [];
            $mergedDetails = array_unique(array_merge($existingDetails, $allergenDetailsArr));

            $stmtUp = $pdo->prepare("
                UPDATE cart_items 
                SET quantity = ?, allergen_warning = ?, allergen_details = ? 
                WHERE id = ?
            ");
            $stmtUp->execute([$newQty, $newWarn, json_encode($mergedDetails), $existing['id']]);
        } else {
            $stmtAdd = $pdo->prepare("
                INSERT INTO cart_items (cart_id, food_id, quantity, allergen_warning, allergen_details)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtAdd->execute([
                $cartId,
                $foodId,
                $quantity,
                $allergenWarning,
                !empty($allergenDetailsArr) ? json_encode($allergenDetailsArr) : null
            ]);
        }
    } else {
        // Session-based guest cart
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        if (isset($_SESSION['guest_cart'][$foodId])) {
            $_SESSION['guest_cart'][$foodId]['quantity'] += $quantity;
            if ($allergenWarning) {
                $_SESSION['guest_cart'][$foodId]['allergen_warning'] = 1;
                $_SESSION['guest_cart'][$foodId]['allergen_details'] = array_unique(array_merge(
                    $_SESSION['guest_cart'][$foodId]['allergen_details'] ?? [],
                    $allergenDetailsArr
                ));
            }
        } else {
            $_SESSION['guest_cart'][$foodId] = [
                'food_id'          => $foodId,
                'quantity'         => $quantity,
                'allergen_warning' => $allergenWarning,
                'allergen_details' => $allergenDetailsArr,
                'modifications'    => [],
            ];
        }
    }

    $cartData = get_active_cart();
    echo json_encode([
        'success'     => true,
        'message'     => $food['name'] . ' added to your order!',
        'total_units' => $cartData['total_units'],
        'cart'        => $cartData,
    ]);
    exit;
}

// 3. Update Item Quantity
if ($action === 'update') {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    $cartItemId = $jsonData['cart_item_id'] ?? $_POST['cart_item_id'] ?? null;
    $foodId     = (int)($jsonData['food_id'] ?? $_POST['food_id'] ?? 0);
    $quantity   = (int)($jsonData['quantity'] ?? $_POST['quantity'] ?? 0);

    if ($quantity <= 0) {
        // Remove item if quantity is zero or negative
        if (is_logged_in()) {
            $userId = $_SESSION['user_id'];
            $cartId = get_or_create_user_cart_id($userId);
            if ($cartItemId) {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
                $stmt->execute([(int)$cartItemId, $cartId]);
            } elseif ($foodId > 0) {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE food_id = ? AND cart_id = ?");
                $stmt->execute([$foodId, $cartId]);
            }
        } else {
            if ($foodId > 0 && isset($_SESSION['guest_cart'][$foodId])) {
                unset($_SESSION['guest_cart'][$foodId]);
            }
        }
    } else {
        // Update to new positive quantity
        if (is_logged_in()) {
            $userId = $_SESSION['user_id'];
            $cartId = get_or_create_user_cart_id($userId);
            if ($cartItemId) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?");
                $stmt->execute([$quantity, (int)$cartItemId, $cartId]);
            } elseif ($foodId > 0) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE food_id = ? AND cart_id = ?");
                $stmt->execute([$quantity, $foodId, $cartId]);
            }
        } else {
            if ($foodId > 0 && isset($_SESSION['guest_cart'][$foodId])) {
                $_SESSION['guest_cart'][$foodId]['quantity'] = $quantity;
            }
        }
    }

    $cartData = get_active_cart();
    echo json_encode([
        'success'     => true,
        'total_units' => $cartData['total_units'],
        'cart'        => $cartData,
    ]);
    exit;
}

// 4. Remove Item
if ($action === 'remove') {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    $cartItemId = $jsonData['cart_item_id'] ?? $_POST['cart_item_id'] ?? null;
    $foodId     = (int)($jsonData['food_id'] ?? $_POST['food_id'] ?? 0);

    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $cartId = get_or_create_user_cart_id($userId);
        if ($cartItemId) {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
            $stmt->execute([(int)$cartItemId, $cartId]);
        } elseif ($foodId > 0) {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE food_id = ? AND cart_id = ?");
            $stmt->execute([$foodId, $cartId]);
        }
    } else {
        if ($foodId > 0 && isset($_SESSION['guest_cart'][$foodId])) {
            unset($_SESSION['guest_cart'][$foodId]);
        }
    }

    $cartData = get_active_cart();
    echo json_encode([
        'success'     => true,
        'message'     => 'Item removed from cart.',
        'total_units' => $cartData['total_units'],
        'cart'        => $cartData,
    ]);
    exit;
}

// 5. Clear Cart
if ($action === 'clear') {
    clear_active_cart();
    $cartData = get_active_cart();
    echo json_encode([
        'success'     => true,
        'message'     => 'Cart cleared.',
        'total_units' => 0,
        'cart'        => $cartData,
    ]);
    exit;
}

// 6. Save Item Modification (Ingredient Removals & Notes)
if ($action === 'save_modification') {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    $cartItemId   = $jsonData['cart_item_id'] ?? $_POST['cart_item_id'] ?? null;
    $foodId       = (int)($jsonData['food_id'] ?? $_POST['food_id'] ?? 0);
    $removals     = $jsonData['removals'] ?? $_POST['removals'] ?? []; // Array of ingredient names
    $instructions = trim($jsonData['instructions'] ?? $_POST['instructions'] ?? '');

    if (is_logged_in() && $cartItemId) {
        $userId = $_SESSION['user_id'];
        $cartId = get_or_create_user_cart_id($userId);

        // Verify cart_item belongs to current user
        $stmtCheck = $pdo->prepare("SELECT id FROM cart_items WHERE id = ? AND cart_id = ?");
        $stmtCheck->execute([(int)$cartItemId, $cartId]);
        if ($stmtCheck->fetch()) {
            // Delete old modifications for this cart item
            $stmtDel = $pdo->prepare("DELETE FROM cart_item_modifications WHERE cart_item_id = ?");
            $stmtDel->execute([(int)$cartItemId]);

            // Insert removal requests
            if (is_array($removals)) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO cart_item_modifications (cart_item_id, ingredient_name, modification_type, instructions)
                    VALUES (?, ?, 'removal', ?)
                ");
                foreach ($removals as $ingName) {
                    $ingName = trim($ingName);
                    if (!empty($ingName)) {
                        $stmtIns->execute([(int)$cartItemId, $ingName, $instructions]);
                    }
                }
            }

            // If special instruction exists without specific ingredient
            if (!empty($instructions) && empty($removals)) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO cart_item_modifications (cart_item_id, ingredient_name, modification_type, instructions)
                    VALUES (?, 'General Note', 'special_instruction', ?)
                ");
                $stmtIns->execute([(int)$cartItemId, $instructions]);
            }
        }
    } else {
        // Guest cart in session
        if ($foodId > 0 && isset($_SESSION['guest_cart'][$foodId])) {
            $_SESSION['guest_cart'][$foodId]['modifications'] = [
                'removals'     => $removals,
                'instructions' => $instructions,
            ];
        }
    }

    echo json_encode(['success' => true, 'message' => 'Modifications updated.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid cart action.']);
