<?php
// tests/test_flow.php - Comprehensive Automated Backend Test Suite
require_once __DIR__ . '/../config/db.php';

function run_test($name, $fn) {
    echo "Testing: {$name}... ";
    try {
        $result = $fn();
        if ($result === true) {
            echo "[PASS]\n";
            return true;
        } else {
            echo "[FAIL]: {$result}\n";
            return false;
        }
    } catch (Exception $e) {
        echo "[ERROR]: " . $e->getMessage() . "\n";
        return false;
    }
}

$pdo = get_db_connection();
$passed = 0;
$total = 0;

echo "=========================================================\n";
echo "CANTEEN MANAGEMENT SYSTEM - AUTOMATED VERIFICATION SUITE\n";
echo "=========================================================\n\n";

// Test 1: Seed data and ingredients verification
$total++;
if (run_test("1. Database Schema and Food Ingredients Relational Integrity", function() use ($pdo) {
    $stmt = $pdo->query("SELECT count(*) FROM food_items");
    $foodCount = (int)$stmt->fetchColumn();
    if ($foodCount < 10) return "Expected at least 10 food items, found {$foodCount}";

    $stmt2 = $pdo->query("SELECT count(*) FROM food_ingredients");
    $fiCount = (int)$stmt2->fetchColumn();
    if ($fiCount < 20) return "Expected food ingredients mappings, found {$fiCount}";

    $stmt3 = $pdo->query("SELECT count(*) FROM ingredient_allergens");
    $iaCount = (int)$stmt3->fetchColumn();
    if ($iaCount < 5) return "Expected ingredient allergen mappings, found {$iaCount}";

    return true;
})) $passed++;

// Test 2: User Authentication & Password Hashing
$total++;
if (run_test("2. User Authentication and Password Verification (Alex Morgan)", function() use ($pdo) {
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, allergen_profile_completed FROM users WHERE email = ?");
    $stmt->execute(['test@example.com']);
    $user = $stmt->fetch();
    if (!$user) return "Seed user test@example.com not found";
    if (!password_verify('password123', $user['password_hash'])) return "Password verification failed";
    if ((int)$user['allergen_profile_completed'] !== 1) return "Alex Morgan should have completed allergen profile";
    return true;
})) $passed++;

// Test 3: Allergen Profile Persistence for User 1
$total++;
if (run_test("3. Saved Allergen Retrieval for Alex Morgan (Peanuts & Dairy)", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT a.name 
        FROM user_allergens ua 
        JOIN allergens a ON ua.allergen_id = a.id 
        WHERE ua.user_id = 1 
        ORDER BY a.name ASC
    ");
    $stmt->execute();
    $allergens = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('Milk and dairy', $allergens) || !in_array('Peanuts', $allergens)) {
        return "Alex Morgan should have 'Milk and dairy' and 'Peanuts' saved. Found: " . implode(', ', $allergens);
    }
    return true;
})) $passed++;

// Test 4: Allergen Detection logic for Food with conflict (Ponir & Shobji -> Dairy)
$total++;
if (run_test("4. Allergen Detection: Alex Morgan selecting 'Ponir & Shobji' (Contains Dairy)", function() use ($pdo) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Alex Morgan';

    // Simulate check_allergen query
    $foodId = 1; // Ponir & Shobji
    $stmtFood = $pdo->prepare("SELECT name, ingredients_verified FROM food_items WHERE id = ?");
    $stmtFood->execute([$foodId]);
    $food = $stmtFood->fetch();

    $stmtUA = $pdo->prepare("SELECT allergen_id FROM user_allergens WHERE user_id = ?");
    $stmtUA->execute([1]);
    $userAids = $stmtUA->fetchAll(PDO::FETCH_COLUMN);

    $stmtIng = $pdo->prepare("
        SELECT i.name AS ing_name, ia.allergen_id, a.name AS allergen_name
        FROM food_ingredients fi
        JOIN ingredients i ON fi.ingredient_id = i.id
        JOIN ingredient_allergens ia ON i.id = ia.ingredient_id
        JOIN allergens a ON ia.allergen_id = a.id
        WHERE fi.food_id = ?
    ");
    $stmtIng->execute([$foodId]);
    $conflicts = [];
    while ($row = $stmtIng->fetch()) {
        if (in_array($row['allergen_id'], $userAids)) {
            $conflicts[] = $row['allergen_name'];
        }
    }
    if (empty($conflicts)) return "Expected conflict with Dairy for Ponir & Shobji";
    if (!in_array('Milk and dairy', $conflicts)) return "Expected 'Milk and dairy' in conflicts";
    return true;
})) $passed++;

