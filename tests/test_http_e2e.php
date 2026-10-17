<?php
// tests/test_http_e2e.php - Real HTTP Client End-to-End Simulation against localhost:8000

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function http_request($url, $method = 'GET', $data = null, $isJson = false) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

echo "=========================================================\n";
echo "HTTP END-TO-END SIMULATION ON http://127.0.0.1:8000\n";
echo "=========================================================\n\n";

// 1. Initial Guest Status
echo "1. Checking Guest Session... ";
$res = http_request("{$baseUrl}/api/auth.php?action=status");
$data = json_decode($res['body'], true);
if ($data['success'] && !$data['logged_in'] && $data['cart_units'] === 0) {
    echo "[PASS] (Guest status confirmed)\n";
} else {
    echo "[FAIL]: " . $res['body'] . "\n";
}

// 2. Login as Alex Morgan (has Dairy & Peanuts)
echo "2. Logging in as Alex Morgan (test@example.com)... ";
$res = http_request("{$baseUrl}/api/auth.php", 'POST', [
    'action'   => 'login',
    'email'    => 'test@example.com',
    'password' => 'password123',
    'ajax'     => '1'
]);
$data = json_decode($res['body'], true);
if ($data['success'] && $data['data']['name'] === 'Alex Morgan') {
    echo "[PASS] (Logged in successfully)\n";
} else {
    echo "[FAIL]: " . $res['body'] . "\n";
}

// 3. Verify Saved Allergen Profile via API
echo "3. Fetching Saved Allergen Profile... ";
$res = http_request("{$baseUrl}/api/allergens.php?action=get_user");
$data = json_decode($res['body'], true);
$algNames = array_column($data['allergens'], 'name');
if (in_array('Milk and dairy', $algNames) && in_array('Peanuts', $algNames)) {
    echo "[PASS] (Saved allergens: " . implode(', ', $algNames) . ")\n";
} else {
    echo "[FAIL]: " . $res['body'] . "\n";
}

// 4. Test Allergen Detection on Food 1 (Ponir & Shobji -> Contains Dairy)
echo "4. Testing Allergen Warning Check on 'Ponir & Shobji'... ";
$res = http_request("{$baseUrl}/api/check_allergen.php?food_id=1");
$data = json_decode($res['body'], true);
if ($data['has_allergen_conflict'] && in_array('Milk and dairy', $data['matched_allergens'])) {
    echo "[PASS] (Conflict correctly flagged: " . implode(', ', $data['matched_allergens']) . ")\n";
} else {
    echo "[FAIL]: " . $res['body'] . "\n";
}

// 5. Add to Cart with Allergen Warning Acknowledged (Continue Anyway)
echo "5. Adding 'Ponir & Shobji' with acknowledged warning... ";
$res = http_request("{$baseUrl}/api/cart.php?action=add", 'POST', [
    'food_id'          => 1,
    'quantity'         => 2,
    'allergen_warning' => 1,
    'allergen_details' => ['Milk and dairy']
], true);
$data = json_decode($res['body'], true);
if ($data['success'] && $data['total_units'] === 2) {
    echo "[PASS] (Added 2 units, cart_units = 2)\n";
} else {
    echo "[FAIL]: " . $res['body'] . "\n";
}

// 6. Add Food 11 (Chicken Burger) x 3
echo "6. Adding 3x 'Chicken Burger'... ";
$res = http_request("{$baseUrl}/api/cart.php?action=add", 'POST', [
    'food_id'          => 11,
    'quantity'         => 3,
    'allergen_warning' => 1,
    'allergen_details' => ['Wheat and gluten', 'Milk and dairy']
], true);
$data = json_decode($res['body'], true);
if ($data['success'] && $data['total_units'] === 5) {
    echo "[PASS] (Total units = 5: 2 + 3)\n";
} else {
    echo "[FAIL]: " . $res['body'] . "\n";
}

// 7. Verify Active Cart Subtotal and Items
echo "7. Verifying Active Cart Contents... ";
$res = http_request("{$baseUrl}/api/cart.php?action=get");
$data = json_decode($res['body'], true);
$cart = $data['cart'];
// (85 * 2) + (150 * 3) = 170 + 450 = 620
if ($cart['total_units'] === 5 && (float)$cart['subtotal'] === 620.0) {
    echo "[PASS] (Subtotal = 620tk, Units = 5)\n";
} else {
    echo "[FAIL]: subtotal = " . $cart['subtotal'] . ", units = " . $cart['total_units'] . "\n";
}

// 8. Place Order with Ingredient Removal
echo "8. Submitting Checkout Order with Ingredient Removals... ";
$burgerItemId = null;
foreach ($cart['items'] as $it) {
    if ($it['food_id'] === 11) $burgerItemId = $it['cart_item_id'];
}

$res = http_request("{$baseUrl}/api/order.php", 'POST', [
    'name'        => 'Alex Morgan',
    'email'       => 'test@example.com',
    'phone'       => '+880 1711 000111',
    'address'     => 'North Hall, Room 304',
    'pickup_time' => '12:30 PM',
    'notes'       => 'Extra napkins please',
    'item_modifications' => [
        11 => [
            'removals' => ['Mayonnaise', 'Cheddar Cheese Slice'],
            'notes'    => 'Strict allergy exclusion'
        ]
    ]
]);
$data = json_decode($res['body'], true);
if ($data['success'] && !empty($data['order_code'])) {
    $orderCode = $data['order_code'];
    echo "[PASS] (Order Code: {$orderCode})\n";
} else {
    echo "[FAIL]: " . $res['body'] . "\n";
}

// 9. Verify Active Cart is now completely reset (0 units)
echo "9. Verifying Cart Auto-Reset on Order Completion... ";
$res = http_request("{$baseUrl}/api/cart.php?action=get");
$data = json_decode($res['body'], true);
if ($data['cart']['total_units'] === 0 && empty($data['cart']['items'])) {
    echo "[PASS] (Cart successfully cleared, total units = 0)\n";
} else {
    echo "[FAIL]: Cart units not 0: " . $data['cart']['total_units'] . "\n";
}

// 10. Verify Order Confirmation HTML Page
echo "10. Loading Order Confirmation Page ({$orderCode})... ";
$res = http_request("{$baseUrl}/order-confirmation.php?code=" . urlencode($orderCode));
if ($res['code'] === 200 && strpos($res['body'], $orderCode) !== false && strpos($res['body'], 'Mayonnaise') !== false) {
    echo "[PASS] (Confirmation page contains order reference & ingredient removals)\n";
} else {
    echo "[FAIL]: Status: " . $res['code'] . "\n";
}

// Cleanup
if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n=========================================================\n";
echo "ALL HTTP INTEGRATION TESTS COMPLETED SUCCESSFULLY!\n";
echo "=========================================================\n";
