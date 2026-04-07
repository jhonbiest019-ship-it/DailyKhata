<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Farani_Khata_DB
{

	public static function activate()
	{
		self::create_tables();
		self::create_app_page();

		// Set transient to redirect after activation
		set_transient('farani_khata_activation_redirect', true, 30);
	}

	private static function create_tables()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_customers = $wpdb->prefix . 'khata_customers';
		$table_entries = $wpdb->prefix . 'khata_entries';

		$sql_customers = "CREATE TABLE $table_customers (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			phone varchar(50) DEFAULT '' NOT NULL,
			email varchar(100) DEFAULT '' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		$sql_entries = "CREATE TABLE $table_entries (
			id int(11) NOT NULL AUTO_INCREMENT,
			customer_id int(11) NULL,
			type enum('credit','debit') NOT NULL,
			amount decimal(15,2) NOT NULL,
			description text NOT NULL,
			category varchar(100) DEFAULT '' NOT NULL,
			transaction_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY customer_id (customer_id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_customers);
		dbDelta($sql_entries);
	}

	private static function create_app_page()
	{
		$page_slug = 'khata-app';

		$page = get_page_by_path($page_slug);
		if (!$page) {
			wp_insert_post(array(
				'post_title' => 'Khata Dashboard',
				'post_name' => $page_slug,
				'post_content' => '[ultimate_khata_app]',
				'post_status' => 'publish',
				'post_type' => 'page',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
			));
		}
	}
}

function farani_khata_activation_redirect()
{
	if (get_transient('farani_khata_activation_redirect')) {
		delete_transient('farani_khata_activation_redirect');
		if (!is_network_admin() && !isset($_GET['activate-multi'])) {
			wp_safe_redirect(site_url('/khata-app/'));
			exit;
		}
	}
}
add_action('admin_init', 'farani_khata_activation_redirect');
