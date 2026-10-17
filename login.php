<?php
// login.php - Preserved Sign-In / Sign-Up Sliding Interface with Allergen Profile Setup
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Login & Sign Up - Smart Canteen Management System';
$currentPage = 'login';
$returnUrl = $_GET['return'] ?? '';

// If already logged in, redirect
if (is_logged_in()) {
    $prof = get_current_user_profile();
    if (!$prof['allergen_profile_completed']) {
        header("Location: profile.php?setup=1" . ($returnUrl ? '&return=' . urlencode($returnUrl) : ''));
    } else {
        header("Location: " . ($returnUrl ?: 'index.php'));
    }
    exit;
}

$errorMessage = $_SESSION['auth_error'] ?? '';
$successMessage = $_SESSION['auth_success'] ?? '';
unset($_SESSION['auth_error'], $_SESSION['auth_success']);

// Fetch all allergens for the modal
$pdo = get_db_connection();
$stmtAllergens = $pdo->query("SELECT id, name, slug FROM allergens ORDER BY id ASC");
$allAllergens = $stmtAllergens->fetchAll();

include __DIR__ . '/includes/header.php';
?>

    <!-- Additional custom styles for login/signup and allergen chart (Preserved from login.html) -->
    <style>
    .cont {
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        width: 900px;
        max-width: 95%;
        height: 580px;
        margin: 40px auto 100px;
        background: #fff;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .form {
        position: relative;
        width: 640px;
        max-width: 100%;
        height: 100%;
        -webkit-transition: -webkit-transform 1.2s ease-in-out;
        transition: -webkit-transform 1.2s ease-in-out;
        transition: transform 1.2s ease-in-out;
        padding: 50px 30px 0;
    }

    .sub-cont {
        overflow: hidden;
        position: absolute;
        left: 640px;
        top: 0;
        width: 900px;
        height: 100%;
        padding-left: 260px;
        background: #fff;
        -webkit-transition: -webkit-transform 1.2s ease-in-out;
        transition: -webkit-transform 1.2s ease-in-out;
        transition: transform 1.2s ease-in-out;
    }

    .cont.s--signup .sub-cont {
        -webkit-transform: translate3d(-640px, 0, 0);
        transform: translate3d(-640px, 0, 0);
    }

    .form-btn {
        display: block;
        margin: 25px auto 0;
        width: 260px;
        height: 40px;
        border-radius: 30px;
        color: #fff;
        font-size: 15px;
        cursor: pointer;
        background: #F28123;
        font-weight: 600;
        border: none;
        transition: background 0.3s;
    }
    .form-btn:hover {
        background: #051922;
        color: #fff;
    }

    .img {
        overflow: hidden;
        z-index: 2;
        position: absolute;
        left: 0;
        top: 0;
        width: 260px;
        height: 100%;
        padding-top: 360px;
    }

    .img:before {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        width: 900px;
        height: 100%;
        background-image: url("assets/img/hero-bg.jpg");
        opacity: .85;
        background-size: cover;
        -webkit-transition: -webkit-transform 1.2s ease-in-out;
        transition: transform 1.2s ease-in-out;
    }

    .img:after {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(5, 25, 34, 0.7);
    }

    .cont.s--signup .img:before {
        -webkit-transform: translate3d(640px, 0, 0);
        transform: translate3d(640px, 0, 0);
    }

    .img__text {
        z-index: 2;
        position: absolute;
        left: 0;
        top: 50px;
        width: 100%;
        padding: 0 20px;
        text-align: center;
        color: #fff;
        -webkit-transition: -webkit-transform 1.2s ease-in-out;
        transition: transform 1.2s ease-in-out;
    }

    .img__text h3 {
        color: #fff;
        margin-bottom: 10px;
        font-size: 20px;
    }

    .cont.s--signup .img__text.m--up {
        -webkit-transform: translateX(520px);
        transform: translateX(520px);
    }

    .img__text.m--in {
        -webkit-transform: translateX(-520px);
        transform: translateX(-520px);
    }

    .cont.s--signup .img__text.m--in {
        -webkit-transform: translateX(0);
        transform: translateX(0);
    }

    .img__btn {
        overflow: hidden;
        z-index: 2;
        position: relative;
        width: 120px;
        height: 38px;
        margin: 0 auto;
        background: transparent;
        color: #fff;
        text-transform: uppercase;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        border: 2px solid #fff;
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .img__btn span {
        position: absolute;
        left: 0;
        top: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100%;
        -webkit-transition: -webkit-transform 1.2s;
        transition: transform 1.2s;
    }

    .img__btn span.m--in {
        -webkit-transform: translateY(-72px);
        transform: translateY(-72px);
    }

    .cont.s--signup .img__btn span.m--in {
        -webkit-transform: translateY(0);
        transform: translateY(0);
    }

    .cont.s--signup .img__btn span.m--up {
        -webkit-transform: translateY(72px);
        transform: translateY(72px);
    }

    .form h2 {
        width: 100%;
        font-size: 26px;
        text-align: center;
        color: #051922;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .form label {
        display: block;
        width: 320px;
        max-width: 100%;
        margin: 18px auto 0;
        text-align: left;
    }

    .form label span {
        font-size: 13px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }

    .form input {
        display: block;
        width: 100%;
        padding: 8px 12px;
        font-size: 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #fafafa;
    }
    .form input:focus {
        border-color: #F28123;
        outline: none;
        background: #fff;
    }

    .sign-in {
        -webkit-transition-timing-function: ease-out;
        transition-timing-function: ease-out;
    }

    .cont.s--signup .sign-in {
        -webkit-transition-timing-function: ease-in-out;
        transition-timing-function: ease-in-out;
        -webkit-transition-duration: 1.2s;
        transition-duration: 1.2s;
        -webkit-transform: translate3d(640px, 0, 0);
        transform: translate3d(640px, 0, 0);
    }

    .sign-up {
        -webkit-transform: translate3d(-900px, 0, 0);
        transform: translate3d(-900px, 0, 0);
    }

    .cont.s--signup .sign-up {
        -webkit-transform: translate3d(0, 0, 0);
        transform: translate3d(0, 0, 0);
    }

    /* Modal styles for Allergen Chart */
    #allergenModalCustom {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        align-items: center;
        justify-content: center;
        z-index: 99999;
    }

    #allergenModalCustom .modal-content-box {
        background: #fff;
        padding: 30px;
        border-radius: 16px;
        width: 650px;
        max-width: 92%;
        text-align: center;
        position: relative;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .allergen-grid-box {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin: 20px 0;
        text-align: left;
    }

    .allergen-checkbox-card {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        padding: 10px 14px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    .allergen-checkbox-card:hover {
        background: #fff5ee;
        border-color: #ffd591;
    }
    .allergen-checkbox-card input {
        margin-right: 10px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    </style>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Secure Student & Staff Access</p>
						<h1>Sign In / Register</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

    <div class="container mt-5">
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger mx-auto text-center font-weight-bold" style="max-width: 600px; border-radius: 8px;">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success mx-auto text-center font-weight-bold" style="max-width: 600px; border-radius: 8px;">
                <i class="fas fa-check-circle mr-2"></i> <?php echo e($successMessage); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Sliding Container -->
    <div class="cont">
        <!-- 1. Sign In Form -->
        <div class="form sign-in">
            <h2>Welcome Back</h2>
            <form action="api/auth.php" method="POST" id="signInForm">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="return_url" value="<?php echo e($returnUrl); ?>">

                <label>
                    <span>Email Address</span>
                    <input type="email" name="email" required placeholder="alex@example.com" />
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required placeholder="••••••••" />
                </label>
                
                <button type="submit" class="form-btn">
                    <i class="fas fa-sign-in-alt mr-1"></i> Sign In
                </button>
            </form>

            <div class="text-center mt-3 small text-muted">
                Demo Accounts:<br>
                <strong>test@example.com</strong> / <strong>password123</strong> (Profile Completed: Dairy, Peanuts)<br>
                <strong>newuser@example.com</strong> / <strong>password123</strong> (Profile Incomplete: First-Time User)
            </div>
        </div>

        <!-- Sliding Overlay & Sign Up Form -->
        <div class="sub-cont">
            <div class="img">
                <div class="img__text m--up">
                    <h3>Don't have an account?</h3>
                    <p>Create an account to protect against allergens and order seamlessly.</p>
                </div>
                <div class="img__text m--in">
                    <h3>Already registered?</h3>
                    <p>Sign in to access your customized allergen preferences and cart.</p>
                </div>
                <div class="img__btn">
                    <span class="m--up">Sign Up</span>
                    <span class="m--in">Sign In</span>
                </div>
            </div>

            <!-- 2. Sign Up Form -->
            <div class="form sign-up">
                <h2>Create Account</h2>
                <form action="api/auth.php" method="POST" id="signUpForm">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="return_url" value="<?php echo e($returnUrl); ?>">
                    <input type="hidden" name="allergen_setup_completed" id="allergenSetupCompletedInput" value="0">

                    <label>
                        <span>Full Name</span>
                        <input type="text" name="name" id="regName" required placeholder="Your full name" />
                    </label>
                    <label>
                        <span>Email Address</span>
                        <input type="email" name="email" id="regEmail" required placeholder="your.email@university.edu" />
                    </label>
                    <label>
                        <span>Password (min 6 characters)</span>
                        <input type="password" name="password" id="regPassword" required placeholder="Create a password" />
                    </label>
                    
                    <button type="button" class="form-btn" id="btnOpenAllergenModal">
                        Next: Allergen Profile <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Box for Allergen Selection during Sign Up (Preserved & Enhanced) -->
    <div id="allergenModalCustom">
        <div class="modal-content-box">
            <span class="close" id="btnCloseAllergenModal" style="position:absolute; right:18px; top:12px; font-size:28px; cursor:pointer;">&times;</span>
            <h3 class="font-weight-bold" style="color:#051922;"><i class="fas fa-shield-alt text-warning mr-2"></i> Allergen Profile Setup</h3>
            <p class="text-muted small">Select any dietary allergens you want the canteen system to warn you against:</p>
            
            <div class="allergen-grid-box">
                <?php foreach ($allAllergens as $alg): ?>
                    <label class="allergen-checkbox-card" for="modal_alg_<?php echo $alg['id']; ?>">
                        <input type="checkbox" id="modal_alg_<?php echo $alg['id']; ?>" class="reg-allergen-cb" value="<?php echo $alg['id']; ?>">
                        <span class="font-weight-bold" style="font-size:14px;"><?php echo e($alg['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="button" class="btn btn-light" id="btnSkipAllergens" style="border-radius:20px;">
                    I have no known allergies
                </button>
                <button type="button" class="form-btn my-0" id="btnFinishRegistration" style="width: auto; padding: 0 25px;">
                    <i class="fas fa-check mr-1"></i> Save & Complete Registration
                </button>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Sliding toggle
    const imgBtn = document.querySelector('.img__btn');
    if (imgBtn) {
        imgBtn.addEventListener('click', function() {
            document.querySelector('.cont').classList.toggle('s--signup');
        });
    }

    // 2. Allergen Modal controls
    const allergenModal = document.getElementById('allergenModalCustom');
    const btnOpenModal = document.getElementById('btnOpenAllergenModal');
    const btnCloseModal = document.getElementById('btnCloseAllergenModal');
    const btnSkip = document.getElementById('btnSkipAllergens');
    const btnFinish = document.getElementById('btnFinishRegistration');
    const signUpForm = document.getElementById('signUpForm');

    function openModal() {
        // Validate registration fields first
        const name = document.getElementById('regName').value.trim();
        const email = document.getElementById('regEmail').value.trim();
        const pass = document.getElementById('regPassword').value;

        if (!name || !email || !pass) {
            alert('Please fill in your Name, Email, and Password before proceeding.');
            return;
        }
        if (pass.length < 6) {
            alert('Password must be at least 6 characters.');
            return;
        }

        allergenModal.style.display = 'flex';
    }

    function closeModal() {
        allergenModal.style.display = 'none';
    }

    if (btnOpenModal) btnOpenModal.addEventListener('click', openModal);
    if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);

    // Save allergens & submit registration
    function submitWithAllergens(isCompleted) {
        document.getElementById('allergenSetupCompletedInput').value = isCompleted ? '1' : '0';

        // Remove old dynamic checkboxes if any
        signUpForm.querySelectorAll('.hidden-alg-input').forEach(e => e.remove());

        // Append checked allergens as hidden inputs into signUpForm
        document.querySelectorAll('.reg-allergen-cb:checked').forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'allergens[]';
            input.value = cb.value;
            input.className = 'hidden-alg-input';
            signUpForm.appendChild(input);
        });

        signUpForm.submit();
    }

    if (btnFinish) {
        btnFinish.addEventListener('click', function() {
            submitWithAllergens(true);
        });
    }

    if (btnSkip) {
        btnSkip.addEventListener('click', function() {
            // Uncheck all and mark completed as 1 (user explicitly confirmed 0 allergens)
            document.querySelectorAll('.reg-allergen-cb').forEach(cb => cb.checked = false);
            submitWithAllergens(true);
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
