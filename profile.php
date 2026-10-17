<?php
// profile.php - User Allergen Profile Interface & Account Settings
require_once __DIR__ . '/config/db.php';

$pageTitle = 'My Allergen Profile - Smart Canteen Management System';
$currentPage = 'profile';

// Require authenticated user
require_login('profile.php');

$pdo = get_db_connection();
$userId = $_SESSION['user_id'];
$isFirstTimeSetup = !empty($_GET['setup']);
$returnUrl = $_GET['return'] ?? '';

// Fetch all available allergens from DB
$stmtAll = $pdo->query("SELECT id, name, slug, description, icon FROM allergens ORDER BY id ASC");
$allAllergens = $stmtAll->fetchAll();

// Fetch currently saved user allergens
$stmtUser = $pdo->prepare("SELECT allergen_id FROM user_allergens WHERE user_id = ?");
$stmtUser->execute([$userId]);
$savedAllergenIds = $stmtUser->fetchAll(PDO::FETCH_COLUMN);

// Fetch user info
$userProfile = get_current_user_profile();

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Personalized Dietary Safety</p>
						<h1>My Allergen Profile</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- Allergen Profile Section -->
	<div class="mt-100 mb-150">
		<div class="container">

            <?php if ($isFirstTimeSetup || !$userProfile['allergen_profile_completed']): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-warning p-4 shadow-sm" style="border-radius: 12px; border-left: 6px solid #F28123;">
                            <h4 class="alert-heading font-weight-bold mb-2">
                                <i class="fas fa-hand-paper mr-2 text-warning"></i> Initial Allergen Safety Setup Required
                            </h4>
                            <p class="mb-0">
                                Welcome, <strong><?php echo e($userProfile['name']); ?></strong>! Before ordering meals, please configure your dietary allergens below. This ensures our kitchen automatically monitors your recipes and alerts you if any dishes contain conflicting ingredients.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

			<div class="row">
                <!-- Left: Allergen Chart Selection -->
				<div class="col-lg-8">
					<div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 16px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h3 class="font-weight-bold mb-1" style="color: #051922;">Select Your Known Allergens</h3>
                                <p class="text-muted small mb-0">Check all allergens that trigger a reaction. If you have no allergies, you can uncheck all and save.</p>
                            </div>
                            <span class="badge badge-primary px-3 py-2" style="background:#051922; border-radius: 20px;">
                                <i class="fas fa-database mr-1"></i> Live Database Profile
                            </span>
                        </div>

                        <form id="allergenProfileForm">
                            <div class="row mt-3">
                                <?php foreach ($allAllergens as $alg): ?>
                                    <?php $isChecked = in_array((int)$alg['id'], $savedAllergenIds, true); ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="allergen-card d-flex align-items-center p-3 border rounded shadow-sm h-100" 
                                               style="cursor: pointer; transition: all 0.2s; background: <?php echo $isChecked ? '#fff7ed' : '#ffffff'; ?>; border-color: <?php echo $isChecked ? '#F28123' : '#e9ecef'; ?> !important;">
                                            <input type="checkbox" name="allergens[]" value="<?php echo $alg['id']; ?>" 
                                                   class="allergen-cb mr-3" style="width: 20px; height: 20px; cursor: pointer;"
                                                   <?php echo $isChecked ? 'checked' : ''; ?>>
                                            <div>
                                                <div class="font-weight-bold" style="color: #051922; font-size: 15px;">
                                                    <i class="<?php echo e($alg['icon'] ?: 'fas fa-exclamation-circle'); ?> mr-2 text-warning"></i>
                                                    <?php echo e($alg['name']); ?>
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    <?php echo e($alg['description']); ?>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-secondary" id="btnClearAllergenChecks" style="border-radius: 25px;">
                                    <i class="fas fa-undo mr-1"></i> Clear All (No Allergies)
                                </button>
                                <button type="submit" class="boxed-btn black border-0" id="btnSaveProfile" style="background: #F28123; border-radius: 30px; font-weight: 700; cursor: pointer; padding: 12px 30px;">
                                    <i class="fas fa-save mr-2"></i> Save Allergen Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Profile Overview & Quick Actions -->
                <div class="col-lg-4">
                    <!-- User Details Summary -->
                    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 16px;">
                        <h4 class="font-weight-bold mb-3" style="color: #051922;">
                            <i class="fas fa-user-circle mr-2 text-warning"></i> Account Details
                        </h4>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Student / Staff Name:</span>
                            <strong><?php echo e($userProfile['name']); ?></strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Email Address:</span>
                            <span><?php echo e($userProfile['email']); ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Campus Delivery Location:</span>
                            <span><?php echo e($userProfile['address'] ?: 'Not set yet'); ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small d-block">Profile Status:</span>
                            <?php if ($userProfile['allergen_profile_completed']): ?>
                                <span class="badge badge-success px-3 py-2" style="border-radius: 12px;">
                                    <i class="fas fa-check-circle mr-1"></i> Active & Protected
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger px-3 py-2" style="border-radius: 12px;">
                                    <i class="fas fa-clock mr-1"></i> Setup Pending
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Saved Profile Summary Preview -->
                    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 16px;">
                        <h4 class="font-weight-bold mb-3" style="color: #051922;">
                            <i class="fas fa-shield-alt mr-2 text-warning"></i> Active Protection
                        </h4>
                        <p class="small text-muted">Dishes containing ingredients linked to these categories will trigger safety popups on selection:</p>
                        
                        <div id="activeAllergenBadgesContainer">
                            <?php if (empty($savedAllergenIds)): ?>
                                <p class="text-muted small font-italic mb-0" id="noAllergensNotice">
                                    No allergens selected. You can order any menu items without ingredient alerts.
                                </p>
                            <?php else: ?>
                                <?php foreach ($allAllergens as $alg): ?>
                                    <?php if (in_array((int)$alg['id'], $savedAllergenIds, true)): ?>
                                        <span class="allergen-tag mb-2 d-inline-block">
                                            <i class="fas fa-exclamation-triangle"></i> <?php echo e($alg['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <a href="<?php echo e(!empty($returnUrl) ? $returnUrl : 'shop.php'); ?>" class="boxed-btn black d-block text-center py-3" style="border-radius: 30px; font-weight: 700; background: #051922;">
                                Continue to Menu <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
			</div>
		</div>
	</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('allergenProfileForm');
    const saveBtn = document.getElementById('btnSaveProfile');
    const clearBtn = document.getElementById('btnClearAllergenChecks');
    const checkboxes = document.querySelectorAll('.allergen-cb');

    // Visual card state toggle on checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const card = this.closest('.allergen-card');
            if (this.checked) {
                card.style.background = '#fff7ed';
                card.style.borderColor = '#F28123';
            } else {
                card.style.background = '#ffffff';
                card.style.borderColor = '#e9ecef';
            }
        });
    });

    // Clear all button
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => {
                cb.checked = false;
                const card = cb.closest('.allergen-card');
                card.style.background = '#ffffff';
                card.style.borderColor = '#e9ecef';
            });
        });
    }

    // Submit Allergen Profile via AJAX
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving to Database...';

            const selected = [];
            document.querySelectorAll('.allergen-cb:checked').forEach(cb => {
                selected.push(parseInt(cb.value, 10));
            });

            fetch('api/allergens.php?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ allergens: selected })
            })
            .then(r => r.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Allergen Preferences';

                if (data.success) {
                    showToast('Allergen profile saved successfully to database!', 'success');
                    
                    // Update preview badges
                    const container = document.getElementById('activeAllergenBadgesContainer');
                    container.innerHTML = '';
                    if (data.allergens && data.allergens.length > 0) {
                        data.allergens.forEach(a => {
                            container.innerHTML += `<span class="allergen-tag mb-2 d-inline-block"><i class="fas fa-exclamation-triangle"></i> ${a.name}</span> `;
                        });
                    } else {
                        container.innerHTML = '<p class="text-muted small font-italic mb-0">No allergens selected. You can order any menu items without ingredient alerts.</p>';
                    }

                    // If user was completing first-time setup with return URL, redirect them smoothly
                    const returnUrl = <?php echo json_encode($returnUrl); ?>;
                    if (returnUrl) {
                        setTimeout(() => { window.location.href = returnUrl; }, 1200);
                    }
                } else {
                    showToast(data.message || 'Error saving allergen preferences.', 'danger');
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Allergen Preferences';
                showToast('Failed to save profile. Please check database connection.', 'danger');
            });
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
