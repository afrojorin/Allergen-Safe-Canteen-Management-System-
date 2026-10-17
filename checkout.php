<?php
// checkout.php - Integrated Checkout Page with Ingredient Removal Requests and Allergen Guard
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Checkout - Smart Canteen Management System';
$currentPage = 'checkout';
$cartData = get_active_cart();
$currentUser = current_user();
$userProfile = get_current_user_profile();

// If user is not logged in, we preserve intent and let them login or sign up
$idempotencyToken = bin2hex(random_bytes(16));

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Fresh, Safe & Custom Prepared</p>
						<h1>Check Out Order</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- check out section -->
	<div class="checkout-section mt-100 mb-150">
		<div class="container">
            <?php if (empty($cartData['items'])): ?>
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <div class="p-5 bg-white shadow-sm" style="border-radius: 16px;">
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3" style="color: #F28123 !important;"></i>
                            <h3 class="font-weight-bold" style="color: #051922;">Your Active Cart is Empty</h3>
                            <p class="text-muted">You cannot proceed to checkout without any food items in your order.</p>
                            <a href="shop.php" class="boxed-btn mt-3" style="background: #F28123;">
                                <i class="fas fa-utensils mr-2"></i> Browse Food Menu
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>

                <!-- Guest Login Alert if not signed in -->
                <?php if (!$currentUser): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-warning p-4 shadow-sm" style="border-radius: 12px; border-left: 5px solid #F28123;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <h5 class="alert-heading font-weight-bold mb-1"><i class="fas fa-lock mr-2"></i> User Authentication Required</h5>
                                    <p class="mb-0">Please sign in or create an account to finalize your order and verify allergen protection.</p>
                                </div>
                                <div class="mt-2 mt-md-0">
                                    <a href="login.php?return=checkout.php" class="btn btn-dark px-4 py-2 font-weight-bold" style="border-radius: 25px;">
                                        Sign In / Register <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form id="orderCheckoutForm" action="api/order.php" method="POST">
                    <input type="hidden" name="idempotency_token" value="<?php echo e($idempotencyToken); ?>">

                    <div class="row">
                        <!-- Left Column: Customer Details & Ingredient Modifications -->
                        <div class="col-lg-7">
                            <div class="checkout-accordion-wrap">
                                <div class="accordion" id="accordionExample">
                                    
                                    <!-- 1. Customer Details -->
                                    <div class="card single-accordion mb-3 shadow-sm border-0" style="border-radius: 10px; overflow: hidden;">
                                        <div class="card-header bg-white py-3" id="headingOne">
                                            <h5 class="mb-0">
                                                <button class="btn btn-link font-weight-bold text-dark d-flex align-items-center" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="text-decoration:none;">
                                                    <i class="fas fa-id-card mr-2 text-warning"></i> Customer & Pickup Information
                                                </button>
                                            </h5>
                                        </div>

                                        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
                                            <div class="card-body p-4 bg-light">
                                                <div class="billing-address-form">
                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold small text-muted">Full Name *</label>
                                                        <input type="text" name="name" class="form-control" placeholder="Your Name" required value="<?php echo e($userProfile['name'] ?? ''); ?>" style="border-radius: 8px;">
                                                    </div>
                                                    <div class="form-row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="font-weight-bold small text-muted">Email Address *</label>
                                                            <input type="email" name="email" class="form-control" placeholder="Email" required value="<?php echo e($userProfile['email'] ?? ''); ?>" style="border-radius: 8px;">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="font-weight-bold small text-muted">Phone Number *</label>
                                                            <input type="tel" name="phone" class="form-control" placeholder="+880 1711..." required value="<?php echo e($userProfile['phone'] ?? ''); ?>" style="border-radius: 8px;">
                                                        </div>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold small text-muted">Campus Hall / Department / Room *</label>
                                                        <input type="text" name="address" class="form-control" placeholder="e.g. Science Building 2nd Floor or Room 204" required value="<?php echo e($userProfile['address'] ?? ''); ?>" style="border-radius: 8px;">
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold small text-muted">Preferred Pickup Time</label>
                                                        <select name="pickup_time" class="form-control" style="border-radius: 8px;">
                                                            <option value="12:00 PM">12:00 PM (Lunch Rush A)</option>
                                                            <option value="12:30 PM" selected>12:30 PM (Standard Lunch)</option>
                                                            <option value="01:00 PM">01:00 PM (Lunch Rush B)</option>
                                                            <option value="01:30 PM">01:30 PM</option>
                                                            <option value="02:00 PM">02:00 PM (Afternoon Pick)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Ingredient Removal & Kitchen Preparation Requests -->
                                    <div class="card single-accordion mb-3 shadow-sm border-0" style="border-radius: 10px; overflow: hidden;">
                                        <div class="card-header bg-white py-3" id="headingTwo">
                                            <h5 class="mb-0">
                                                <button class="btn btn-link font-weight-bold text-dark d-flex align-items-center" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo" style="text-decoration:none;">
                                                    <i class="fas fa-utensils mr-2 text-warning"></i> Recipe Customization & Ingredient Removals
                                                </button>
                                            </h5>
                                        </div>
                                        <div id="collapseTwo" class="collapse show" aria-labelledby="headingTwo" data-parent="#accordionExample">
                                            <div class="card-body p-4 bg-light">
                                                
                                                <div class="alert alert-warning small mb-4" style="border-radius: 8px;">
                                                    <i class="fas fa-exclamation-triangle text-danger mr-1"></i>
                                                    <strong>Important Kitchen Policy:</strong> Specifying an ingredient removal is a kitchen preparation request, not a medical safety guarantee. While our canteen staff will make every effort to exclude requested ingredients, shared kitchen equipment and blended sauces may still carry cross-contact risks.
                                                </div>

                                                <?php foreach ($cartData['items'] as $cItem): ?>
                                                    <div class="card mb-3 border-0 shadow-sm" style="border-radius: 8px; border-left: 4px solid #F28123 !important;">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <img src="<?php echo e($cItem['image']); ?>" alt="<?php echo e($cItem['food_name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;" class="mr-3">
                                                                <div>
                                                                    <h6 class="font-weight-bold mb-0" style="color: #051922;"><?php echo e($cItem['food_name']); ?> (Qty: <?php echo $cItem['quantity']; ?>)</h6>
                                                                    <?php if ($cItem['allergen_warning']): ?>
                                                                        <span class="badge badge-danger">
                                                                            <i class="fas fa-exclamation-triangle"></i> Allergen Alert: <?php echo e(implode(', ', $cItem['allergen_details'])); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Removable Ingredients Checkboxes -->
                                                            <div class="mt-2 pt-2 border-top">
                                                                <label class="small font-weight-bold text-muted mb-1 d-block">
                                                                    Select ingredients to REMOVE from this item:
                                                                </label>
                                                                <?php if (!empty($cItem['ingredients'])): ?>
                                                                    <div class="d-flex flex-wrap">
                                                                        <?php foreach ($cItem['ingredients'] as $ing): ?>
                                                                            <div class="custom-control custom-checkbox mr-3 mb-2">
                                                                                <input type="checkbox" 
                                                                                       class="custom-control-input" 
                                                                                       id="rem_<?php echo $cItem['food_id']; ?>_<?php echo $ing['id']; ?>" 
                                                                                       name="item_modifications[<?php echo $cItem['food_id']; ?>][removals][]" 
                                                                                       value="<?php echo e($ing['name']); ?>">
                                                                                <label class="custom-control-label small" for="rem_<?php echo $cItem['food_id']; ?>_<?php echo $ing['id']; ?>">
                                                                                    Remove <?php echo e($ing['name']); ?>
                                                                                    <?php if (!$ing['is_optional_removable']): ?>
                                                                                        <span class="text-muted font-italic">(core ingredient)</span>
                                                                                    <?php endif; ?>
                                                                                </label>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <p class="small text-muted mb-1 font-italic">No specific separable ingredients listed for this item.</p>
                                                                <?php endif; ?>

                                                                <input type="text" 
                                                                       class="form-control form-control-sm mt-1" 
                                                                       name="item_modifications[<?php echo $cItem['food_id']; ?>][notes]" 
                                                                       placeholder="Special preparation request (e.g. less spicy, sauce on the side)..." 
                                                                       style="border-radius: 6px;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <div class="form-group mt-3 mb-0">
                                                    <label class="font-weight-bold small text-muted">Additional Order Notes</label>
                                                    <textarea name="notes" rows="2" class="form-control" placeholder="Any additional packaging or delivery instructions..." style="border-radius: 8px;"></textarea>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Order Details Summary & Confirmation -->
                        <div class="col-lg-5">
                            <div class="order-details-wrap shadow-sm bg-white p-4" style="border-radius: 12px;">
                                <h4 class="font-weight-bold mb-3" style="color: #051922;">
                                    <i class="fas fa-receipt mr-2 text-warning"></i> Order Summary
                                </h4>
                                
                                <table class="order-details w-100">
                                    <thead>
                                        <tr class="border-bottom">
                                            <th class="py-2" style="color:#888; font-size:13px; text-transform:uppercase;">Product</th>
                                            <th class="py-2 text-right" style="color:#888; font-size:13px; text-transform:uppercase;">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="order-details-body">
                                        <?php foreach ($cartData['items'] as $item): ?>
                                            <tr class="border-bottom">
                                                <td class="py-3">
                                                    <div class="font-weight-bold" style="color:#051922;">
                                                        <?php echo e($item['food_name']); ?> &times; <?php echo $item['quantity']; ?>
                                                    </div>
                                                    <div class="small text-muted">
                                                        Unit price: <?php echo number_format($item['price'], 0); ?>tk
                                                    </div>
                                                    <?php if ($item['allergen_warning']): ?>
                                                        <div class="mt-1">
                                                            <span class="badge badge-warning text-dark small" style="font-size:10px;">
                                                                <i class="fas fa-exclamation-triangle"></i> Allergen Warning Acknowledged
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 text-right font-weight-bold" style="color:#051922;">
                                                    <?php echo number_format($item['line_total'], 0); ?>tk
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tbody class="checkout-details">
                                        <tr>
                                            <td class="py-2 text-muted">Total Food Units:</td>
                                            <td class="py-2 text-right font-weight-bold"><?php echo $cartData['total_units']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 text-muted">Subtotal:</td>
                                            <td class="py-2 text-right font-weight-bold"><?php echo number_format($cartData['subtotal'], 0); ?>tk</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 text-muted">Service Tax (0%):</td>
                                            <td class="py-2 text-right text-muted">0tk</td>
                                        </tr>
                                        <tr class="border-top" style="border-top: 2px solid #ddd !important;">
                                            <td class="py-3 font-weight-bold" style="font-size: 18px; color: #051922;">Final Total:</td>
                                            <td class="py-3 text-right font-weight-bold" style="font-size: 22px; color: #F28123;">
                                                <?php echo number_format($cartData['total'], 0); ?>tk
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="mt-4">
                                    <?php if ($currentUser): ?>
                                        <button type="submit" id="btnPlaceOrder" class="boxed-btn black w-100 text-center py-3 border-0" style="border-radius: 30px; font-weight: 700; background: #F28123; cursor: pointer;">
                                            <i class="fas fa-check-circle mr-2"></i> Confirm & Place Order
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php?return=checkout.php" class="boxed-btn black w-100 text-center py-3 d-block" style="border-radius: 30px; font-weight: 700; background: #051922;">
                                            <i class="fas fa-sign-in-alt mr-2"></i> Sign In to Place Order
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="text-center mt-3">
                                    <span class="small text-muted"><i class="fas fa-shield-alt text-success mr-1"></i> Secure Database-Backed Transaction</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
		</div>
	</div>
	<!-- end check out section -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('orderCheckoutForm');
    const submitBtn = document.getElementById('btnPlaceOrder');

    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Placing Order...';

            const formData = new FormData(form);

            fetch('api/order.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || ('order-confirmation.php?code=' + encodeURIComponent(data.order_code));
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirm & Place Order';
                    alert(data.message || 'Error processing order.');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirm & Place Order';
                alert('A network error occurred while submitting your order. Your active cart was not lost.');
            });
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
