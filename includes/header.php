<?php
// includes/header.php - Shared Navigation, Metadata, and Allergen Warning Modal
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/db.php';
}

$pageTitle = $pageTitle ?? 'CANTEEN - Smart Canteen & Allergen Detection System';
$currentPage = $currentPage ?? 'home';
$cartUnits = get_cart_total_units();
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Smart Canteen Management System with User Authentication, Database Integration, and Automated Allergen Detection">

	<!-- title -->
	<title><?php echo e($pageTitle); ?></title>

	<!-- favicon -->
	<link rel="shortcut icon" type="image/png" href="assets/img/favicon.png">
	<!-- google font -->
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet">
	<!-- Bangla font for Bengali menu text -->
	<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
	<!-- fontawesome -->
	<link rel="stylesheet" href="assets/css/all.min.css">
	<!-- bootstrap -->
	<link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
	<!-- owl carousel -->
	<link rel="stylesheet" href="assets/css/owl.carousel.css">
	<!-- magnific popup -->
	<link rel="stylesheet" href="assets/css/magnific-popup.css">
	<!-- animate css -->
	<link rel="stylesheet" href="assets/css/animate.css">
	<!-- mean menu css -->
	<link rel="stylesheet" href="assets/css/meanmenu.min.css">
	<!-- main style -->
	<link rel="stylesheet" href="assets/css/main.css">
	<!-- responsive -->
	<link rel="stylesheet" href="assets/css/responsive.css">

    <!-- Custom Enhancements: Allergen Warnings, Cart Badge, Modal Styling -->
    <style>
        .cart-icon-wrapper {
            position: relative;
            display: inline-block;
        }
        .cart-badge {
            position: absolute;
            top: -9px;
            right: -12px;
            background: #F28123;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 50%;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.25);
            transition: transform 0.2s ease;
        }
        .cart-badge.bounce {
            transform: scale(1.3);
        }
        .user-nav-dropdown {
            display: inline-block;
            position: relative;
        }
        .user-nav-dropdown .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border: none;
            padding: 10px 0;
            min-width: 200px;
            right: 0;
            left: auto;
        }
        .user-nav-dropdown .dropdown-item {
            padding: 8px 20px;
            font-size: 14px;
            color: #333;
            transition: all 0.2s;
        }
        .user-nav-dropdown .dropdown-item:hover {
            background-color: #FFF5EE;
            color: #F28123;
        }
        .user-nav-dropdown .dropdown-item i {
            margin-right: 8px;
            width: 16px;
            color: #F28123;
        }
        .allergen-tag {
            display: inline-block;
            background-color: #fff1f0;
            color: #cf1322;
            border: 1px solid #ffa39e;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px 4px 2px 0;
        }
        .allergen-tag.safe {
            background-color: #f6ffed;
            color: #389e0d;
            border-color: #b7eb8f;
        }
        .allergen-warning-badge {
            background: #fff2e8;
            border: 1px solid #ffbb96;
            color: #d4380d;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        /* Custom Allergen Warning Modal */
        #allergenWarningModal .modal-dialog {
            max-width: 540px;
            margin-top: 100px;
        }
        #allergenWarningModal .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        }
        #allergenWarningModal .modal-header {
            background: #fff2e8;
            border-bottom: 2px solid #ffd591;
            padding: 20px 25px;
        }
        #allergenWarningModal .modal-title {
            color: #d4380d;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #allergenWarningModal .modal-body {
            padding: 25px;
            font-size: 15px;
            line-height: 1.6;
            color: #444;
        }
        #allergenWarningModal .btn-cancel {
            background: #e9ecef;
            color: #495057;
            font-weight: 600;
            border-radius: 30px;
            padding: 10px 24px;
            border: none;
            transition: all 0.2s;
        }
        #allergenWarningModal .btn-cancel:hover {
            background: #dee2e6;
            color: #212529;
        }
        #allergenWarningModal .btn-continue {
            background: #F28123;
            color: #fff;
            font-weight: 600;
            border-radius: 30px;
            padding: 10px 24px;
            border: none;
            transition: all 0.2s;
        }
        #allergenWarningModal .btn-continue:hover {
            background: #051922;
            color: #fff;
        }
        /* Flash message alerts */
        .canteen-alert {
            position: fixed;
            top: 25px;
            right: 25px;
            z-index: 99999;
            min-width: 320px;
            max-width: 450px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18);
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        /* Bangla script support */
        .single-product-item h3,
        .product-filters ul li,
        .bangla-text {
            font-family: 'Hind Siliguri', 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
	
	<!--PreLoader-->
    <div class="loader">
        <div class="loader-inner">
            <div class="circle"></div>
        </div>
    </div>
    <!--PreLoader Ends-->
	
	<!-- header -->
	<div class="top-header-area" id="sticker">
		<div class="container">
			<div class="row">
				<div class="col-lg-12 col-sm-12 text-center">
					<div class="main-menu-wrap">
						<!-- logo -->
						<div class="site-logo">
							<a href="index.php">
								<img src="pictures/logo.jpg" alt="Canteen Logo" style="max-height: 50px; border-radius: 6px;">
							</a>
						</div>
						<!-- logo -->

						<!-- menu start -->
						<nav class="main-menu">
							<ul>
								<li class="<?php echo ($currentPage === 'home') ? 'current-list-item' : ''; ?>">
                                    <a href="index.php">Home</a>
								</li>
								<li class="<?php echo ($currentPage === 'about') ? 'current-list-item' : ''; ?>">
                                    <a href="about.php">About</a>
                                </li>
								<li class="<?php echo ($currentPage === 'menu') ? 'current-list-item' : ''; ?>">
                                    <a href="shop.php">Menu</a>
								</li>
								<li class="<?php echo ($currentPage === 'health') ? 'current-list-item' : ''; ?>">
                                    <a href="news.php">Health</a>
								</li>
								<li class="<?php echo ($currentPage === 'contact') ? 'current-list-item' : ''; ?>">
                                    <a href="contact.php">Contact</a>
                                </li>
                                <?php if ($currentUser): ?>
                                <li class="<?php echo ($currentPage === 'profile') ? 'current-list-item' : ''; ?>">
                                    <a href="profile.php"><i class="fas fa-shield-alt text-warning mr-1"></i> Allergens</a>
                                </li>
                                <?php endif; ?>
								
								<li>
									<div class="header-icons">
										<a class="shopping-cart cart-icon-wrapper" href="cart.php" title="View Cart">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span class="cart-badge" id="headerCartCount"><?php echo $cartUnits; ?></span>
                                        </a>

                                        <?php if ($currentUser): ?>
                                        <div class="dropdown d-inline-block user-nav-dropdown">
                                            <a href="#" class="dropdown-toggle" id="userMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color:#fff;" title="<?php echo e($currentUser['name']); ?>">
                                                <i class="fas fa-user-circle"></i> <span class="d-none d-lg-inline ml-1" style="font-size:14px;"><?php echo e(explode(' ', $currentUser['name'])[0]); ?></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userMenuButton">
                                                <div class="dropdown-item disabled font-weight-bold" style="color:#888;">
                                                    <i class="fas fa-user"></i> <?php echo e($currentUser['name']); ?>
                                                </div>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="profile.php">
                                                    <i class="fas fa-allergies"></i> Allergen Profile
                                                </a>
                                                <a class="dropdown-item" href="cart.php">
                                                    <i class="fas fa-shopping-bag"></i> Active Cart (<span class="headerUserCartUnits"><?php echo $cartUnits; ?></span>)
                                                </a>
                                                <a class="dropdown-item" href="checkout.php">
                                                    <i class="fas fa-credit-card"></i> Checkout
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="api/auth.php?action=logout">
                                                    <i class="fas fa-sign-out-alt text-danger"></i> Sign Out
                                                </a>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <a class="fa fa-user" href="login.php" title="Sign In / Register"></a>
                                        <?php endif; ?>
									</div>
								</li>
							</ul>
						</nav>
						<div class="mobile-menu"></div>
						<!-- menu end -->
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end header -->

    <!-- Global Toast Alert Container -->
    <div id="canteenToastContainer"></div>

    <!-- Allergen Warning Popup Modal -->
    <div class="modal fade" id="allergenWarningModal" tabindex="-1" role="dialog" aria-labelledby="allergenWarningModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allergenWarningModalLabel">
                        <i class="fas fa-exclamation-triangle text-danger fa-lg"></i> Allergen Safety Warning
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="allergenModalCloseX">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="allergenModalFoodImg" src="assets/img/products/dish1.jpg" alt="Dish Preview" style="max-height: 120px; border-radius: 10px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                        <h4 id="allergenModalFoodName" class="font-weight-bold mt-2" style="color: #051922;">Food Name</h4>
                    </div>

                    <div id="allergenWarningDetailsBox" class="p-3 mb-3" style="background: #fff1f0; border: 1px solid #ffa39e; border-radius: 8px;">
                        <p class="mb-2 font-weight-bold text-danger">
                            <i class="fas fa-info-circle mr-1"></i> The following ingredients trigger your saved allergen profile:
                        </p>
                        <div id="allergenModalBadges" class="mb-2"></div>
                        <p id="allergenModalExplanation" class="text-muted small mb-0"></p>
                    </div>

                    <div class="alert alert-warning small mb-0" role="alert">
                        <i class="fas fa-shield-alt mr-1"></i> You can cancel this selection, or choose to continue anyway. If you continue, the allergen alert will be preserved in your cart and order summary.
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <!-- Option A: Cancel -->
                    <button type="button" class="btn btn-cancel" id="btnCancelAllergen" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancel (Do Not Add)
                    </button>
                    <!-- Option B: Continue Anyway -->
                    <button type="button" class="btn btn-continue" id="btnContinueAllergen">
                        <i class="fas fa-check mr-1"></i> Continue Anyway
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Allergen Warning Modal -->
