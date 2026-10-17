<?php
// shop.php - Dynamic Menu Page with Database Integration and Allergen Detection
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Menu - Smart Canteen Management System';
$currentPage = 'menu';
$pdo = get_db_connection();

// Fetch all available food items from database
$stmt = $pdo->query("
    SELECT f.id, f.name, f.category, f.description, f.price, f.image, f.is_available, f.ingredients_verified,
           GROUP_CONCAT(DISTINCT i.name ORDER BY i.name SEPARATOR ', ') AS ingredients_summary,
           GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ', ') AS allergens_summary
    FROM food_items f
    LEFT JOIN food_ingredients fi ON f.id = fi.food_id
    LEFT JOIN ingredients i ON fi.ingredient_id = i.id
    LEFT JOIN ingredient_allergens ia ON i.id = ia.ingredient_id
    LEFT JOIN allergens a ON ia.allergen_id = a.id
    GROUP BY f.id
    ORDER BY f.id ASC
");
$foodItems = $stmt->fetchAll();

// Fetch distinct categories for filter
$categories = ['lunch', 'breakfast', 'drinks'];

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Fresh, Nutritious & Transparent</p>
						<h1>Dishes Lineup</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- products -->
	<div class="product-section mt-100 mb-150">
		<div class="container">

            <!-- Allergen Protection Notification Banner -->
            <?php if (is_logged_in()): ?>
                <?php $userProf = get_current_user_profile(); ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-info d-flex justify-content-between align-items-center shadow-sm" style="border-radius: 12px; border-left: 5px solid #F28123; background-color: #fff9f5;">
                            <div>
                                <i class="fas fa-shield-alt text-warning fa-lg mr-2"></i>
                                <strong>Active Allergen Guard:</strong> Safe ordering enabled for <strong><?php echo e($userProf['name']); ?></strong>.
                                <?php if (!$userProf['allergen_profile_completed']): ?>
                                    <span class="text-danger ml-2 font-weight-bold"><i class="fas fa-exclamation-circle"></i> Profile Incomplete!</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="profile.php" class="btn btn-sm btn-outline-warning text-dark font-weight-bold" style="border-radius: 20px;">
                                    <i class="fas fa-sliders-h mr-1"></i> Update Allergen Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-secondary d-flex justify-content-between align-items-center shadow-sm" style="border-radius: 12px;">
                            <div>
                                <i class="fas fa-user-lock mr-2 text-secondary"></i>
                                <strong>Guest Browsing:</strong> Log in to enable personalized allergen detection and ingredient filtering for your safety.
                            </div>
                            <div>
                                <a href="login.php?return=shop.php" class="btn btn-sm btn-dark" style="border-radius: 20px;">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Sign In / Register
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

			<div class="row">
                <div class="col-md-12">
                    <div class="product-filters">
                        <ul>
                            <li class="active" data-filter="*">All</li>
                            <li data-filter=".lunch">lunch</li>
                            <li data-filter=".breakfast">breakfast</li>
                            <li data-filter=".drinks">drinks</li>
                        </ul>
                    </div>
                </div>
            </div>

			<div class="row product-lists">
                <?php foreach ($foodItems as $item): ?>
                    <?php 
                        $catClass = strtolower(trim($item['category']));
                        $priceFormatted = number_format($item['price'], 0) . 'tk';
                        $imgSrc = !empty($item['image']) ? $item['image'] : 'assets/img/products/product-img-1.jpg';
                        $isAvailable = (bool)$item['is_available'];
                        $isVerified = (bool)$item['ingredients_verified'];
                    ?>
                    <div class="col-lg-4 col-md-6 text-center <?php echo e($catClass); ?>">
                        <div class="single-product-item shadow-sm" style="border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                            <div class="product-image" style="position: relative;">
                                <img src="<?php echo e($imgSrc); ?>" alt="<?php echo e($item['name']); ?>" style="height: 220px; width: 100%; object-fit: cover;">
                                <?php if (!$isVerified): ?>
                                    <span class="badge badge-warning position-absolute" style="top: 10px; right: 10px; padding: 6px 10px; border-radius: 8px;">
                                        <i class="fas fa-question-circle"></i> Unverified
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="mt-3"><?php echo e($item['name']); ?></h3>
                            <p class="product-price">
                                <span><?php echo e($item['category']); ?></span> <?php echo e($priceFormatted); ?>
                            </p>
                            
                            <?php if (!empty($item['ingredients_summary'])): ?>
                                <p class="text-muted small px-3 mb-3 text-truncate" title="<?php echo e($item['ingredients_summary']); ?>">
                                    <i class="fas fa-leaf text-success mr-1"></i> 
                                    <?php echo e($item['ingredients_summary']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($isAvailable): ?>
                                <button type="button" class="cart-btn btn-add-to-cart border-0" data-food-id="<?php echo $item['id']; ?>" style="cursor: pointer;">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            <?php else: ?>
                                <button type="button" class="cart-btn bg-secondary border-0 text-white" disabled style="cursor: not-allowed;">
                                    <i class="fas fa-ban"></i> Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
			</div>

		</div>
	</div>
	<!-- end products -->

<?php include __DIR__ . '/includes/footer.php'; ?>
