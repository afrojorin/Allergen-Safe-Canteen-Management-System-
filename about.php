<?php
// about.php - About Canteen Management System
require_once __DIR__ . '/config/db.php';

$pageTitle = 'About Us - Smart Canteen Management System';
$currentPage = 'about';

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Every Meal Feels Like Home</p>
						<h1>About Us</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- featured section -->
	<div class="feature-bg my-5">
		<div class="container">
			<div class="row">
				<div class="col-lg-7">
					<div class="featured-text">
						<h2 class="pb-3">Why Use <span class="orange-text">CANTEEN?</span></h2>
						<div class="row">
							<div class="col-lg-6 col-md-6 mb-4 mb-md-5">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-seedling"></i>
									</div>
									<div class="content">
										<h3>Nutritious</h3>
										<p>Fresh, healthy ingredients sourced daily to provide balanced nutrition for studying and working minds.</p>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 mb-5 mb-md-5">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-shield-alt text-warning"></i>
									</div>
									<div class="content">
										<h3>Allergy Aware</h3>
										<p>Intelligent cross-referencing between user allergen profiles and database recipes prevents accidental exposure.</p>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 mb-5 mb-md-5">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-sliders-h"></i>
									</div>
									<div class="content">
										<h3>Customization</h3>
										<p>Easily request removable ingredients at checkout to personalize your meal to dietary preferences.</p>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 mb-5 mb-md-5">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-sync-alt"></i>
									</div>
									<div class="content">
										<h3>Synchronized Cart</h3>
										<p>Real-time order synchronization with zero accidental cart resets across browsing and checkout.</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
                <div class="col-lg-5 d-flex align-items-center">
                    <img src="pictures/about us.jpg" alt="About Canteen" style="border-radius: 16px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                </div>
			</div>
		</div>
	</div>
	<!-- end featured section -->

<?php include __DIR__ . '/includes/footer.php'; ?>
