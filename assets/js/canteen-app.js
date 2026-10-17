// assets/js/canteen-app.js - Allergen Detection, Modal Interceptor, and Shared Cart Management
$(document).ready(function() {
    'use strict';

    let pendingFoodItem = null;

    // Toast alert helper
    window.showToast = function(message, type = 'success') {
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle');
        const alertClass = type === 'success' ? 'alert-success' : (type === 'danger' ? 'alert-danger' : 'alert-warning');
        
        const toast = $(`
            <div class="canteen-alert alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${icon} mr-2"></i> ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);
        
        $('#canteenToastContainer').append(toast);
        setTimeout(function() {
            toast.alert('close');
        }, 4000);
    };

    // Update cart badge across all header instances
    window.updateHeaderCartBadge = function(count) {
        const badge = $('#headerCartCount');
        badge.text(count);
        $('.headerUserCartUnits').text(count);
        badge.addClass('bounce');
        setTimeout(() => badge.removeClass('bounce'), 300);
    };

    // Fetch and sync active cart count on page load
    $.getJSON('api/cart.php?action=get', function(res) {
        if (res.success && res.cart) {
            updateHeaderCartBadge(res.cart.total_units);
        }
    });

    // Add to Cart handler with server-side allergen check
    $(document).on('click', '.btn-add-to-cart, .add-to-cart-trigger', function(e) {
        e.preventDefault();
        const btn = $(this);
        const foodId = btn.data('food-id');
        const qty = parseInt(btn.data('quantity') || 1, 10);

        if (!foodId) return;

        btn.prop('disabled', true).addClass('loading');

        // 1. Perform server-side allergen check
        $.ajax({
            url: 'api/check_allergen.php',
            method: 'GET',
            data: { food_id: foodId },
            dataType: 'json',
            success: function(resp) {
                btn.prop('disabled', false).removeClass('loading');

                if (!resp.success) {
                    showToast(resp.message || 'Error checking food item.', 'danger');
                    return;
                }

                const food = resp.food;

                // Case 1: Allergen Conflict detected OR Unverified Ingredients
                if (resp.has_allergen_conflict || resp.is_unverified) {
                    pendingFoodItem = {
                        food_id: food.id,
                        food_name: food.name,
                        quantity: qty,
                        allergen_warning: true,
                        allergen_details: resp.has_allergen_conflict ? resp.matched_allergens : ['Unverified Ingredients'],
                        triggering_ingredients: resp.triggering_ingredients
                    };

                    // Populate Modal UI
                    $('#allergenModalFoodName').text(food.name);
                    if (food.image) {
                        $('#allergenModalFoodImg').attr('src', food.image);
                    }

                    const badgesContainer = $('#allergenModalBadges').empty();
                    if (resp.has_allergen_conflict) {
                        resp.matched_allergens.forEach(function(alg) {
                            badgesContainer.append(`<span class="allergen-tag"><i class="fas fa-exclamation-triangle"></i> Allergen: ${alg}</span>`);
                        });
                        if (resp.triggering_ingredients && resp.triggering_ingredients.length > 0) {
                            resp.triggering_ingredients.forEach(function(ing) {
                                badgesContainer.append(`<span class="allergen-tag safe"><i class="fas fa-utensils"></i> Found in: ${ing}</span>`);
                            });
                        }
                    } else if (resp.is_unverified) {
                        badgesContainer.append(`<span class="allergen-tag"><i class="fas fa-question-circle"></i> Incomplete / Unverified Recipe</span>`);
                    }

                    $('#allergenModalExplanation').text(resp.explanation);

                    // Show Warning Modal
                    $('#allergenWarningModal').modal('show');
                } else {
                    // Case 2: Safe to add directly without warning
                    performAddToCart({
                        food_id: food.id,
                        quantity: qty,
                        allergen_warning: false,
                        allergen_details: []
                    });
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).removeClass('loading');
                showToast('Unable to check allergens right now.', 'danger');
            }
        });
    });

    // Modal Option A: Cancel (Close and do not add to cart)
    $('#btnCancelAllergen, #allergenModalCloseX').on('click', function() {
        pendingFoodItem = null;
        $('#allergenWarningModal').modal('hide');
        showToast('Item cancelled. It was not added to your cart.', 'warning');
    });

    // Modal Option B: Continue Anyway (Add to cart preserving allergen warning)
    $('#btnContinueAllergen').on('click', function() {
        if (!pendingFoodItem) return;

        const itemToAdd = Object.assign({}, pendingFoodItem);
        pendingFoodItem = null;
        $('#allergenWarningModal').modal('hide');

        performAddToCart(itemToAdd);
    });

    // Core Add to Cart Ajax
    function performAddToCart(itemData) {
        $.ajax({
            url: 'api/cart.php?action=add',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(itemData),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    updateHeaderCartBadge(res.total_units);
                    if (itemData.allergen_warning) {
                        showToast(`<strong>${res.message}</strong> (Allergen warning acknowledged and flagged for checkout)`, 'warning');
                    } else {
                        showToast(`<strong>${res.message}</strong>`, 'success');
                    }
                } else {
                    showToast(res.message || 'Could not add item to cart.', 'danger');
                }
            },
            error: function() {
                showToast('Network error while updating cart.', 'danger');
            }
        });
    }

    // Cart Quantity Increment / Decrement
    $(document).on('change', '.cart-qty-input', function() {
        const input = $(this);
        const cartItemId = input.data('cart-item-id');
        const foodId = input.data('food-id');
        const newQty = parseInt(input.val(), 10);

        if (isNaN(newQty) || newQty < 0) {
            input.val(1);
            return;
        }

        $.ajax({
            url: 'api/cart.php?action=update',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                cart_item_id: cartItemId,
                food_id: foodId,
                quantity: newQty
            }),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    updateHeaderCartBadge(res.total_units);
                    if (typeof window.renderCartTable === 'function') {
                        window.renderCartTable(res.cart);
                    } else if (typeof window.renderCheckoutSummary === 'function') {
                        window.renderCheckoutSummary(res.cart);
                    } else {
                        location.reload();
                    }
                }
            }
        });
    });

    // Remove item from Cart
    $(document).on('click', '.btn-remove-cart-item', function(e) {
        e.preventDefault();
        const btn = $(this);
        const cartItemId = btn.data('cart-item-id');
        const foodId = btn.data('food-id');

        $.ajax({
            url: 'api/cart.php?action=remove',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ cart_item_id: cartItemId, food_id: foodId }),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    updateHeaderCartBadge(res.total_units);
                    showToast('Item removed from cart.', 'info');
                    if (typeof window.renderCartTable === 'function') {
                        window.renderCartTable(res.cart);
                    } else if (typeof window.renderCheckoutSummary === 'function') {
                        window.renderCheckoutSummary(res.cart);
                    } else {
                        location.reload();
                    }
                }
            }
        });
    });

});
