<?php
/**
 * Plugin Name: Dynamic Discount Plugin
 * Description: A plugin to apply dynamic discounts to specific products based on payment methods.
 * Version: 1.1
 * Author: Anas Dev
 */

// Register admin menu page
add_action('admin_menu', 'ddp_add_admin_menu');
function ddp_add_admin_menu() {
    add_menu_page(
        'Dynamic Discount Settings',
        'Discount Settings',
        'manage_options',
        'ddp-settings',
        'ddp_settings_page',
        'dashicons-cart',
        20
    );
}

// Admin page content
function ddp_settings_page() {
    if (isset($_POST['ddp_save_settings'])) {
        update_option('ddp_discount_percentage', sanitize_text_field($_POST['ddp_discount_percentage']));
        update_option('ddp_discounted_product_ids', sanitize_text_field($_POST['ddp_discounted_product_ids']));
        update_option('ddp_payment_methods', $_POST['ddp_payment_methods']);
        echo '<div class="updated"><p>Settings saved successfully!</p></div>';
    }

    $discount_percentage = get_option('ddp_discount_percentage', '20');
    $discounted_product_ids = get_option('ddp_discounted_product_ids', '');
    $selected_payment_methods = get_option('ddp_payment_methods', []);

    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    ?>
    <div class="wrap">
        <h1>Dynamic Discount Settings</h1>
        <form method="POST" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ddp_discount_percentage">Discount Percentage</label></th>
                    <td><input type="number" id="ddp_discount_percentage" name="ddp_discount_percentage" value="<?php echo esc_attr($discount_percentage); ?>" />%</td>
                </tr>
                <tr>
                    <th scope="row"><label for="ddp_discounted_product_ids">Discounted Product IDs</label></th>
                    <td><input type="text" id="ddp_discounted_product_ids" name="ddp_discounted_product_ids" value="<?php echo esc_attr($discounted_product_ids); ?>" /><br />
                    <small>Enter product IDs separated by commas (e.g., 8937,8896,2519)</small></td>
                </tr>
                <tr>
                    <th scope="row">Select Payment Methods for Discount</th>
                    <td>
                        <?php foreach ($payment_gateways as $gateway) { ?>
                            <label><input type="checkbox" name="ddp_payment_methods[]" value="<?php echo esc_attr($gateway->id); ?>" <?php checked(in_array($gateway->id, $selected_payment_methods)); ?> /> <?php echo esc_html($gateway->get_title()); ?></label><br />
                        <?php } ?>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="ddp_save_settings" id="submit" class="button button-primary" value="Save Settings"></p>
        </form>
    </div>
    <?php
}

// Apply discount based on settings
add_filter('woocommerce_get_price_html', 'ddp_custom_discounted_price', 10, 2);
function ddp_custom_discounted_price($price, $product) {
    $discounted_product_ids = explode(',', get_option('ddp_discounted_product_ids', ''));
    $discount_percentage = get_option('ddp_discount_percentage', '20');

    if (in_array($product->get_id(), $discounted_product_ids)) {
        $regular_price = $product->get_regular_price();
        $discounted_price = $regular_price * ((100 - $discount_percentage) / 100);
        return '<del>' . wc_price($regular_price) . '</del> <ins style="color:red;">' . wc_price($discounted_price) . '</ins>';
    }
    return $price;
}

// Apply discount based on selected payment method
add_action('woocommerce_cart_calculate_fees', 'ddp_apply_discount_based_on_payment_method', 20, 1);
function ddp_apply_discount_based_on_payment_method($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $chosen_payment_method = WC()->session->get('chosen_payment_method');
    $selected_payment_methods = get_option('ddp_payment_methods', []);
    $discount_percentage = get_option('ddp_discount_percentage', '20');

    if (in_array($chosen_payment_method, $selected_payment_methods)) {
        $discount = $cart->cart_contents_total * ($discount_percentage / 100);
        $cart->add_fee(sprintf(__('%s%% Discount', 'woocommerce'), $discount_percentage), -$discount);
    }
}

// Save payment method in session
add_action('woocommerce_checkout_update_order_review', 'ddp_save_payment_method_to_session');
function ddp_save_payment_method_to_session($posted_data) {
    parse_str($posted_data, $data);
    if (isset($data['payment_method'])) {
        WC()->session->set('chosen_payment_method', $data['payment_method']);
    }
}
