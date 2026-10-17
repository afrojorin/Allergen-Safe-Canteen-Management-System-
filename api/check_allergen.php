<?php
// api/check_allergen.php - Allergen Detection and Verification API
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$foodId = (int)($_GET['food_id'] ?? $_POST['food_id'] ?? 0);

if ($foodId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid food item specified.'
    ]);
    exit;
}

$pdo = get_db_connection();

// 1. Fetch Food Item
$stmtFood = $pdo->prepare("SELECT id, name, category, description, price, image, is_available, ingredients_verified FROM food_items WHERE id = ?");
$stmtFood->execute([$foodId]);
$food = $stmtFood->fetch();

if (!$food) {
    echo json_encode([
        'success' => false,
        'message' => 'Food item not found.'
    ]);
    exit;
}

// 2. Fetch User Allergens
$userAllergens = [];
$userAllergenIds = [];
$isUserLoggedIn = is_logged_in();

if ($isUserLoggedIn) {
    $userId = $_SESSION['user_id'];
    $stmtUA = $pdo->prepare("
        SELECT a.id, a.name, a.slug
        FROM user_allergens ua
        JOIN allergens a ON ua.allergen_id = a.id
        WHERE ua.user_id = ?
    ");
    $stmtUA->execute([$userId]);
    $userAllergens = $stmtUA->fetchAll();
    $userAllergenIds = array_map(function($a) { return (int)$a['id']; }, $userAllergens);
}

// 3. Fetch Food Ingredients & their associated Allergens
$stmtIng = $pdo->prepare("
    SELECT i.id AS ingredient_id, i.name AS ingredient_name, fi.is_optional_removable,
           a.id AS allergen_id, a.name AS allergen_name, a.slug AS allergen_slug
    FROM food_ingredients fi
    JOIN ingredients i ON fi.ingredient_id = i.id
    LEFT JOIN ingredient_allergens ia ON i.id = ia.ingredient_id
    LEFT JOIN allergens a ON ia.allergen_id = a.id
    WHERE fi.food_id = ?
");
$stmtIng->execute([$foodId]);
$recipeRows = $stmtIng->fetchAll();

// Group recipe ingredients and check for allergen matches
$ingredientsList = [];
$matchedAllergens = [];
$triggeringIngredients = [];
$allFoodAllergens = [];

foreach ($recipeRows as $row) {
    $ingId = $row['ingredient_id'];
    if (!isset($ingredientsList[$ingId])) {
        $ingredientsList[$ingId] = [
            'id'                   => $ingId,
            'name'                 => $row['ingredient_name'],
            'is_optional_removable'=> (bool)$row['is_optional_removable'],
            'allergens'            => []
        ];
    }
    if (!empty($row['allergen_id'])) {
        $aId = (int)$row['allergen_id'];
        $aName = $row['allergen_name'];
        $ingredientsList[$ingId]['allergens'][] = $aName;
        $allFoodAllergens[$aId] = $aName;

        // Compare against user's allergen profile!
        if (in_array($aId, $userAllergenIds, true)) {
            $matchedAllergens[$aId] = $aName;
            $triggeringIngredients[$ingId] = $row['ingredient_name'];
        }
    }
}

$hasAllergenConflict = !empty($matchedAllergens);
$isUnverified = (int)$food['ingredients_verified'] === 0 || empty($recipeRows);

// Build explanation message
$explanation = '';
if ($hasAllergenConflict) {
    $allergenListStr = implode(', ', array_values($matchedAllergens));
    $ingListStr = implode(', ', array_values($triggeringIngredients));
    $explanation = "Warning: {$food['name']} contains {$ingListStr}, which matches your saved allergen profile for: {$allergenListStr}.";
} elseif ($isUnverified) {
    $explanation = "Notice: The ingredient and recipe data for {$food['name']} is currently incomplete or unverified. We cannot guarantee it is free of allergens.";
}

echo json_encode([
    'success'                => true,
    'logged_in'              => $isUserLoggedIn,
    'food'                   => [
        'id'                   => $food['id'],
        'name'                 => $food['name'],
        'price'                => (float)$food['price'],
        'image'                => $food['image'],
        'is_available'         => (bool)$food['is_available'],
        'ingredients_verified' => (bool)$food['ingredients_verified'],
    ],
    'has_allergen_conflict'  => $hasAllergenConflict,
    'is_unverified'          => $isUnverified,
    'matched_allergens'      => array_values($matchedAllergens),
    'triggering_ingredients' => array_values($triggeringIngredients),
    'all_ingredients'        => array_values($ingredientsList),
    'explanation'            => $explanation,
]);