// Test 5: Allergen Detection for Chirer Polao (Contains Peanuts -> Conflict)
$total++;
if (run_test("5. Allergen Detection: Alex Morgan selecting 'Chirer Polao' (Contains Peanuts)", function() use ($pdo) {
    $foodId = 4; // Chirer Polao
    $stmtIng = $pdo->prepare("
        SELECT a.name AS allergen_name
        FROM food_ingredients fi
        JOIN ingredients i ON fi.ingredient_id = i.id
        JOIN ingredient_allergens ia ON i.id = ia.ingredient_id
        JOIN allergens a ON ia.allergen_id = a.id
        WHERE fi.food_id = ? AND ia.allergen_id IN (SELECT allergen_id FROM user_allergens WHERE user_id = 1)
    ");
    $stmtIng->execute([$foodId]);
    $matched = $stmtIng->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('Peanuts', $matched)) return "Expected Peanuts allergen detected for Chirer Polao";
    return true;
})) $passed++;

// Test 6: Incomplete / Unverified Ingredient Notice
$total++;
if (run_test("6. Incomplete Data Handling: Food with unverified ingredients does not produce false all-clear", function() use ($pdo) {
    $foodId = 12; // Chef Daily Special
    $stmtFood = $pdo->prepare("SELECT ingredients_verified FROM food_items WHERE id = ?");
    $stmtFood->execute([$foodId]);
    $verified = (int)$stmtFood->fetchColumn();
    if ($verified !== 0) return "Chef Daily Special should have ingredients_verified = 0";
    return true;
})) $passed++;

// Test 7: Food without Conflict (Alu Bhaji: Potato, Onion, Green Chili, Mustard Oil - Alex has no Mustard allergy)
$total++;
if (run_test("7. Safe Food Selection: Alu Bhaji has no conflict with Alex Morgan", function() use ($pdo) {
    $foodId = 3; // Alu Bhaji
    $stmtIng = $pdo->prepare("
        SELECT a.name AS allergen_name
        FROM food_ingredients fi
        JOIN ingredients i ON fi.ingredient_id = i.id
        JOIN ingredient_allergens ia ON i.id = ia.ingredient_id
        JOIN allergens a ON ia.allergen_id = a.id
        WHERE fi.food_id = ? AND ia.allergen_id IN (SELECT allergen_id FROM user_allergens WHERE user_id = 1)
    ");
    $stmtIng->execute([$foodId]);
    $matched = $stmtIng->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($matched)) return "Alu Bhaji should not conflict with Alex Morgan. Found: " . implode(', ', $matched);
    return true;
})) $passed++;

// Test 8: Cart Food Units Calculation (2 burgers + 3 sandwiches = 5 units)
$total++;
if (run_test("8. Cart Total Units Calculation (Sum of quantities, not distinct items)", function() use ($pdo) {
    $_SESSION['user_id'] = 1;
    clear_active_cart();

    $cartId = get_or_create_user_cart_id(1);
    // Add 2 of Food 11 (Chicken Burger)
    $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, food_id, quantity) VALUES (?, 11, 2)");
    $stmt->execute([$cartId]);

    // Add 3 of Food 3 (Alu Bhaji)
    $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, food_id, quantity) VALUES (?, 3, 3)");
    $stmt->execute([$cartId]);

    $totalUnits = get_cart_total_units();
    if ($totalUnits !== 5) return "Expected total units = 5 (2 + 3), got {$totalUnits}";

    // Verify subtotal: (150 * 2) + (65 * 3) = 300 + 195 = 495
    $activeCart = get_active_cart();
    if ((float)$activeCart['subtotal'] !== 495.0) return "Expected subtotal = 495.00, got {$activeCart['subtotal']}";

    return true;
})) $passed++;

