<?php
// api/order.php - Order Creation and Confirmation API
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// 1. Validate Authentication
if (!is_logged_in()) {
    echo json_encode([
        'success'   => false,
        'message'   => 'Please sign in or register before completing your order.',
        'logged_in' => false,
        'redirect'  => 'login.php?return=' . urlencode('checkout.php')
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$pdo = get_db_connection();

// 2. Fetch and Validate Active Cart
$cartData = get_active_cart();
if (empty($cartData['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Your cart is empty. Please add delicious meals before checking out.'
    ]);
    exit;
}

// 3. Process Customer Billing / Contact Details
$name        = trim($_POST['name'] ?? $_SESSION['user_name'] ?? '');
$email       = trim($_POST['email'] ?? $_SESSION['user_email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$address     = trim($_POST['address'] ?? '');
$pickupTime  = trim($_POST['pickup_time'] ?? '12:30 PM');
$notes       = trim($_POST['notes'] ?? $_POST['bill'] ?? '');
$idempotency = trim($_POST['idempotency_token'] ?? '');

if (empty($name) || empty($email) || empty($phone) || empty($address)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required billing information (Name, Email, Phone, and Delivery/Pickup Address).'
    ]);
    exit;
}

// Check idempotency / double submission prevention
if (!empty($idempotency)) {
    if (isset($_SESSION['last_order_token']) && $_SESSION['last_order_token'] === $idempotency) {
        echo json_encode([
            'success'    => true,
            'message'    => 'Order already submitted.',
            'order_code' => $_SESSION['last_order_code'] ?? '',
            'order_id'   => $_SESSION['last_order_id'] ?? 0,
            'redirect'   => 'order-confirmation.php?code=' . urlencode($_SESSION['last_order_code'] ?? '')
        ]);
        exit;
    }
}

// 4. Server-side Price & Stock Re-verification
$calculatedSubtotal = 0;
$verifiedItems = [];

foreach ($cartData['items'] as $item) {
    $foodId = (int)$item['food_id'];
    $stmtFood = $pdo->prepare("SELECT id, name, price, is_available FROM food_items WHERE id = ?");
    $stmtFood->execute([$foodId]);
    $food = $stmtFood->fetch();

    if (!$food || !$food['is_available']) {
        echo json_encode([
            'success' => false,
            'message' => "Item '{$item['food_name']}' is currently unavailable. Please adjust your cart."
        ]);
        exit;
    }

    $unitPrice = (float)$food['price'];
    $qty = (int)$item['quantity'];
    if ($qty <= 0) $qty = 1;

    $lineTotal = $unitPrice * $qty;
    $calculatedSubtotal += $lineTotal;

    $verifiedItems[] = [
        'cart_item_id'     => $item['cart_item_id'],
        'food_id'          => $food['id'],
        'food_name'        => $food['name'],
        'unit_price'       => $unitPrice,
        'quantity'         => $qty,
        'line_total'       => $lineTotal,
        'allergen_warning' => $item['allergen_warning'] ? 1 : 0,
        'allergen_details' => !empty($item['allergen_details']) ? json_encode($item['allergen_details']) : null,
        'modifications'    => $item['modifications'] ?? [],
    ];
}

$tax = 0.00;
$total = $calculatedSubtotal + $tax;

// Generate Order Code (e.g. ORD-20261015-7B2A)
$orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));

// 5. Database Transaction
$pdo->beginTransaction();
try {
    // Insert into orders table
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (order_code, user_id, customer_name, customer_email, customer_phone, delivery_address, pickup_time, subtotal, tax, total, status, special_notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?)
    ");
    $stmtOrder->execute([
        $orderCode,
        $userId,
        $name,
        $email,
        $phone,
        $address,
        $pickupTime,
        $calculatedSubtotal,
        $tax,
        $total,
        $notes
    ]);
    $orderId = $pdo->lastInsertId();

    // Insert order items & modifications
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, food_id, food_name, unit_price, quantity, line_total, allergen_warning, allergen_details)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtMod = $pdo->prepare("
        INSERT INTO order_item_modifications (order_item_id, ingredient_name, modification_type, instructions)
        VALUES (?, ?, ?, ?)
    ");

    // Optional direct modifications submitted from checkout form (e.g. removals per food_id)
    $formModifications = $_POST['item_modifications'] ?? [];

    foreach ($verifiedItems as $vItem) {
        $stmtItem->execute([
            $orderId,
            $vItem['food_id'],
            $vItem['food_name'],
            $vItem['unit_price'],
            $vItem['quantity'],
            $vItem['line_total'],
            $vItem['allergen_warning'],
            $vItem['allergen_details']
        ]);
        $orderItemId = $pdo->lastInsertId();

        // Save modifications from cart
        if (!empty($vItem['modifications'])) {
            foreach ($vItem['modifications'] as $mod) {
                $stmtMod->execute([
                    $orderItemId,
                    $mod['ingredient_name'] ?? 'Custom',
                    $mod['modification_type'] ?? 'removal',
                    $mod['instructions'] ?? ''
                ]);
            }
        }

        // Check if checkout form submitted specific removals for this food item
        $fId = $vItem['food_id'];
        if (!empty($formModifications[$fId]['removals']) && is_array($formModifications[$fId]['removals'])) {
            $notes = $formModifications[$fId]['notes'] ?? '';
            foreach ($formModifications[$fId]['removals'] as $remIng) {
                $remIng = trim($remIng);
                if (!empty($remIng)) {
                    $stmtMod->execute([
                        $orderItemId,
                        $remIng,
                        'removal',
                        $notes
                    ]);
                }
            }
        }
    }

    // Commit Transaction
    $pdo->commit();

    // 6. CLEAR ACTIVE CART ONLY AFTER SUCCESSFUL ORDER
    clear_active_cart();

    // Save tokens for idempotency
    if (!empty($idempotency)) {
        $_SESSION['last_order_token'] = $idempotency;
    }
    $_SESSION['last_order_code'] = $orderCode;
    $_SESSION['last_order_id']   = $orderId;

    echo json_encode([
        'success'    => true,
        'message'    => 'Order placed successfully! Reference: ' . $orderCode,
        'order_code' => $orderCode,
        'order_id'   => $orderId,
        'total'      => $total,
        'redirect'   => 'order-confirmation.php?code=' . urlencode($orderCode)
    ]);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order placement error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process order. Your active cart has been preserved. Error: ' . $e->getMessage()
    ]);
    exit;
}
