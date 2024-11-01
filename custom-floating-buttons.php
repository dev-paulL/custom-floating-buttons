<?php
/**
 * Plugin Name: Boutons flottants personnalisés
 * Description: Un plugin pour ajouter des boutons flottants personnalisés à la page.
 * Version: 1.0.1
 * Author: Paul Dev
 * Text Domain: custom-floating-buttons
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue button assets (front-end)
function cflb_enqueue_button_assets()
{
    wp_enqueue_style('cflb-button-style', plugins_url('assets/css/button.css', __FILE__), array(), '1.0.1');
    wp_enqueue_style('dashicons');
}

// Enqueue admin assets (form, settings, etc.)
function cflb_enqueue_admin_assets()
{
    wp_enqueue_style('cflb-admin-style', plugins_url('assets/css/admin.css', __FILE__), array(), '1.0.1');
    wp_enqueue_style('dashicons');
}

add_action('wp_enqueue_scripts', 'cflb_enqueue_button_assets');
add_action('admin_enqueue_scripts', 'cflb_enqueue_admin_assets');
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';