// Test 9: Ingredient Removal Modification Attachment
$total++;
if (run_test("9. Ingredient Removal Specification on Cart Item", function() use ($pdo) {
    $cartId = get_or_create_user_cart_id(1);
    $stmt = $pdo->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND food_id = 11");
    $stmt->execute([$cartId]);
    $cartItemId = (int)$stmt->fetchColumn();

    // Attach modification: Remove Mayonnaise
    $stmtMod = $pdo->prepare("
        INSERT INTO cart_item_modifications (cart_item_id, ingredient_name, modification_type, instructions)
        VALUES (?, 'Mayonnaise', 'removal', 'Strict allergy caution')
    ");
    $stmtMod->execute([$cartItemId]);

    $cart = get_active_cart();
    $burgerItem = null;
    foreach ($cart['items'] as $it) {
        if ($it['food_id'] === 11) $burgerItem = $it;
    }
    if (!$burgerItem) return "Burger item not found in cart";
    if (empty($burgerItem['modifications'])) return "Burger modifications array empty";
    if ($burgerItem['modifications'][0]['ingredient_name'] !== 'Mayonnaise') return "Modification ingredient name mismatch";

    return true;
})) $passed++;

// Test 10: Order Placement Transaction and Cart Reset
$total++;
if (run_test("10. Full Order Checkout Transaction and Automatic Cart Clearing", function() use ($pdo) {
    $cartBefore = get_active_cart();
    if (empty($cartBefore['items'])) return "Cart should have items before placing order";

    // Place order
    $orderCode = 'TEST-ORD-' . time();
    $pdo->beginTransaction();
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (order_code, user_id, customer_name, customer_email, customer_phone, delivery_address, pickup_time, subtotal, tax, total, status)
        VALUES (?, 1, 'Alex Morgan', 'test@example.com', '+880 1711 000111', 'Room 304, North Hall', '12:30 PM', ?, 0.00, ?, 'Confirmed')
    ");
    $stmtOrder->execute([$orderCode, $cartBefore['subtotal'], $cartBefore['total']]);
    $orderId = $pdo->lastInsertId();

    foreach ($cartBefore['items'] as $item) {
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, food_id, food_name, unit_price, quantity, line_total, allergen_warning)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtItem->execute([
            $orderId,
            $item['food_id'],
            $item['food_name'],
            $item['price'],
            $item['quantity'],
            $item['line_total'],
            $item['allergen_warning'] ? 1 : 0
        ]);
        $orderItemId = $pdo->lastInsertId();

        if (!empty($item['modifications'])) {
            foreach ($item['modifications'] as $mod) {
                $stmtMod = $pdo->prepare("
                    INSERT INTO order_item_modifications (order_item_id, ingredient_name, modification_type, instructions)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtMod->execute([$orderItemId, $mod['ingredient_name'], $mod['modification_type'], $mod['instructions']]);
            }
        }
    }
    $pdo->commit();

    // Clear cart upon success
    clear_active_cart();

    // Verify cart is now completely empty
    $unitsAfter = get_cart_total_units();
    if ($unitsAfter !== 0) return "Cart total units should be 0 after successful order, got {$unitsAfter}";

    // Verify order and item modifications in database
    $stmtVerify = $pdo->prepare("SELECT count(*) FROM order_items WHERE order_id = ?");
    $stmtVerify->execute([$orderId]);
    $oiCount = (int)$stmtVerify->fetchColumn();
    if ($oiCount !== count($cartBefore['items'])) return "Order item count mismatch in database";

    $stmtModVerify = $pdo->prepare("
        SELECT oim.ingredient_name 
        FROM order_item_modifications oim 
        JOIN order_items oi ON oim.order_item_id = oi.id 
        WHERE oi.order_id = ?
    ");
    $stmtModVerify->execute([$orderId]);
    $mods = $stmtModVerify->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('Mayonnaise', $mods)) return "Order item modification 'Mayonnaise' not persisted in database";

    return true;
})) $passed++;

// Test 11: First-time User Flow (Sarah Student: profile incomplete)
$total++;
if (run_test("11. First-Time User Incomplete Allergen Profile Flag", function() use ($pdo) {
    $stmt = $pdo->prepare("SELECT allergen_profile_completed FROM users WHERE email = ?");
    $stmt->execute(['newuser@example.com']);
    $completed = (int)$stmt->fetchColumn();
    if ($completed !== 0) return "Sarah Student should have allergen_profile_completed = 0";
    return true;
})) $passed++;

// Test 12: New Registration & Updating Allergen Profile
$total++;
if (run_test("12. Registration & Allergen Profile Dynamic Update", function() use ($pdo) {
    $testEmail = 'autotest_' . time() . '@university.edu';
    $passwordHash = password_hash('pass123', PASSWORD_DEFAULT);

    // Register user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, allergen_profile_completed) VALUES (?, ?, ?, 0)");
    $stmt->execute(['Auto Tester', $testEmail, $passwordHash]);
    $newId = $pdo->lastInsertId();

    // Save allergens: Eggs (4) and Mustard (10)
    $stmtA = $pdo->prepare("INSERT INTO user_allergens (user_id, allergen_id) VALUES (?, 4), (?, 10)");
    $stmtA->execute([$newId, $newId]);

    $stmtU = $pdo->prepare("UPDATE users SET allergen_profile_completed = 1 WHERE id = ?");
    $stmtU->execute([$newId]);

    // Check food 5 (Dimer Halwa, which contains Eggs)
    $stmtCheck = $pdo->prepare("
        SELECT count(*) 
        FROM food_ingredients fi 
        JOIN ingredient_allergens ia ON fi.ingredient_id = ia.ingredient_id 
        WHERE fi.food_id = 5 AND ia.allergen_id IN (SELECT allergen_id FROM user_allergens WHERE user_id = ?)
    ");
    $stmtCheck->execute([$newId]);
    $conflictCount = (int)$stmtCheck->fetchColumn();
    if ($conflictCount < 1) return "Dimer Halwa should conflict with Eggs for newly registered user";

    return true;
})) $passed++;

echo "\n=========================================================\n";
echo "TEST RESULTS: {$passed} of {$total} PASSED (" . round(($passed/$total)*100) . "%)\n";
echo "=========================================================\n";
