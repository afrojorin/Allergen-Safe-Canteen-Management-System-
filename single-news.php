<?php
// single-news.php - Single Article Detail
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Health Article - Smart Canteen Management System';
$currentPage = 'health';

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Campus Wellness & Nutrition</p>
						<h1>Managing Dietary Allergens</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- single article section -->
	<div class="mt-100 mb-150">
		<div class="container">
			<div class="row">
				<div class="col-lg-8">
					<div class="single-article-section">
						<div class="single-article-text">
							<div class="single-artcile-bg mb-4" style="background-image: url('assets/img/abt.jpg'); height: 350px; background-size: cover; border-radius: 12px;"></div>
							<p class="blog-meta">
								<span class="author"><i class="fas fa-user"></i> Canteen Health Committee</span>
								<span class="date"><i class="fas fa-calendar"></i> October 2024</span>
							</p>
							<h2>Understanding Common Food Allergens & Recipe Safety</h2>
							<p>Food allergies affect millions of individuals, and dining away from home can often cause unnecessary anxiety. Our smart canteen management system was built to provide complete peace of mind by linking every dish's recipe directly to individual student and staff profiles.</p>
							<p>When you configure your allergen profile, our system checks every food order in real-time. Whether an item contains dairy, peanuts, mustard oil, or eggs, you will be warned before the item is added to your active order.</p>
							<p>Furthermore, our kitchen customization options empower customers to request the exclusion of specific separable ingredients (such as mayonnaise, cheese slices, onions, or roasted nuts) directly at checkout.</p>
                            <div class="mt-4">
                                <a href="shop.php" class="boxed-btn" style="background:#F28123;"><i class="fas fa-utensils mr-2"></i> Browse Safe Menu Dishes</a>
                            </div>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="sidebar-section">
						<div class="recent-posts">
							<h4>Quick Links</h4>
							<ul>
								<li><a href="profile.php">Configure Allergen Profile</a></li>
								<li><a href="shop.php">Browse Current Dishes</a></li>
								<li><a href="cart.php">View Active Cart</a></li>
								<li><a href="contact.php">Contact Canteen Staff</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
