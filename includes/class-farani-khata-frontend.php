<?php
if (!defined('ABSPATH')) {
	exit;
}

class Farani_Khata_Frontend
{
	public function __construct()
	{
		add_action('template_redirect', array($this, 'load_app_ui'));
		add_shortcode('ultimate_khata_app', array($this, 'shortcode_fallback'));
	}

	public function load_app_ui()
	{
		if (is_page('khata-app')) {
			// Ensure user is logged in
			if (!is_user_logged_in()) {
				auth_redirect();
				exit;
			}

			// Restrict logic could go here, e.g., current_user_can('manage_options')
			// Include template and exit
			include FARANI_KHATA_DIR . 'templates/app-ui.php';
			exit;
		}
	}

	public function shortcode_fallback()
	{
		return '<p>Redirecting to the app...</p><script>window.location.href="' . site_url('/khata-app/') . '";</script>';
	}
}
