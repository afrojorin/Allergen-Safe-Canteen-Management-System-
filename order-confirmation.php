<?php
// order-confirmation.php - Order Receipt and Kitchen Preparation Summary
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Order Confirmation - Smart Canteen Management System';
$currentPage = 'checkout';

$orderCode = trim($_GET['code'] ?? '');
$pdo = get_db_connection();

if (empty($orderCode)) {
    header("Location: shop.php");
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.id, o.order_code, o.customer_name, o.customer_email, o.customer_phone, 
           o.delivery_address, o.pickup_time, o.subtotal, o.tax, o.total, o.status, o.special_notes, o.created_at
    FROM orders o
    WHERE o.order_code = ?
");
$stmt->execute([$orderCode]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: shop.php");
    exit;
}

// Fetch order items & modifications
$stmtItems = $pdo->prepare("
    SELECT oi.id AS order_item_id, oi.food_id, oi.food_name, oi.unit_price, oi.quantity, oi.line_total,
           oi.allergen_warning, oi.allergen_details, f.image
    FROM order_items oi
    LEFT JOIN food_items f ON oi.food_id = f.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order['id']]);
$items = $stmtItems->fetchAll();

include __DIR__ . '/includes/header.php';
?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Thank You For Your Order!</p>
						<h1>Order Confirmation</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<div class="mt-100 mb-150">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-10">
					<div class="card shadow border-0 p-4 p-md-5" style="border-radius: 18px;">
						
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <div class="mb-3">
                                <span class="d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: #e6ffed; border-radius: 50%; color: #389e0d;">
                                    <i class="fas fa-check fa-2x"></i>
                                </span>
                            </div>
                            <h2 class="font-weight-bold" style="color: #051922;">Order Successfully Placed!</h2>
                            <p class="text-muted">Your order has been recorded in the database and sent to the canteen kitchen.</p>
                            <div class="badge badge-warning py-2 px-3 font-weight-bold" style="font-size: 16px; background: #fff2e8; color: #d4380d; border: 1px solid #ffbb96; border-radius: 8px;">
                                Order Reference: <strong><?php echo e($order['order_code']); ?></strong>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-user-circle text-warning mr-1"></i> Customer Information</h5>
                                <p class="mb-1"><strong>Name:</strong> <?php echo e($order['customer_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo e($order['customer_email']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo e($order['customer_phone']); ?></p>
                                <p class="mb-1"><strong>Pickup Location:</strong> <?php echo e($order['delivery_address']); ?></p>
                            </div>
                            <div class="col-md-6 text-md-right">
                                <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-clock text-warning mr-1"></i> Schedule Details</h5>
                                <p class="mb-1"><strong>Order Placed:</strong> <?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></p>
                                <p class="mb-1"><strong>Pickup Time:</strong> <span class="badge badge-success px-2 py-1"><?php echo e($order['pickup_time']); ?></span></p>
                                <p class="mb-1"><strong>Order Status:</strong> <span class="badge badge-primary px-2 py-1" style="background:#051922;"><?php echo e($order['status']); ?></span></p>
                            </div>
                        </div>

                        <!-- Order Line Items -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Unit Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-right">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                            // Fetch modifications for this item
                                            $stmtMods = $pdo->prepare("SELECT ingredient_name, modification_type, instructions FROM order_item_modifications WHERE order_item_id = ?");
                                            $stmtMods->execute([$item['order_item_id']]);
                                            $mods = $stmtMods->fetchAll();
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold" style="color: #051922; font-size: 15px;">
                                                    <?php echo e($item['food_name']); ?>
                                                </div>
                                                
                                                <?php if ($item['allergen_warning']): ?>
                                                    <div class="mt-1">
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-exclamation-triangle"></i> Allergen Warning Acknowledged
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($mods)): ?>
                                                    <div class="mt-2 p-2 bg-light rounded small" style="border-left: 3px solid #F28123;">
                                                        <strong class="text-danger"><i class="fas fa-utensils mr-1"></i> Kitchen Preparation Requests:</strong>
                                                        <ul class="mb-0 pl-3">
                                                            <?php foreach ($mods as $m): ?>
                                                                <li>
                                                                    <?php if ($m['modification_type'] === 'removal'): ?>
                                                                        <strong>Removed:</strong> <?php echo e($m['ingredient_name']); ?>
                                                                    <?php else: ?>
                                                                        <strong>Note:</strong> <?php echo e($m['instructions']); ?>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($item['unit_price'], 0); ?>tk</td>
                                            <td class="text-center font-weight-bold"><?php echo $item['quantity']; ?></td>
                                            <td class="text-right font-weight-bold" style="color: #F28123;">
                                                <?php echo number_format($item['line_total'], 0); ?>tk
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold">Subtotal:</td>
                                        <td class="text-right font-weight-bold"><?php echo number_format($order['subtotal'], 0); ?>tk</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold" style="font-size: 18px; color: #051922;">Total Paid / Due on Pickup:</td>
                                        <td class="text-right font-weight-bold" style="font-size: 20px; color: #F28123;">
                                            <?php echo number_format($order['total'], 0); ?>tk
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($order['special_notes'])): ?>
                            <div class="alert alert-light border p-3 mb-4">
                                <strong><i class="fas fa-comment-alt text-warning mr-1"></i> Special Order Notes:</strong>
                                <?php echo e($order['special_notes']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center flex-wrap pt-3 border-top">
                            <a href="shop.php" class="boxed-btn black py-3 px-4" style="border-radius: 30px; font-weight: 700; background: #F28123; border: none;">
                                <i class="fas fa-plus mr-1"></i> Start a New Order (Cart Cleared)
                            </a>
                            <button type="button" class="btn btn-outline-secondary py-3 px-4" onclick="window.print()" style="border-radius: 30px;">
                                <i class="fas fa-print mr-1"></i> Print Receipt
                            </button>
                        </div>

					</div>
				</div>
			</div>
		</div>
	</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
