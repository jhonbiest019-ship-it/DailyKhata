<?php
/**
 * Plugin Name: Farani Daily Khata
 * Description: A professional-grade Monthly Expense and Udhaar Management System with custom database architecture.
 * Version: 1.0.0
 * Author: Sikandar Hayat Baba
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('FARANI_KHATA_VERSION', '1.0.0');
define('FARANI_KHATA_DIR', plugin_dir_path(__FILE__));
define('FARANI_KHATA_URL', plugin_dir_url(__FILE__));

// Includes
require_once FARANI_KHATA_DIR . 'includes/class-farani-khata-db.php';
require_once FARANI_KHATA_DIR . 'includes/class-farani-khata-frontend.php';
require_once FARANI_KHATA_DIR . 'includes/class-farani-khata-ajax.php';

// Activation
register_activation_hook(__FILE__, array('Farani_Khata_DB', 'activate'));

// Initialize components
function farani_khata_init()
{
	new Farani_Khata_Frontend();
	new Farani_Khata_Ajax();
}
add_action('plugins_loaded', 'farani_khata_init');
