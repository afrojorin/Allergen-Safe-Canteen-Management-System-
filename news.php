<?php
// news.php - Health Articles & Nutritional Insights
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Health Articles - Smart Canteen Management System';
$currentPage = 'health';

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Fresh Flavors, Happy Faces & Healthy Minds</p>
						<h1>Health Articles & Dietary Guides</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- latest news -->
	<div class="latest-news mt-100 mb-150">
		<div class="container">
			<div class="row">
				<div class="col-lg-4 col-md-6 mb-4">
					<div class="single-latest-news shadow-sm" style="border-radius:12px; overflow:hidden;">
						<a href="single-news.php"><div class="latest-news-bg news-bg-1"></div></a>
						<div class="news-text-box">
							<h3><a href="single-news.php">Managing Blood Sugar for a Healthier Life</a></h3>
							<p class="blog-meta">
								<span class="author"><i class="fas fa-user"></i> Canteen Nutritionist</span>
								<span class="date"><i class="fas fa-calendar"></i> October 2024</span>
							</p>
							<p class="excerpt">How automated canteen nutrition tracking and low-glycemic dish options support steady daily energy levels and long-term metabolic health.</p>
							<a href="single-news.php" class="read-more-btn">Read More <i class="fas fa-angle-right"></i></a>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6 mb-4">
					<div class="single-latest-news shadow-sm" style="border-radius:12px; overflow:hidden;">
						<a href="single-news.php"><div class="latest-news-bg news-bg-2"></div></a>
						<div class="news-text-box">
							<h3><a href="single-news.php">Understanding Common Food Allergens</a></h3>
							<p class="blog-meta">
								<span class="author"><i class="fas fa-user"></i> Campus Health</span>
								<span class="date"><i class="fas fa-calendar"></i> October 2024</span>
							</p>
							<p class="excerpt">A guide to the top 10 allergens including dairy, peanuts, mustard, and eggs, and how our relational ingredient database keeps you safe.</p>
							<a href="single-news.php" class="read-more-btn">Read More <i class="fas fa-angle-right"></i></a>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6 mb-4">
					<div class="single-latest-news shadow-sm" style="border-radius:12px; overflow:hidden;">
						<a href="single-news.php"><div class="latest-news-bg news-bg-3"></div></a>
						<div class="news-text-box">
							<h3><a href="single-news.php">Customized Nutrition for Peak Academic Focus</a></h3>
							<p class="blog-meta">
								<span class="author"><i class="fas fa-user"></i> Wellness Team</span>
								<span class="date"><i class="fas fa-calendar"></i> October 2024</span>
							</p>
							<p class="excerpt">Learn how customizing your daily plates by requesting ingredient removals helps tailor calories and macros to your personal goals.</p>
							<a href="single-news.php" class="read-more-btn">Read More <i class="fas fa-angle-right"></i></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end latest news -->

<?php include __DIR__ . '/includes/footer.php'; ?>
