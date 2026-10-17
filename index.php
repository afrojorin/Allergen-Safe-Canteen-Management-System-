<?php
// index.php - Main Homepage with Dynamic Allergen Detection & Featured Dishes
require_once __DIR__ . '/config/db.php';

$pageTitle = 'CANTEEN - Where Every Meal Feels Like Home';
$currentPage = 'home';
$pdo = get_db_connection();

// Fetch 3 featured dishes from database
$stmtFeatured = $pdo->query("
    SELECT id, name, category, price, image, is_available, ingredients_verified 
    FROM food_items 
    ORDER BY id ASC 
    LIMIT 3
");
$featuredDishes = $stmtFeatured->fetchAll();

include __DIR__ . '/includes/header.php';
?>

	<!-- search area -->
	<div class="search-area">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<span class="close-btn"><i class="fas fa-window-close"></i></span>
					<div class="search-bar">
						<div class="search-bar-tablecell">
							<h3>Search For:</h3>
							<input type="text" placeholder="Keywords">
							<button type="submit">Search <i class="fas fa-search"></i></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end search area -->

	<!-- home page slider - 3 horizontal banner panels -->
	<div class="homepage-slider-horizontal">
		<!-- Panel 1 -->
		<div class="hero-panel" style="background-image: url('pictures/bg/background 1.jpg');">
			<div class="hero-panel-overlay"></div>
			<div class="hero-panel-content">
				<p class="subtitle">Fresh Flavors, Happy Faces!</p>
				<h1>Where Every Meal Feels Like Home!</h1>
				<div class="hero-btns">
					<a href="shop.php" class="boxed-btn">Browse Menu</a>
				</div>
			</div>
		</div>
		<!-- Panel 2 -->
		<div class="hero-panel" style="background-image: url('pictures/bg/background 2.jpg');">
			<div class="hero-panel-overlay"></div>
			<div class="hero-panel-content">
				<p class="subtitle">Quick Bites, Big Smiles</p>
				<h1>Bringing the Canteen Experience to You!</h1>
				<div class="hero-btns">
					<a href="shop.php" class="boxed-btn">Order Food</a>
				</div>
			</div>
		</div>
		<!-- Panel 3 -->
		<div class="hero-panel" style="background-image: url('pictures/bg/background 3.jpg');">
			<div class="hero-panel-overlay"></div>
			<div class="hero-panel-content">
				<p class="subtitle">Healthy Choices for Smarter Minds!</p>
				<h1>Healthy Fuel for Productive Days!</h1>
				<div class="hero-btns">
					<a href="news.php" class="boxed-btn">Health Articles</a>
				</div>
			</div>
		</div>
	</div>
	<!-- end home page slider -->


	<!-- features list section -->
	<div class="list-section pt-80 pb-80">
		<div class="container">

			<div class="row">
				<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
					<div class="list-box d-flex align-items-center">
						<div class="list-icon">
							<i class="fas fa-utensils"></i>
						</div>
						<div class="content">
							<h3>Fresh Flavors</h3>
							<p>Quick Ordering & Fast Campus Pickup</p>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
					<div class="list-box d-flex align-items-center">
						<div class="list-icon">
							<i class="fas fa-shield-alt"></i>
						</div>
						<div class="content">
							<h3>Allergy Aware</h3>
							<p>Automated Database Allergen Guard</p>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="list-box d-flex justify-content-start align-items-center">
						<div class="list-icon">
							<i class="fas fa-sliders-h"></i>
						</div>
						<div class="content">
							<h3>Customization</h3>
							<p>Request Ingredient Removals at Checkout</p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	<!-- end features list section -->

	<!-- product section -->
	<div class="product-section mt-100 mb-100">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="section-title">	
						<h3><span class="orange-text">Featured</span> Dishes</h3>
						<p>Discover a diverse selection of freshly prepared dishes, catering to every craving and dietary need with automated ingredient protection.</p>
					</div>
				</div>
			</div>

			<div class="row">
                <?php foreach ($featuredDishes as $dish): ?>
				<div class="col-lg-4 col-md-6 text-center">
					<div class="single-product-item shadow-sm" style="border-radius: 12px; overflow: hidden;">
						<div class="product-image">
							<img src="<?php echo e($dish['image']); ?>" alt="<?php echo e($dish['name']); ?>" style="height: 220px; width: 100%; object-fit: cover;">
						</div>
						<h3 class="mt-3"><?php echo e($dish['name']); ?></h3>
						<p class="product-price"><span><?php echo e($dish['category']); ?></span> <?php echo number_format($dish['price'], 0); ?>tk</p>
						<button type="button" class="cart-btn btn-add-to-cart border-0" data-food-id="<?php echo $dish['id']; ?>" style="cursor: pointer;">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
					</div>
				</div>
                <?php endforeach; ?>
			</div>

            <div class="row mt-4">
                <div class="col-12 text-center">
                    <a href="shop.php" class="boxed-btn black py-3 px-5 font-weight-bold" style="border-radius: 30px; background: #051922;">
                        View Full Canteen Menu <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
		</div>
	</div>
	<!-- end product section -->

	<!-- cart banner section -->
	<section class="cart-banner pt-100 pb-100">
    	<div class="container">
        	<div class="row clearfix align-items-center">
            	<!--Image Column-->
            	<div class="image-column col-lg-6">
                	<div class="image">
                    	<div class="price-box">
                        	<div class="inner-price">
                                <span class="price">
                                    <strong>Meals</strong> <br> Fresh Everyday
                                </span>
                            </div>
                        </div>
                    	<img src="pictures/smart canteen.png" alt="Smart Canteen" style="border-radius: 12px; width: 100%; object-fit: cover;">
                    </div>
                </div>
                <!--Content Column-->
                <div class="content-column col-lg-6">
					<h3><span class="orange-text">Smart Canteen Services</span></h3>
                    <h4>with Sustainability & Dietary Accessibility</h4>
                    <div class="text">
                        Our digital canteen platform allows students and staff to effortlessly browse live menu items, check allergen details, specify ingredient removals, and order securely without paper waste or long queue times. Designed to safeguard individual health and promote campus wellness.
                    </div>
                   
                	<a href="shop.php" class="cart-btn mt-4"><i class="fas fa-utensils"></i> Explore Menu</a>
                </div>
            </div>
        </div>
    </section>
    <!-- end cart banner section -->

	<!-- advertisement section -->
	<div class="abt-section my-5">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-6 col-md-12">
					<div class="abt-bg" style="border-radius: 12px; overflow: hidden;">
						<img src="pictures/about us.jpg" alt="About Canteen" style="width: 100%; border-radius: 12px;">
					</div>
				</div>
				<div class="col-lg-6 col-md-12">
					<div class="abt-text">
						<p class="top-sub">University Dining Hall</p>
						<h2>About <span class="orange-text">Canteen</span></h2>
						<p>Our canteen management system is designed to deliver seamless, efficient, and user-friendly dining experiences for students, employees, and faculty alike.</p>
						<p>We specialize in tech-enabled solutions that streamline operations, eliminate wait times, and guarantee allergen safety through relational ingredient tracking and customizable meal preparation.</p>
						<a href="about.php" class="boxed-btn mt-3">Know More</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end advertisement section -->

<?php include __DIR__ . '/includes/footer.php'; ?>
