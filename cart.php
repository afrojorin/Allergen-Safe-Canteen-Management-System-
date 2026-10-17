<?php
// cart.php - Synchronized Cart Page
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Cart - Smart Canteen Management System';
$currentPage = 'cart';
$cartData = get_active_cart();

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Every Meal Freshly Prepared</p>
						<h1>Your Active Cart</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- cart -->
	<div class="cart-section mt-100 mb-150">
		<div class="container">
			<div class="row" id="cartContentWrapper">
                <?php if (empty($cartData['items'])): ?>
                    <div class="col-12 text-center py-5">
                        <div class="p-5 bg-white shadow-sm" style="border-radius: 16px;">
                            <i class="fas fa-shopping-basket fa-4x text-muted mb-3" style="color: #ffbb96 !important;"></i>
                            <h3 class="font-weight-bold" style="color: #051922;">Your Cart is Empty</h3>
                            <p class="text-muted">You haven't added any items to your order yet. Explore our delicious menu lineup!</p>
                            <a href="shop.php" class="boxed-btn mt-3" style="background: #F28123;">
                                <i class="fas fa-utensils mr-2"></i> Browse Food Menu
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="col-lg-8 col-md-12">
                        <div class="cart-table-wrap shadow-sm bg-white" style="border-radius: 12px; overflow: hidden;">
                            <table class="cart-table">
                                <thead class="cart-table-head">
                                    <tr class="table-head-row">
                                        <th class="product-remove"></th>
                                        <th class="product-image">Product Image</th>
                                        <th class="product-name">Name</th>
                                        <th class="product-price">Price</th>
                                        <th class="product-quantity">Quantity</th>
                                        <th class="product-total">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartData['items'] as $item): ?>
                                    <tr class="table-body-row" id="cartRow_<?php echo e($item['cart_item_id']); ?>">
                                        <td class="product-remove">
                                            <a href="#" class="btn-remove-cart-item text-danger" data-cart-item-id="<?php echo e($item['cart_item_id']); ?>" data-food-id="<?php echo $item['food_id']; ?>" title="Remove item">
                                                <i class="far fa-window-close fa-lg"></i>
                                            </a>
                                        </td>
                                        <td class="product-image">
                                            <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['food_name']); ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
                                        </td>
                                        <td class="product-name text-left">
                                            <span class="font-weight-bold" style="color:#051922;"><?php echo e($item['food_name']); ?></span>
                                            
                                            <?php if ($item['allergen_warning']): ?>
                                                <div class="mt-1">
                                                    <span class="allergen-warning-badge" title="Allergen warning acknowledged by user">
                                                        <i class="fas fa-exclamation-triangle"></i> Allergen Caution: 
                                                        <?php echo e(implode(', ', $item['allergen_details'])); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!$item['ingredients_verified']): ?>
                                                <div class="mt-1">
                                                    <span class="badge badge-warning text-dark">
                                                        <i class="fas fa-info-circle"></i> Unverified Ingredients
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="product-price"><?php echo number_format($item['price'], 0); ?>tk</td>
                                        <td class="product-quantity">
                                            <input type="number" class="cart-qty-input form-control text-center mx-auto" 
                                                   style="width: 75px; border-radius: 8px;" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="99" 
                                                   data-cart-item-id="<?php echo e($item['cart_item_id']); ?>" 
                                                   data-food-id="<?php echo $item['food_id']; ?>">
                                        </td>
                                        <td class="product-total font-weight-bold" style="color: #F28123;">
                                            <?php echo number_format($item['line_total'], 0); ?>tk
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="shop.php" class="btn btn-outline-dark" style="border-radius: 25px;">
                                <i class="fas fa-arrow-left mr-1"></i> Continue Adding Food
                            </a>
                            <button type="button" class="btn btn-outline-danger" id="btnClearCart" style="border-radius: 25px;">
                                <i class="fas fa-trash-alt mr-1"></i> Clear Entire Cart
                            </button>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="total-section shadow-sm bg-white" style="border-radius: 12px; padding: 25px;">
                            <table class="total-table w-100">
                                <thead class="total-table-head">
                                    <tr class="table-total-row">
                                        <th style="font-size: 18px; color: #051922;">Order Summary</th>
                                        <th style="font-size: 18px; color: #051922;">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="total-data">
                                        <td><strong>Total Food Units:</strong></td>
                                        <td class="font-weight-bold"><?php echo $cartData['total_units']; ?> items</td>
                                    </tr>
                                    <tr class="total-data">
                                        <td><strong>Subtotal: </strong></td>
                                        <td><?php echo number_format($cartData['subtotal'], 0); ?>tk</td>
                                    </tr>
                                    <tr class="total-data">
                                        <td><strong>Estimated Pickup: </strong></td>
                                        <td class="text-success font-weight-bold">12:30 PM</td>
                                    </tr>
                                    <tr class="total-data" style="border-top: 2px solid #f1f1f1;">
                                        <td style="font-size: 18px;"><strong>Final Total: </strong></td>
                                        <td style="font-size: 20px; color: #F28123; font-weight: 700;">
                                            <?php echo number_format($cartData['total'], 0); ?>tk
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="cart-buttons mt-4">
                                <a href="checkout.php" class="boxed-btn black d-block text-center py-3" style="border-radius: 30px; font-weight: 700; background: #F28123; border-color: #F28123;">
                                    Proceed to Checkout <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
			</div>
		</div>
	</div>
	<!-- end cart -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnClear = document.getElementById('btnClearCart');
    if (btnClear) {
        btnClear.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all items from your cart?')) {
                fetch('api/cart.php?action=clear', { method: 'POST' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            location.reload();
                        }
                    });
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
