<?php
/**
 * Plugin Name: WooCommerce Shipping Class Report
 * Description: A plugin to display all shipping classes, related products, and flat rate shipping costs for each class in a single shipping zone.
 * Version: 1.1
 * Author: Anas Dev
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Function to display shipping class and product report in the admin area
 */
function display_shipping_classes_and_products_admin_page() {
    echo '<div class="wrap"><h1>WooCommerce Shipping Class Report</h1>';

    // Get all shipping zones
    $zones = WC_Shipping_Zones::get_zones();

    // Include some basic inline JavaScript for expanding/collapsing product lists
    echo '<script type="text/javascript">
        function toggleProductList(classId) {
            var productList = document.getElementById("product-list-" + classId);
            var toggleButton = document.getElementById("toggle-button-" + classId);
            
            if (productList.style.display === "none") {
                productList.style.display = "block";
                toggleButton.innerText = "Hide Products";
            } else {
                productList.style.display = "none";
                toggleButton.innerText = "Show Products";
            }
        }
    </script>';

    // Loop through all shipping zones
    foreach ( $zones as $zone ) {
        echo '<h2>Shipping Zone: ' . $zone['zone_name'] . '</h2>';
        $shipping_methods = $zone['shipping_methods'];

        // Loop through the shipping methods for this zone
        foreach ( $shipping_methods as $method ) {
            if ( 'flat_rate' === $method->id ) {
                echo '<h3>Shipping Method: ' . $method->title . '</h3>';
                
                // Use WordPress admin table classes for styling
                echo '<table class="widefat fixed striped">';
                echo '<thead><tr><th>Shipping Class</th><th>Products</th><th>Flat Rate Cost</th></tr></thead>';
                echo '<tbody>';

                // Get all shipping classes
                $shipping_classes = WC()->shipping->get_shipping_classes();

                // Fetch costs from method settings (stored in 'class_cost_' keys)
                foreach ( $shipping_classes as $shipping_class ) {
                    $class_id = $shipping_class->term_id;
                    
                    // Retrieve the cost for each shipping class, or use a default if not set
                    $class_cost_key = 'class_cost_' . $class_id;
                    $class_cost = isset( $method->instance_settings[ $class_cost_key ] ) && '' !== $method->instance_settings[ $class_cost_key ] ? $method->instance_settings[ $class_cost_key ] : 'Default cost';

                    // Get products that belong to this shipping class
                    $products = get_posts( array(
                        'post_type'   => 'product',
                        'numberposts' => -1,
                        'tax_query'   => array(
                            array(
                                'taxonomy' => 'product_shipping_class',
                                'field'    => 'id',
                                'terms'    => $class_id,
                            ),
                        ),
                    ) );

                    $product_list = '';
                    foreach ( $products as $product ) {
                        // Create link to product edit page
                        $edit_link = get_edit_post_link( $product->ID );
                        $product_list .= '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $product->post_title ) . '</a><br>';
                    }

                    // Show total product count and expandable list
                    $product_count = count( $products );
                    $product_list = ! empty( $product_list ) ? $product_list : 'No products';

                    // Display shipping class, product count, and cost
                    echo '<tr>';
                    echo '<td>' . esc_html( $shipping_class->name ) . '</td>';
                    echo '<td>';
                    echo '<span>' . esc_html( $product_count ) . ' products</span>';
                    echo ' <button id="toggle-button-' . esc_attr( $class_id ) . '" onclick="toggleProductList(' . esc_attr( $class_id ) . ')">Show Products</button>';
                    echo '<div id="product-list-' . esc_attr( $class_id ) . '" style="display:none; margin-top: 10px;">' . $product_list . '</div>';
                    echo '</td>';
                    echo '<td>' . ( ! empty( $class_cost ) ? wc_price( $class_cost ) : 'Default flat rate' ) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
            }
        }
    }

    echo '</div>';
}

/**
 * Function to add the custom menu item in the admin sidebar
 */
function register_shipping_class_report_menu() {
    add_menu_page(
        'Shipping Class Report', // Page title
        'Shipping Class Report', // Menu title
        'manage_woocommerce',    // Capability required to see the menu
        'shipping-class-report', // Menu slug
        'display_shipping_classes_and_products_admin_page', // Function to display the report
        'dashicons-chart-area',  // Icon for the menu item
        56                       // Position of the menu item
    );
}

add_action( 'admin_menu', 'register_shipping_class_report_menu' );
