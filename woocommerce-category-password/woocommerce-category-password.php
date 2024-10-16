<?php
/**
 * Plugin Name: WooCommerce Category Password Protection
 * Description: Adds password protection to selected WooCommerce categories with a global password and an admin page to manage protected categories.
 * Version: 1.1
 * Author: Anas Dev
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Create Admin Menu for Global Password and Category Selection
 */
function wc_category_password_menu() {
    add_menu_page(
        'Category Passwords',         // Page title
        'Category Passwords',         // Menu title
        'manage_options',             // Capability
        'wc-category-password',       // Menu slug
        'wc_category_password_page',  // Function to display the page
        'dashicons-lock',             // Icon
        56                            // Position
    );
}
add_action( 'admin_menu', 'wc_category_password_menu' );

/**
 * Display the Admin Page for Managing Global Password and Protected Categories
 */
function wc_category_password_page() {
    if ( isset( $_POST['save_password'] ) ) {
        $password = isset( $_POST['global_password'] ) ? sanitize_text_field( $_POST['global_password'] ) : '';
        update_option( 'wc_global_category_password', $password );

        $protected_categories = isset( $_POST['protected_categories'] ) ? $_POST['protected_categories'] : [];
        update_option( 'wc_protected_categories', $protected_categories );

        echo '<div class="updated"><p>Password and categories updated successfully!</p></div>';
    }

    $global_password = get_option( 'wc_global_category_password', '' );
    $protected_categories = get_option( 'wc_protected_categories', [] );
    $categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );

    echo '<div class="wrap">';
    echo '<h1>WooCommerce Category Password Protection</h1>';
    echo '<form method="POST" action="">';
    
    // Password input field
    echo '<h3>Password for Protected Categories</h3>';
    echo '<input type="text" name="global_password" value="' . esc_attr( $global_password ) . '" class="regular-text" />';
    echo '<p><input type="submit" name="save_password" value="Update" class="button button-primary"></p>';
    
    // Category selection with "Select All" checkbox
    echo '<h3>Select Categories to Protect</h3>';
    echo '<table class="widefat striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Category</th>';
    echo '<th><input type="checkbox" id="select-all" /> Protect All</th>'; // "Select All" checkbox
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ( $categories as $category ) {
        $checked = in_array( $category->term_id, $protected_categories ) ? 'checked' : '';
        echo '<tr>';
        echo '<td>' . esc_html( $category->name ) . '</td>';
        echo '<td><input type="checkbox" name="protected_categories[]" value="' . esc_attr( $category->term_id ) . '" ' . $checked . ' class="category-checkbox"></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    
    echo '</form>';
    echo '</div>';
    
    // Add JavaScript for the "Select All" checkbox functionality
    echo '
    <script type="text/javascript">
        document.getElementById("select-all").addEventListener("change", function() {
            var checkboxes = document.querySelectorAll(".category-checkbox");
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });
    </script>';
}

/**
 * Front-end Password Protection
 */
function password_protect_category_page() {
    if ( is_product_category() ) {
        $category = get_queried_object();
        $protected_categories = get_option( 'wc_protected_categories', [] );
        $global_password = get_option( 'wc_global_category_password', '' );

        // Check if the category is in the protected list
        if ( in_array( $category->term_id, $protected_categories ) && ! empty( $global_password ) ) {
            // Show the password protection dialog if the password hasn't been submitted or is incorrect
            if ( ! isset( $_POST['category_password'] ) || $_POST['category_password'] !== $global_password ) {
                $error_message = isset( $_POST['category_password'] ) ? '<p style="color: red;">Incorrect password. Please try again.</p>' : '';

                echo '
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
                <style>
                    .password-protect-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.5);
                        z-index: 999;
                    }
                    .password-protect-dialog {
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background-color: #fff;
                        padding: 20px;
                        max-width: 400px;
                        width: 100%;
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
                        z-index: 1000;
                        border-radius: 8px;
                        font-family: "Poppins", Sans-serif;
                        font-size: 16px;
                        font-weight: 500;
                        text-align: center;
                    }
                    .password-protect-dialog h2 {
                        margin: 0 0 10px;
                        font-size: 1.5em;
                        color: #333;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .password-protect-dialog h2::before {
                        content: "\f023";
                        font-family: "Font Awesome 5 Free";
                        font-weight: 900;
                        margin-right: 10px;
                        color: #007cba;
                    }
                    .password-protect-dialog p {
                        margin-bottom: 20px;
                        color: #555;
                    }
                    .password-protect-dialog input[type="password"] {
                        width: 100%;
                        padding: 12px;
                        margin-bottom: 15px;
                        font-size: 16px;
                        border: 1px solid #ddd;
                        border-radius: 6px;
                    }
                    .password-protect-dialog .button-row {
                        display: flex;
                        justify-content: center;
                        gap: 10px;
                        margin-top: 10px;
                    }
                    .password-protect-dialog button.submit-button,
                    .password-protect-dialog button.back-button {
                        padding: 12px;
                        font-size: 16px;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        transition: background-color 0.3s ease;
                        width: 120px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .password-protect-dialog button.submit-button {
                        background-color: #007cba;
                        color: #fff;
                    }
                    .password-protect-dialog button.submit-button:hover {
                        background-color: #005a9c;
                    }
                    .password-protect-dialog button.back-button {
                        background-color: #f1f1f1;
                        color: #333;
                    }
                    .password-protect-dialog button.back-button:hover {
                        background-color: #e0e0e0;
                    }
                    .password-protect-dialog .fas {
                        margin-right: 8px;
                    }
                </style>

                <div class="password-protect-overlay"></div>
                <div class="password-protect-dialog">
                    <h2>Password Protected</h2>
                    ' . $error_message . '
                    <p>Please enter the password to access this category:</p>
                    <form action="" method="POST">
                        <input type="password" name="category_password" placeholder="Enter Password" />
                        <div class="button-row">
                            <button class="submit-button" type="submit"><i class="fas fa-unlock"></i> Unlock </button>
                            <button class="back-button" type="button" onclick="history.back()"><i class="fas fa-arrow-left"></i> Go Back</button>
                        </div>
                    </form>
                </div>';
                exit;
            }
        }
    }
}
add_action( 'template_redirect', 'password_protect_category_page' );
