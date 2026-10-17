<?php
// contact.php - Contact & Support Page
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Contact Us - Smart Canteen Management System';
$currentPage = 'contact';

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Get Campus Food & Allergen Support</p>
						<h1>Contact Us</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- contact form -->
	<div class="contact-from-section mt-100 mb-150">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 mb-5 mb-lg-0">
					<div class="form-title">
						<h2>Have a Question or Dietary Inquiry?</h2>
						<p>We’re here to help! Whether you have questions regarding allergen recipes, want to report a dietary concern, or need assistance with your canteen order, feel free to reach out.</p>
					</div>
				 	<div id="form_status"></div>
					<div class="contact-form">
						<form method="POST" id="fruitkha-contact" action="mail.php">
							<p>
								<input type="text" placeholder="Name" name="name" id="name" required>
								<input type="email" placeholder="Email" name="email" id="email" required>
							</p>
							<p>
								<input type="tel" placeholder="Phone" name="phone" id="phone">
								<input type="text" placeholder="Subject" name="subject" id="subject" required>
							</p>
							<p><textarea name="message" id="message" cols="30" rows="10" placeholder="Message or Special Dietary Request" required></textarea></p>
							<input type="hidden" name="token" value="FsWga4&@f6aw" />
							<p><input type="submit" value="Submit Message" class="boxed-btn" style="background:#F28123; border:none; border-radius:30px;"></p>
						</form>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="contact-form-wrap">
						<div class="contact-form-box">
							<h4><i class="fas fa-map"></i> Canteen Address</h4>
							<p>2/A, Arambagh, Motijheel<br> Dhaka-1000 <br> Bangladesh</p>
						</div>
						<div class="contact-form-box">
							<h4><i class="far fa-clock"></i> Service Hours</h4>
							<p>MON - FRIDAY: 8:00 AM to 8:00 PM <br> SATURDAY: 9:00 AM to 5:00 PM </p>
						</div>
						<div class="contact-form-box">
							<h4><i class="fas fa-address-book"></i> Direct Support</h4>
							<p>Phone: +880 0140612465 <br> Email: ndub.canteen@gmail.com</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end contact form -->

<?php include __DIR__ . '/includes/footer.php'; ?>
