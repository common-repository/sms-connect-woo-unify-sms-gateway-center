<?php
	/*
	  Plugin Name: SMSConnectWoo Unify SGC SMS
	  Plugin URI: https://www.smsgatewaycenter.com/sms-order-alert-notification-woocommerce/
	  Description: Sends SMS Messages for WooCommerce Order statuses and send bulk SMS to all subscribers groups via unify.smsgateway.center SMS API.
	  Version: 1.0.0
	  Author: SMS Gateway Center
	  Author URI: https://unify.smsgateway.center
	  Text Domain: unify_sgc_sms
	  License: GPL-2.0+
	  License URI: http://www.gnu.org/licenses/gpl-2.0.html
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access

	$plugin_dir_name = dirname(plugin_basename(__FILE__));

	if (!defined('UNIFY_SGC_SMS_DIR')) {
		define("UNIFY_SGC_SMS_DIR", WP_PLUGIN_DIR . "/" . $plugin_dir_name);
	}

	if (!defined('UNIFY_SGC_SMS_GATEWAY_URL')) {
		define("UNIFY_SGC_SMS_GATEWAY_URL", WP_PLUGIN_URL . "/" . $plugin_dir_name);
	}

	global $unifySGCSMSid,
	$wpdb,
	$woocommerce,
	$product;

	class unifySGCSMSAlerts {

		/**
		 * Options for Admin Page Settings
		 *
		 * This variable holds the options for the admin page settings.
		 * It is used to store and retrieve configuration settings for the plugin's admin page.
		 */
		private $options;

		/**
		 * Constructor for the class.
		 *
		 * Sets up the necessary WordPress hooks for adding admin menus, initializing settings pages, and enqueuing scripts.
		 * It also initializes an instance of the UnifySMSGatewayCenter_Alerts_Subscriptions class for managing subscriptions.
		 *
		 * Actions:
		 * - 'admin_menu': Adds custom menus and submenus to the WordPress admin dashboard.
		 * - 'admin_init': Initializes settings for the plugin's admin pages.
		 * - 'admin_enqueue_scripts': Enqueues custom styles and scripts needed for the plugin's admin interface.
		 *
		 * Additionally, it includes the class file for subscription management and instantiates the subscription management object.
		 */
		public function __construct() {
			add_action('admin_menu', array($this, 'unify_sgc_sms_menu'));
			add_action('admin_init', array($this, 'unify_sgc_sms_page_init'));
			add_action('admin_enqueue_scripts', array($this, 'unify_sgc_sms_enqueue_script'));

			include_once UNIFY_SGC_SMS_DIR . '/includes/class-unify-alerts-subscribe.php';
			$this->subscribe = new unifySMS_Alerts_Subscriptions();
		}

		/**
		 * Creates the menu and submenu items for the UnifySMSGatewayCenter in the WordPress admin dashboard.
		 *
		 * This method adds a main menu item for the unifysgcsms plugin in the WordPress admin dashboard,
		 * along with several submenus for different plugin functionalities like DLR Logs, Subscribers, and Subscriber Groups.
		 *
		 * The function utilizes WordPress's `add_menu_page` and `add_submenu_page` functions to create these menu items.
		 */
//		public function unify_sgc_sms_menu() {
//			add_menu_page('Unify SGCSMS', 'Unify SGCSMS Settings', 'manage_options', 'unify_sgc_sms_settings', array($this, 'unify_sgc_sms_page'), 'dashicons-comments');
//			add_submenu_page('unify_sgc_sms_settings', 'DLR Logs', 'DLR Logs', 'manage_options', 'unify_sgc_sms_dlr_page', array($this, 'unify_sgc_sms_dlr_page'));
//			add_submenu_page('unify_sgc_sms_settings', 'Subscribers', 'Subscribers', 'manage_options', 'unify_sgc_sms_subscribers', array($this, 'unify_sgc_sms_subscribers'));
//			add_submenu_page('unify_sgc_sms_settings', 'Groups', 'Groups', 'manage_options', 'unify_sgc_sms_subscriber_groups', array($this, 'unify_sgc_sms_subscriber_groups'));
//		}

		public function unify_sgc_sms_menu() {
			add_menu_page('Unify SGC SMS', 'Unify SGC SMS', 'manage_options', 'unify_sgc_sms_settings', array($this, 'unify_sgc_sms_page'), 'dashicons-admin-comments');

			// Add Settings submenu under Unify SGC SMS
			add_submenu_page('unify_sgc_sms_settings', 'Settings', 'Settings', 'manage_options', 'unify_sgc_sms_settings', array($this, 'unify_sgc_sms_page'));

			// Add Subscribers submenu under Settings
			add_submenu_page('unify_sgc_sms_settings', 'Subscribers', 'Subscribers', 'manage_options', 'unify_sgc_sms_subscribers', array($this, 'unify_sgc_sms_subscribers'));

			// Add Groups submenu under Settings
			add_submenu_page('unify_sgc_sms_settings', 'Groups', 'Groups', 'manage_options', 'unify_sgc_sms_subscriber_groups', array($this, 'unify_sgc_sms_subscriber_groups'));

			// Add DLR Logs submenu under Settings
			add_submenu_page('unify_sgc_sms_settings', 'DLR Logs', 'DLR Logs', 'manage_options', 'unify_sgc_sms_dlr_page', array($this, 'unify_sgc_sms_dlr_page'));
		}

		/**
		 * Enqueues custom styles and scripts for the unifysgcsms plugin in the admin dashboard.
		 *
		 * This method is responsible for adding the plugin's CSS and JavaScript files to the WordPress admin interface.
		 * It uses WordPress's `wp_enqueue_style` and `wp_enqueue_script` functions to enqueue the styles and scripts.
		 *
		 * The enqueued scripts and styles include:
		 * - The plugin's main stylesheet.
		 * - Bootstrap's stylesheet for UI elements.
		 * - jQuery pagination plugin for handling pagination functionalities.
		 */
		public function unify_sgc_sms_enqueue_script() {
			wp_enqueue_style('unify_sgc_sms_settings', UNIFY_SGC_SMS_GATEWAY_URL . '/assets/css/unify_sgcsms_alerts_style.css');
			wp_enqueue_style('unify_sgc_sms_settings_bootstrap_min', UNIFY_SGC_SMS_GATEWAY_URL . '/assets/css/bootstrap.min.css');
			wp_enqueue_script('unify_sgc_sms_settings_twbsPagination', UNIFY_SGC_SMS_GATEWAY_URL . '/assets/js/jquery.twbsPagination.min.js?v=0.0001');
		}

		/**
		 * Defines constants for the unifysgcsms plugin.
		 *
		 * This script sets up several important constants used throughout the plugin. These constants include:
		 *
		 * UNIFY_SGC_SMS_DIR:
		 * - The absolute directory path of the plugin on the server. It combines the WordPress plugin directory path
		 *   with the plugin's directory name.
		 *
		 * UNIFY_SGC_SMS_GATEWAY_URL:
		 * - The URL to the plugin's directory. This is used for referencing plugin assets like scripts, styles, or images.
		 *
		 * Each constant is defined only if it hasn't been defined already, ensuring they don't get redefined if they already exist.
		 *
		 * Note:
		 * - These constants are crucial for the plugin's file system structure and URL referencing, hence their definition
		 *   at the beginning of the plugin's execution.
		 * - 'plugin_basename(__FILE__)' is used to get the plugin's directory name dynamically, making the code more portable and reusable.
		 */
		public function unify_sgc_sms_page_init() {
			//register
			register_setting(
				'unify_sgc_sms_option_group', // Option group
				'unify_sgc_sms_option_name', // Option name
				array($this, 'unify_sgc_sms_settings_sanitize') // Sanitize
			);

			//API heading section settings
			add_settings_section(
				'unify_sgc_sms_api_config', // ID
				'unify.smsgateway.center API Credentials', // Title
				'', // Callback
				'unify_sgc_sms_settings' // Page
			);

			//API userId section settings
			add_settings_field(
				'unify_sgc_sms_userId', 'Username', array($this, 'unify_sgc_sms_userId_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_api_config'
			);

			//API password section settings
			add_settings_field(
				'unify_sgc_sms_password', 'Password', array($this, 'unify_sgc_sms_password_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_api_config'
			);

			//API sender id section settings
			add_settings_field(
				'unify_sgc_sms_sendername', 'Sender Name', array($this, 'unify_sgc_sms_sendername_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_api_config'
			);

			//Admin notification section heading
			add_settings_section(
				'unify_sgc_sms_order_admin_notify_config', ' Alert Settings for Admins', '', 'unify_sgc_sms_settings'
			);

			//Admin notify enable/disable section settings
			add_settings_field(
				'unify_sgc_sms_notify_admin', 'Enable / Disable Admin Notification', array($this, 'unify_sgc_sms_admin_notify_callback'), 'unify_sgc_sms_settings', "unify_sgc_sms_order_admin_notify_config"
			);

			//Admin Mobile Number section settings
			add_settings_field(
				'unify_sgc_sms_admin_mobile', 'Admin\'s Mobile Number', array($this, 'unify_sgc_sms_admin_mobile_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_admin_notify_config'
			);

			//product review notification
			add_settings_field(
				'unify_sgc_sms_product_review_notification', 'Enable / Disable Product Review Notification', array($this, 'unify_sgc_sms_order_review_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_admin_notify_config'
			);

			//heading
			add_settings_section(
				'unify_sgc_sms_order_notify_config', 'Order Notification Configuration', '', 'unify_sgc_sms_settings'
			);

			//default variables
			add_settings_section(
				'unify_sgc_sms_order_notify_config_1', 'Default Variables Allowed', array($this, 'unify_sgc_sms_default_variables_callback'), 'unify_sgc_sms_settings'
			);

			//checkboxes to select specific notification
			add_settings_field(
				'unify_sgc_sms_order_status_notification', 'Select status to send notification', array($this, 'unify_sgc_sms_order_status_alert_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//order complete
			add_settings_field(
				'unify_sgc_sms_order_complete', 'Text Message Content for Order <span class="color-blue">Complete</span> Status', array($this, 'unify_sgc_sms_order_completed_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//order processing 
			add_settings_field(
				'unify_sgc_sms_order_processing', 'Text Message Content for Order <span class="color-blue">Processing</span> Status', array($this, 'unify_sgc_sms_order_processing_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//order pending payment
			add_settings_field(
				'unify_sgc_sms_order_pending_payment', 'Text Message Content for Order <span class="color-blue">Pending Payment</span> Status', array($this, 'unify_sgc_sms_order_pending_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//order on hold
			add_settings_field(
				'unify_sgc_sms_order_onhold', 'Text Message Content for Order <span class="color-blue">On-Hold</span> Status', array($this, 'unify_sgc_sms_order_onhold_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//order cancelled
			add_settings_field(
				'unify_sgc_sms_order_cancelled', 'Text Message Content for Order <span class="color-blue">Cancelled</span> Status', array($this, 'unify_sgc_sms_order_cancelled_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//order refunded
			add_settings_field(
				'unify_sgc_sms_order_refunded', 'Text Message Content for Order <span class="color-blue">Refunded</span> Status', array($this, 'unify_sgc_sms_order_refunded_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//order failed
			add_settings_field(
				'unify_sgc_sms_order_failed', 'Text Message Content for Order <span class="color-blue">Failed</span> Status', array($this, 'unify_sgc_sms_order_failed_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_order_notify_config'
			);

			//heading
			add_settings_section(
				'unify_sgc_sms_user_notification_config', 'Customer Notification Configuration', '', 'unify_sgc_sms_settings'
			);

			//new registrations
			add_settings_field(
				'unify_sgc_sms_regi_status', 'Text Message Content for Customer\'s <span class="color-blue">New Registration</span> Status', array($this, 'unify_sgc_sms_user_regi_status_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_user_notification_config'
			);

			//update profile
			add_settings_field(
				'unify_sgc_sms_update_profile', 'Text Message Content for Customer\'s <span class="color-blue">Profile Update</span> Status', array($this, 'unify_sgc_sms_user_update_profile_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_user_notification_config'
			);

			//password reset
			add_settings_field(
				'unify_sgc_sms_pass_reset', 'Text Message Content for Customer\'s <span class="color-blue">Forget Password</span> Status', array($this, 'unify_sgc_sms_password_reset_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_user_notification_config'
			);

			//coupon announcement
			add_settings_field(
				'unify_sgc_sms_coupon_announcement', 'Text Message Content for Customer\'s <span class="color-blue">Coupon Announcement</span>', array($this, 'unify_sgc_sms_coupon_announcement_callback'), 'unify_sgc_sms_settings', 'unify_sgc_sms_user_notification_config'
			);
		}

		/**
		 * Sanitizes and prepares the settings input for the unifysgcsms plugin.
		 *
		 * This function is responsible for sanitizing the input received from the plugin's settings form.
		 * It ensures that all input data is properly sanitized before being saved to the database.
		 * The function processes various settings related to the plugin, such as API credentials, message templates, 
		 * and notification settings for different WooCommerce order statuses and other events.
		 *
		 * Process:
		 * - The function iterates over each expected input field, checking if it is set.
		 * - For text fields, it uses `sanitize_text_field` to ensure text string safety.
		 * - For textarea fields, it uses `sanitize_textarea_field` to handle multi-line text inputs.
		 * - Boolean and other types of fields are directly assigned after checking if they are set.
		 * - The sanitized input values are then stored in a new array which is returned at the end.
		 *
		 * Note:
		 * - This function is typically used as a callback for the 'sanitize_callback' parameter in `register_setting` function 
		 *   as part of the WordPress Settings API.
		 * - It is crucial to sanitize user inputs to prevent security vulnerabilities like XSS attacks.
		 */
		public function unify_sgc_sms_settings_sanitize($input) {
			$new_input = array();
			// Sanitization process for each setting field
			if (isset($input['unify_sgc_sms_userId'])) {
				$new_input['unify_sgc_sms_userId'] = sanitize_text_field($input['unify_sgc_sms_userId']);
			}
			if (isset($input['unify_sgc_sms_password'])) {
				$new_input['unify_sgc_sms_password'] = sanitize_text_field($input['unify_sgc_sms_password']);
			}
			if (isset($input['unify_sgc_sms_sendername'])) {
				$new_input['unify_sgc_sms_sendername'] = sanitize_text_field($input['unify_sgc_sms_sendername']);
			}
			if (isset($input['unify_sgc_sms_admin_mobile'])) {
				$new_input['unify_sgc_sms_admin_mobile'] = sanitize_text_field($input['unify_sgc_sms_admin_mobile']);
			}
			//admin notify enable
			if (isset($input['unify_sgc_sms_notify_admin'])) {
				$new_input['unify_sgc_sms_notify_admin'] = $input['unify_sgc_sms_notify_admin'];
			}
			//complete
			if (isset($input['unify_sgc_sms_order_complete'])) {
				$new_input['unify_sgc_sms_order_complete'] = sanitize_textarea_field($input['unify_sgc_sms_order_complete']);
			}
			if (isset($input['unify_sgc_sms_order_completed_status'])) {
				$new_input['unify_sgc_sms_order_completed_status'] = $input['unify_sgc_sms_order_completed_status'];
			}
			//processing
			if (isset($input['unify_sgc_sms_order_processing'])) {
				$new_input['unify_sgc_sms_order_processing'] = sanitize_textarea_field($input['unify_sgc_sms_order_processing']);
			}
			if (isset($input['unify_sgc_sms_order_status_processing'])) {
				$new_input['unify_sgc_sms_order_status_processing'] = $input['unify_sgc_sms_order_status_processing'];
			}
			//pending payment
			if (isset($input['unify_sgc_sms_order_pending_payment'])) {
				$new_input['unify_sgc_sms_order_pending_payment'] = sanitize_textarea_field($input['unify_sgc_sms_order_pending_payment']);
			}
			if (isset($input['unify_sgc_sms_order_status_pending_payment'])) {
				$new_input['unify_sgc_sms_order_status_pending_payment'] = $input['unify_sgc_sms_order_status_pending_payment'];
			}
			//on hold
			if (isset($input['unify_sgc_sms_order_onhold'])) {
				$new_input['unify_sgc_sms_order_onhold'] = sanitize_textarea_field($input['unify_sgc_sms_order_onhold']);
			}
			if (isset($input['unify_sgc_sms_order_status_onhold'])) {
				$new_input['unify_sgc_sms_order_status_onhold'] = $input['unify_sgc_sms_order_status_onhold'];
			}
			//cancelled
			if (isset($input['unify_sgc_sms_order_cancelled'])) {
				$new_input['unify_sgc_sms_order_cancelled'] = sanitize_textarea_field($input['unify_sgc_sms_order_cancelled']);
			}
			if (isset($input['unify_sgc_sms_order_status_cancelled'])) {
				$new_input['unify_sgc_sms_order_status_cancelled'] = $input['unify_sgc_sms_order_status_cancelled'];
			}
			//refund
			if (isset($input['unify_sgc_sms_order_refunded'])) {
				$new_input['unify_sgc_sms_order_refunded'] = sanitize_textarea_field($input['unify_sgc_sms_order_refunded']);
			}
			if (isset($input['unify_sgc_sms_order_status_refunded'])) {
				$new_input['unify_sgc_sms_order_status_refunded'] = $input['unify_sgc_sms_order_status_refunded'];
			}
			//failed
			if (isset($input['unify_sgc_sms_order_failed'])) {
				$new_input['unify_sgc_sms_order_failed'] = sanitize_textarea_field($input['unify_sgc_sms_order_failed']);
			}
			if (isset($input['unify_sgc_sms_order_status_failed'])) {
				$new_input['unify_sgc_sms_order_status_failed'] = $input['unify_sgc_sms_order_status_failed'];
			}
			//registration
			if (isset($input['unify_sgc_sms_regi_status'])) {
				$new_input['unify_sgc_sms_regi_status'] = sanitize_textarea_field($input['unify_sgc_sms_regi_status']);
			}
			//profile
			if (isset($input['unify_sgc_sms_update_profile'])) {
				$new_input['unify_sgc_sms_update_profile'] = sanitize_textarea_field($input['unify_sgc_sms_update_profile']);
			}
			//forget pass
			if (isset($input['unify_sgc_sms_pass_reset'])) {
				$new_input['unify_sgc_sms_pass_reset'] = sanitize_textarea_field($input['unify_sgc_sms_pass_reset']);
			}
			//review
			if (isset($input['unify_sgc_sms_product_review_notification'])) {
				$new_input['unify_sgc_sms_product_review_notification'] = $input['unify_sgc_sms_product_review_notification'];
			}
			//coupon
			if (isset($input['unify_sgc_sms_coupon_announcement'])) {
				$new_input['unify_sgc_sms_coupon_announcement'] = sanitize_textarea_field($input['unify_sgc_sms_coupon_announcement']);
			}
			return $new_input;
		}

		/**
		 * Callback function for rendering the username input field in the plugin settings.
		 *
		 * This function generates the HTML markup for an input field where the administrator can enter the username for the unifysgcsms service.
		 * It retrieves and displays the currently stored username from the plugin options. Additionally, it provides a link for administrators
		 * to register for an account if they don't have one.
		 */
		public function unify_sgc_sms_userId_callback() {
			printf(
				'<input type="text" id="unify_sgc_sms_userId" name="unify_sgc_sms_option_name[unify_sgc_sms_userId]" size="50" value="%s" />', isset($this->options['unify_sgc_sms_userId']) ? esc_attr($this->options['unify_sgc_sms_userId']) : ''
			);
			printf('<span class="unify_sgc_sms_userId"><a href="https://unify.smsgateway.center" target="_blank">Click Here</a> to register if you do not have your login credentials.</span>');
		}

		/**
		 * Callback function for rendering the password input field in the plugin settings.
		 *
		 * Similar to the username callback, this function creates an input field for the password associated with the unifysgcsms service.
		 * It retrieves and displays the stored password, ensuring it's safely rendered using the esc_attr function to prevent security issues.
		 */
		public function unify_sgc_sms_password_callback() {
			printf(
				'<input type="password" id="unify_sgc_sms_password" name="unify_sgc_sms_option_name[unify_sgc_sms_password]" size="50" value="%s" />', isset($this->options['unify_sgc_sms_password']) ? esc_attr($this->options['unify_sgc_sms_password']) : ''
			);
		}

		/**
		 * Callback function for rendering the sender name input field in the plugin settings.
		 *
		 * This function provides an select field for selecting the sender name that has been registered with DLT Operators.
		 * The function fetches and displays the stored sender names from the plugin's settings. It also includes a brief note indicating
		 * the purpose of the field.
		 */
		public function unify_sgc_sms_sendername_callback() {
			$options = get_option('unify_sgc_sms_option_name');
			$userid = $options['unify_sgc_sms_userId'] ?? '';
			$password = $options['unify_sgc_sms_password'] ?? '';
			if ($userid != '') {
				$unifysgcsmsCls = new unifysgcsms($userid, $password, false);
				$sgcRawResponse = $unifysgcsmsCls->unifysgcsmsListSenderNames();
				if ($sgcRawResponse->response->status == 'success') {
					$senderNamesList = $sgcRawResponse->response->senderidList;
					printf('<select id="unify_sgc_sms_sendername" name="unify_sgc_sms_option_name[unify_sgc_sms_sendername]">');
					foreach ($senderNamesList as $sender) {
						//$selected = ($options['unify_sgc_sms_sendername'] == $sender->senderid->senderName) ? 'selected="selected"' : '';
						// Use printf for better readability
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr($sender->senderid->senderName),
							selected($options['unify_sgc_sms_sendername'], $sender->senderid->senderName, false),
							esc_html($sender->senderid->senderName)
						);
					}
					printf('</select>');
				} else {
					printf('<span class="text-red">Please input valid username and password to fetch your approved sender ids.</span>');
				}
			} else {
				printf('<span class="text-red">Please input valid username and password to fetch your approved sender ids.</span>');
			}
		}

		/**
		 * Callback function for the 'Enable Disable Admin Notifications setting.
		 *
		 * This function generates a checkbox in the plugin settings allowing administrators to enable or disable
		 * notifications for the admin. It checks the current state of this setting and displays the checkbox accordingly.
		 */
		public function unify_sgc_sms_admin_notify_callback() {
			printf(
				'<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_notify_admin]" type="checkbox" %2$s /> ', 'unify_sgc_sms_notify_admin', checked(isset($this->options['unify_sgc_sms_notify_admin']), true, false)
			);
		}

		/**
		 * Callback function for setting 'Admin Mobile Numbers.
		 *
		 * Generates an input field for administrators to enter mobile numbers where notifications should be sent. 
		 * The function retrieves and displays any currently stored numbers. It also provides instructions on how 
		 * to enter the numbers correctly, emphasizing the need for a country code prefix.
		 */
		public function unify_sgc_sms_admin_mobile_callback() {
			printf(
				'<input class="regular-text color" type="text" id="unify_sgc_sms_admin_mobile" name="unify_sgc_sms_option_name[unify_sgc_sms_admin_mobile]" size="50" value="%s" />', isset($this->options['unify_sgc_sms_admin_mobile']) ? esc_attr($this->options['unify_sgc_sms_admin_mobile']) : ''
			);
			printf('<span class="unify_sgc_sms_admin_mobile"> You can specify multiple mobiles numbers seperated by comma. <b>Country code prefix mandatory</b>.</span>');
		}

		/**
		 * Callback function for the 'Order Review Notification setting.
		 *
		 * Creates a checkbox allowing administrators to enable or disable notifications for product reviews.
		 * The state of the checkbox reflects whether this feature is currently enabled or disabled based on the
		 * stored plugin settings.
		 */
		public function unify_sgc_sms_order_review_callback() {
			printf(
				'<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_product_review_notification]" type="checkbox" %2$s /> ', 'unify_sgc_sms_product_review_notification', checked(isset($this->options['unify_sgc_sms_product_review_notification']), true, false)
			);
		}

		/**
		 * Callback function for configuring order status alert notifications.
		 *
		 * This function renders a series of checkboxes in the plugin settings, each corresponding to a different order status in WooCommerce.
		 * Administrators can use these checkboxes to enable or disable alert notifications for various order statuses.
		 *
		 * The function handles the following order statuses:
		 * - Completed: Notifications for when orders are completed.
		 * - Processing: Notifications for when orders are being processed.
		 * - Pending Payment: Notifications for orders that are pending payment.
		 * - On Hold: Notifications for orders that are on hold.
		 * - Cancelled: Notifications for cancelled orders.
		 * - Refunded: Notifications for refunded orders.
		 * - Failed: Notifications for failed orders.
		 *
		 * Each checkbox's state (checked or unchecked) reflects whether notifications for that particular order status are currently enabled.
		 * The function uses the WordPress checked() function to determine the checkbox state based on the stored plugin settings.
		 *
		 * Note:
		 * - This function is a part of the plugin's settings interface and is used to control the behavior of order-related notifications.
		 * - The 'checked' function ensures proper rendering of the checkbox state based on the corresponding option value.
		 */
		public function unify_sgc_sms_order_status_alert_callback() {
			//completed
			printf('<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_order_completed_status]" type="checkbox" %2$s /> ', 'unify_sgc_sms_order_completed_status', checked(isset($this->options['unify_sgc_sms_order_completed_status']), true, false));
			printf('<label>Completed</label><br/>');
			//processing
			printf('<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_order_status_processing]" type="checkbox" %2$s /> ', 'unify_sgc_sms_order_status_processing', checked(isset($this->options['unify_sgc_sms_order_status_processing']), true, false));
			printf('<label>Processing</label><br/>');
			//pending payment
			printf('<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_order_status_pending_payment]" type="checkbox" %2$s /> ', 'unify_sgc_sms_order_status_pending_payment', checked(isset($this->options['unify_sgc_sms_order_status_pending_payment']), true, false));
			printf('<label>Pending payment</label> <br/>');
			//on hold
			printf('<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_order_status_onhold]" type="checkbox" %2$s /> ', 'unify_sgc_sms_order_status_onhold', checked(isset($this->options['unify_sgc_sms_order_status_onhold']), true, false));
			printf('<label>On hold</label><br/>');
			//cancelled
			printf('<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_order_status_cancelled]" type="checkbox" %2$s /> ', 'unify_sgc_sms_order_status_cancelled', checked(isset($this->options['unify_sgc_sms_order_status_cancelled']), true, false));
			printf('<label>Cancelled</label><br/>');
			//refunded
			printf('<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_order_status_refunded]" type="checkbox" %2$s /> ', 'unify_sgc_sms_order_status_refunded', checked(isset($this->options['unify_sgc_sms_order_status_refunded']), true, false));
			printf('<label>Refunded</label><br/>');
			//failed
			printf('<input id="%1$s" name="unify_sgc_sms_option_name[unify_sgc_sms_order_status_failed]" type="checkbox" %2$s /> ', 'unify_sgc_sms_order_status_failed', checked(isset($this->options['unify_sgc_sms_order_status_failed']), true, false));
			printf('<label>Failed</label><br/>');
		}

		/**
		 * Provides a tip with default replaceable variable parameters for order notification configuration.
		 *
		 * This function returns an HTML string containing a table with various placeholders. These placeholders
		 * are used in the message templates for order notifications and can be dynamically replaced with actual
		 * order and customer details. The function aims to guide administrators on the available placeholders 
		 * they can use in their notification messages.
		 *
		 * The placeholders include:
		 * - {WOOCOM_SHOP_NAME}: The name of the WooCommerce shop.
		 * - {WOOCOM_ORDER_NUMBER}: The order number.
		 * - {WOOCOM_ORDER_STATUS}: The status of the order.
		 * - {WOOCOM_ORDER_AMOUNT}: The total amount of the order.
		 * - {WOOCOM_ORDER_DATE}: The date of the order.
		 * - {WOOCOM_ORDER_ITEMS}: The items in the order.
		 * - {WOOCOM_BILLING_FNAME}: The billing first name.
		 * - {WOOCOM_BILLING_LNAME}: The billing last name.
		 * - {WOOCOM_BILLING_EMAIL}: The billing email address.
		 * - {WOOCOM_CURRENT_DATE}: The current date.
		 * - {WOOCOM_CURRENT_TIME}: The current time.
		 *
		 * Note:
		 * - This function is typically used to display the available placeholders in the plugin's settings interface.
		 * - The returned HTML is structured as a table for clarity and ease of reading.
		 */
		public function unifysgcsms_message_content_text_tip() {
			return '<p class="mtip">'
				. '<span>Default Replace Variable params for all Order Notification Configuration:</span></p>'
				. '<table class="table table-bordered table table-hover" cellspacing="0"><tr><td>{WOOCOM_SHOP_NAME}<br />'
				. '{WOOCOM_ORDER_NUMBER}<br />'
				. '{WOOCOM_ORDER_STATUS}<br />'
				. '{WOOCOM_ORDER_AMOUNT}<br /></td><td>'
				. '{WOOCOM_ORDER_DATE}<br />'
				. '{WOOCOM_ORDER_ITEMS}<br />'
				. '{WOOCOM_BILLING_FNAME}<br />'
				. '{WOOCOM_BILLING_LNAME}<br /></td><td>'
				. '{WOOCOM_BILLING_EMAIL}<br />'
				. '{WOOCOM_CURRENT_DATE}<br />'
				. '{WOOCOM_CURRENT_TIME}</td></tr></table>';
		}

		/**
		 * Callback function for displaying the default available variables for message templates.
		 *
		 * This function calls `unifysgcsms_message_content_text_tip` to display a list of default variables that can be used
		 * in message templates. These variables are placeholders that get replaced with dynamic content in actual messages.
		 */
		public function unify_sgc_sms_default_variables_callback() {
			//printf($this->unifysgcsms_message_content_text_tip());
			$messageContent = $this->unifysgcsms_message_content_text_tip();
			echo wp_kses($messageContent, array(
				'p' => array(
					'class' => array(),
				),
				'span' => array(),
				'table' => array(
					'class' => array(),
					'border' => array(),
					'cellspacing' => array(),
				),
				'tr' => array(),
				'td' => array(),
				'br' => array(),
			));
		}

		/**
		 * Callback function for the 'Order Completed' notification template.
		 *
		 * This function includes the template file for the 'Order Completed' notification settings in the plugin and 
		 * displays a sample message. The sample message demonstrates how the default variables can be utilized in the template.
		 */
		public function unify_sgc_sms_order_completed_callback() {
			printf(
				'<textarea id="unify_sgc_sms_order_complete" name="unify_sgc_sms_option_name[unify_sgc_sms_order_complete]" cols="52" rows="3">%s</textarea>', isset($this->options['unify_sgc_sms_order_complete']) ? esc_attr($this->options['unify_sgc_sms_order_complete']) : ''
			);
			printf('<td><b>Sample:</b> Thank you for the purchase from {WOOCOM_SHOP_NAME}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We thank you for the purchase and we will deliver your merchandise at the earliest.</td>');
		}

		/**
		 * Callback function for the 'Order Processing notification template.
		 *
		 * Similar to the 'Order Completed' callback, this function includes the template file for the 'Order Processing' 
		 * notification settings. It displays a sample message that illustrates the use of default variables in the message template.
		 */
		public function unify_sgc_sms_order_processing_callback() {
			printf(
				'<textarea id="unify_sgc_sms_order_processing" name="unify_sgc_sms_option_name[unify_sgc_sms_order_processing]" cols="52" rows="3">%s</textarea>', isset($this->options['unify_sgc_sms_order_processing']) ? esc_attr($this->options['unify_sgc_sms_order_processing']) : ''
			);
			printf('<td><b>Sample:</b> Thank you for the purchase from {WOOCOM_SHOP_NAME}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We thank you for the purchase and we are processing your order for delivery.</td>');
		}

		/**
		 * Callback function for the 'Order Pending Payment' notification template.
		 *
		 * This function includes the template file for the 'Order Pending Payment' notification settings and displays a sample
		 * message. The sample message uses placeholders to show how dynamic content is integrated into the message template.
		 */
		public function unify_sgc_sms_order_pending_callback() {
			printf(
				'<textarea id="unify_sgc_sms_order_pending_payment" name="unify_sgc_sms_option_name[unify_sgc_sms_order_pending_payment]" cols="52" rows="3">%s</textarea>', isset($this->options['unify_sgc_sms_order_pending_payment']) ? esc_attr($this->options['unify_sgc_sms_order_pending_payment']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your payment is in pending status.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We hope you would process the payment to get your order delivered.</td>');
		}

		/**
		 * Callback function for the 'Order On Hold' notification template settings.
		 *
		 * This function includes the template file for configuring the 'Order On Hold' notification within the plugin. 
		 * It also displays a sample message showing how the default placeholders can be used to generate a dynamic message 
		 * when an order is put on hold. The sample message aims to provide administrators with a clear example of how the 
		 * notification will appear to the end user.
		 */
		public function unify_sgc_sms_order_onhold_callback() {
			printf(
				'<textarea id="unify_sgc_sms_order_onhold" name="unify_sgc_sms_option_name[unify_sgc_sms_order_onhold]" cols="52" rows="3">%s</textarea>', isset($this->options['unify_sgc_sms_order_onhold']) ? esc_attr($this->options['unify_sgc_sms_order_onhold']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your order has been put on {WOOCOM_ORDER_STATUS} status.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We will be in touch with you soon for more details.</td>');
		}

		/**
		 * Callback function for the 'Order Cancelled' notification template settings.
		 *
		 * Similar to the 'Order On Hold' callback, this function includes a template file for the 'Order Cancelled' notification settings.
		 * It displays a sample message utilizing placeholders to illustrate the structure and content of the notification message that will be
		 * sent when an order is cancelled. The sample message provides a clear demonstration of the template's application.
		 */
		public function unify_sgc_sms_order_cancelled_callback() {
			printf(
				'<textarea id="unify_sgc_sms_order_cancelled" name="unify_sgc_sms_option_name[unify_sgc_sms_order_cancelled]" cols="52" rows="3">%s</textarea>', isset($this->options['unify_sgc_sms_order_cancelled']) ? esc_attr($this->options['unify_sgc_sms_order_cancelled']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, we regret to inform you that your order has been {WOOCOM_ORDER_STATUS}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />Please get back to us for more details.</td>');
		}

		/**
		 * Callback function for the 'Order Refunded' notification template settings.
		 *
		 * This function includes the template file for the 'Order Refunded' notification settings and shows a sample message. 
		 * The sample message uses placeholders to demonstrate the customization and dynamic content replacement in the message template.
		 * It provides a practical example of how the notification message for refunded orders will be formatted and presented.
		 */
		public function unify_sgc_sms_order_refunded_callback() {
			printf(
				'<textarea id="unify_sgc_sms_order_refunded" name="unify_sgc_sms_option_name[unify_sgc_sms_order_refunded]" cols="52" rows="3">%s</textarea>', isset($this->options['unify_sgc_sms_order_refunded']) ? esc_attr($this->options['unify_sgc_sms_order_refunded']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, we have processed your refund of {WOOCOM_ORDER_AMOUNT}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />If you have any clarification, please get back to us.</td>');
		}

		/**
		 * Callback function for rendering the order failed alert template in the plugin settings.
		 *
		 * This function is used to display a predefined template for order failed alerts in the plugin's settings page.
		 * It allows administrators to view and understand the format of the alert message sent when an order fails.
		 * The function includes a sample template file and displays a sample message with placeholders.
		 *
		 * Available Placeholders in Sample:
		 * - {WOOCOM_BILLING_FNAME}: Billing first name.
		 * - {WOOCOM_ORDER_NUMBER}: Order number.
		 * - {WOOCOM_ORDER_DATE}: Order date.
		 *
		 * Note:
		 * - This function is part of the plugin's settings interface and is used for informational purposes to guide administrators on the message format.
		 */
		public function unify_sgc_sms_order_failed_callback() {
			printf(
				'<textarea id="unify_sgc_sms_order_failed" name="unify_sgc_sms_option_name[unify_sgc_sms_order_failed]" cols="52" rows="3">%s</textarea>', isset($this->options['unify_sgc_sms_order_failed']) ? esc_attr($this->options['unify_sgc_sms_order_failed']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your order has been Failed for some technical reasons.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We request you to re-order again to get the order delivered.</td>');
		}

		/**
		 * Callback function for rendering the new registration alert textarea in the plugin settings.
		 *
		 * This function displays a textarea for administrators to customize the message template for new registration alerts.
		 * It retrieves the current setting value and shows available placeholders that can be used in the message.
		 *
		 * Available Placeholders:
		 * - {WOOCOM_FIRST_NAME}: The first name of the newly registered user.
		 * - {WOOCOM_LAST_NAME}: The last name of the newly registered user.
		 *
		 * Note:
		 * - The function is typically called as a part of the WordPress Settings API for rendering form fields.
		 */
		public function unify_sgc_sms_user_regi_status_callback() {
			printf(
				'<textarea id="unify_sgc_sms_regi_status" name="unify_sgc_sms_option_name[unify_sgc_sms_regi_status]" cols="52" rows="3">%s</textarea><p class="mtip"><span>Available Variable:</span> {WOOCOM_FIRST_NAME}, {WOOCOM_LAST_NAME}</p>', isset($this->options['unify_sgc_sms_regi_status']) ? esc_attr($this->options['unify_sgc_sms_regi_status']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Welcome to {WOOCOM_SHOP_NAME} Club. Shop the best of the products and get the best experience. If you face any issue, we are just a call away. Call us on +91999XXXXXXX</td>');
		}

		/**
		 * Callback function for rendering the update profile alert textarea in the plugin settings.
		 *
		 * Similar to the new registration callback, this function displays a textarea for setting the message template for profile update alerts.
		 * It allows the administrator to define a custom message for notifying users about successful profile updates.
		 *
		 * Available Placeholders:
		 * - {WOOCOM_FIRST_NAME}: The first name of the user whose profile has been updated.
		 * - {WOOCOM_LAST_NAME}: The last name of the user whose profile has been updated.
		 *
		 * Note:
		 * - This function is part of the plugin's settings interface, used to allow administrators to customize notification messages.
		 */
		public function unify_sgc_sms_user_update_profile_callback() {
			printf(
				'<textarea id="unify_sgc_sms_update_profile" name="unify_sgc_sms_option_name[unify_sgc_sms_update_profile]" cols="52" rows="3">%s</textarea><p class="mtip"><span>Available Variable:</span> {WOOCOM_FIRST_NAME}, {WOOCOM_LAST_NAME}</p>', isset($this->options['unify_sgc_sms_update_profile']) ? esc_attr($this->options['unify_sgc_sms_update_profile']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your profile has been updated successfully at {WOOCOM_SHOP_NAME} site.</td>');
		}

		/**
		 * Callback function for rendering the password reset alert textarea in the plugin settings.
		 *
		 * This function is used to display a textarea input in the plugin's settings page for configuring the
		 * message template used for password reset alerts. It allows administrators to set a custom message
		 * that includes specific placeholders for dynamic content.
		 *
		 * The function retrieves the current setting value (if set) and displays it in the textarea. It also
		 * provides information about available placeholders that can be used in the message template.
		 *
		 * Available Placeholders:
		 * - {WOOCOM_FIRST_NAME}: The first name of the customer.
		 * - {WOOCOM_LAST_NAME}: The last name of the customer.
		 *
		 * Note:
		 * - This function is typically called as a part of the WordPress Settings API for rendering form fields.
		 */
		public function unify_sgc_sms_password_reset_callback() {
			printf(
				'<textarea id="unify_sgc_sms_pass_reset" name="unify_sgc_sms_option_name[unify_sgc_sms_pass_reset]" cols="52" rows="3">%s</textarea><p class="mtip"><span>Available Variable:</span> {WOOCOM_FIRST_NAME}, {WOOCOM_LAST_NAME}</p>', isset($this->options['unify_sgc_sms_pass_reset']) ? esc_attr($this->options['unify_sgc_sms_pass_reset']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your password has been reset successfully at {WOOCOM_SHOP_NAME} site.</td>');
		}

		/**
		 * Callback function for rendering the coupon announcement textarea in the plugin settings.
		 *
		 * Similar to the password reset callback, this function displays a textarea for setting the message
		 * template for coupon announcements. It allows the administrator to define a custom message for
		 * notifying customers about new coupons or sales.
		 *
		 * The function retrieves and displays the currently set message, if any. It also indicates that there
		 * are no dynamic variables available for this particular message template.
		 *
		 * Note:
		 * - This function is part of the plugin's settings interface, used to allow administrators to customize
		 *   notification messages.
		 */
		public function unify_sgc_sms_coupon_announcement_callback() {
			printf(
				'<textarea id="unify_sgc_sms_coupon_announcement" name="unify_sgc_sms_option_name[unify_sgc_sms_coupon_announcement]" cols="52" rows="3">%s</textarea><p class="mtip">No Available Variables for this content.</p>', isset($this->options['unify_sgc_sms_coupon_announcement']) ? esc_attr($this->options['unify_sgc_sms_coupon_announcement']) : ''
			);
			printf('<td><b>Sample:</b> {WOOCOM_SHOP_NAME}: Dear {WOOCOM_BILLING_FNAME},<br />Biggest Sale. Get 10&#37; Discount on all our products. Use Coupon Code ABC10. Text STOP to unsubscribe to 91999XXXXXXX.</td>');
		}

		/**
		 * Renders the settings page for the unifysgcsms plugin in the WordPress admin interface.
		 *
		 * This function is responsible for displaying the settings page where administrators can configure options for the
		 * unifysgcsms plugin. It retrieves the current settings stored in the WordPress database and uses the 
		 * WordPress Settings API to generate the settings form.
		 *
		 * Process:
		 * - Retrieves the plugin options from the WordPress database using get_option().
		 * - Displays a form that allows administrators to change plugin settings.
		 * - The form uses the WordPress Settings API to manage the submission and storage of settings.
		 *
		 * The settings form includes various fields defined in the plugin's settings sections and fields. When the form is submitted,
		 * the settings are saved using WordPress's options mechanism.
		 *
		 * Note:
		 * - The 'settings_fields' function generates hidden input fields that are required for form submission.
		 * - The 'do_settings_sections' function outputs the settings sections and fields for the specified page.
		 * - 'submit_button' is a WordPress helper function that outputs a submit button for the form.
		 * - This function should be called in the context of rendering admin pages, typically hooked to WordPress admin menu actions.
		 */
		public function unify_sgc_sms_page() {
			$unifySGCSMSid = 'unify_sgc_sms_settings';
			$this->options = get_option('unify_sgc_sms_option_name');
			?>
			<div class="wrap">
				<div class="unify_sgc_sms_top_image_logo"></div>
				<h2>Unify SMS Gateway Center  Settings</h2>
				<form method="post" action="options.php">
					<?php
					settings_fields('unify_sgc_sms_option_group');
					do_settings_sections('unify_sgc_sms_settings');
					submit_button();
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Renders the Delivery Report Logs page for the unifysgcsms in the admin interface.
		 *
		 * This function handles the display and functionality of the Delivery Report Logs page. It allows
		 * administrators to view delivery reports for sent messages. The function supports filtering reports
		 * by date and mobile number. It includes a form for setting search criteria and displays the results in a table.
		 * 
		 * Functionality:
		 * - Verifies the nonce for security purposes.
		 * - Handles search filter input (from date, to date, mobile number) from GET request.
		 * - Checks for current user's capability to manage options.
		 * - Displays a form for setting search filters.
		 * - Fetches delivery reports based on filter criteria using `unifysgcsmsListReports` method from `unifysgcsms` class.
		 * - Renders the fetched reports in a table with pagination.
		 * - Uses JavaScript to populate the table with data and handle pagination.
		 *
		 * Note:
		 * - Ensures that only users with 'manage_options' capability (typically administrators) can access this page.
		 * - The script tags within the function use PHP to pass PHP variables to JavaScript.
		 * - The `unifysgcsmsListReports` method is expected to return data in a specific format to be rendered correctly.
		 * - Nonce verification and capability check are crucial for security and access control.
		 */
		public function unify_sgc_sms_dlr_page() {
			if (!isset($_GET['admin_unifysgcsms_woo_search_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['admin_unifysgcsms_woo_search_nonce'])), 'admin_unifysgcsms_woo_search_nonce')) {
				$fromDateValue = '';
				$toDateValue = '';
				$mobileNo = '';
			} else {
				$fromDateValue = isset($_GET['fromDate']) ? sanitize_text_field($_GET['fromDate']) : '';
				$toDateValue = isset($_GET['toDate']) ? sanitize_text_field($_GET['toDate']) : '';
				$mobileNo = isset($_GET['mobileNo']) ? sanitize_text_field($_GET['mobileNo']) : '';
			}
			add_action('_wp_http_referer', '_wp_http_referer');
			if (!current_user_can('manage_options')) {
				wp_die(__('You do not have sufficient permissions to access this page.', 'unify_sgc_sms'));
			}
			$nonce = wp_create_nonce('admin_unifysgcsms_woo_search_nonce');
			?>
			<div class="wrap">
				<div class="unify_sgc_sms_top_image_logo"></div>
				<h1>Delivery Report Logs</h1>
				<p class="color-333">Delivery Report can be fetched for the current day only. You can login to unify.smsgateway.center Panel and download historical data in zip format.</p>
				<form role="search" method="get" id="unifysgcsms_dlr_search" action="<?php echo esc_url(admin_url('admin.php?page=unify_sgc_sms_dlr_page')); ?>">
					<div class="">
						<label class="unifysgcsms-inline-label unifysgcsms-searchLabel" for="mainLabel"><?php esc_html_e('Search', 'unify_sgc_sms'); ?></label>
						<label class="unifysgcsms-inline-label" for="fromDate"><?php esc_html_e('From:', 'unify_sgc_sms'); ?></label>
						<input type="date" class="unifysgcsms-inline-input" value="<?php echo esc_attr($fromDateValue); ?>" name="fromDate" id="fromDate" />
						<label class="unifysgcsms-inline-label" for="toDate"><?php esc_html_e('To:', 'unify_sgc_sms'); ?></label>
						<input type="date" class="unifysgcsms-inline-input" value="<?php echo esc_attr($toDateValue); ?>" name="toDate" id="toDate" />
						<label class="unifysgcsms-inline-label" for="mobileNo"><?php esc_html_e('Mobile:', 'unify_sgc_sms'); ?></label>
						<input type="number" class="unifysgcsms-inline-input" value="<?php echo esc_attr($mobileNo); ?>" name="mobileNo" id="mobileNo" />
						<input type="hidden" name="admin_unifysgcsms_woo_search_nonce" value="<?php echo esc_attr(wp_create_nonce('admin_unifysgcsms_woo_search_nonce')); ?>" />
						<input type="hidden" name="page" value="unify_sgc_sms_dlr_page"/>
						<input type="submit" id="unifysgcsms-searchsubmit" class="button" value="<?php echo esc_attr__('Filter', 'unify_sgc_sms'); ?>" />
					</div>
				</form>
				<?php
				if (is_admin() && current_user_can('manage_options')) {
					$options = get_option('unify_sgc_sms_option_name');
					$unifyapi = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], false);

					$jsonDecode = $unifyapi->unifysgcsmsListReports($fromDateValue, $toDateValue, $mobileNo);
					if (!empty($jsonDecode->response->msg) == 'success') {
						$messages = $jsonDecode->response->reports_dlrList;
					} else {
						$messages = [0 => 'none'];
					}

					wp_enqueue_script('unifysgcsms_message_log_script', UNIFY_SGC_SMS_GATEWAY_URL . '/assets/js/messageLog.js', array('jquery'), time(), true);

					// Pass PHP data to JavaScript
					wp_localize_script('unifysgcsms_message_log_script', 'unifysgcsms_data', array(
						'messages' => $messages,
					));
					?>
					<table id="messages" class="table table-bordered table table-hover" cellspacing="0" width="100%">
						<colgroup><col><col><col></colgroup>
						<thead>
							<tr>
								<th>SenderID</th>
								<th>Mobile</th>
								<th>Text</th>
								<th>UUID</th>
								<th>Channel</th>
								<th>Cost</th>
								<th>MsgType</th>
								<th>Status</th>
								<th>Cause</th>
								<th>Charges</th>
								<th>Delivered Time</th>
							</tr>
						</thead>
						<tbody id="dlrData">
						</tbody>
					</table>
					<div id="pager">
						<ul id="pagination" class="pagination-sm"></ul>
					</div>
				</body>
				</div>
				<?php
			}
		}

		/**
		 * Manages the subscribers page in the unifysgcsms admin interface.
		 *
		 * This function orchestrates the display and management of  alert subscribers.
		 * It includes functionality for adding, editing, and importing subscribers, as well as displaying the list of existing subscribers.
		 * Based on the 'action' parameter in the GET request, the function decides which operation to perform or which view to display.
		 *
		 * Actions Handled:
		 * - 'Add Subscriber': When the 'action' parameter is 'add', the function includes the template for adding a new subscriber.
		 *   If the form for adding a subscriber is submitted (checked via POST request), it calls the 'add_subscriber' method 
		 *   from the subscription management object and displays a notice with the result.
		 * - 'Edit Subscriber': When the 'action' parameter is 'edit', the function includes the template for editing an existing subscriber.
		 *   If the form for updating a subscriber is submitted, it calls the 'update_subscriber' method and displays a notice with the result.
		 * - 'Import Subscriber': When the 'action' parameter is 'import', it includes the template for importing subscribers, suggesting the use
		 *   of a CSV import plugin.
		 * - Default View: If no specific action is defined, it displays the list of subscribers using the UNIFYSGCSMS_ALERTS_Subscribers_List_Table class.
		 *
		 * Note:
		 * - This function should only be invoked within the admin context as it directly includes PHP files for rendering admin templates.
		 * - It assumes the existence of certain template files in the 'includes/templates/subscribe/' directory and a class file for managing 
		 *   the subscribers list table.
		 * - Proper user permissions should be checked before allowing any operations on subscribers.
		 */
		public function unify_sgc_sms_subscribers() {
			if (isset($_GET['action'])) {
				// Add New subscriber
				if ($_GET['action'] == 'add') {
					// Include template and handle form submission for adding a subscriber
					echo '<div class="unify_sgc_sms_top_image_logo"></div>';
					$nonce_field = wp_nonce_field('unifysgcsms_add_subscribe_nonce', 'unifysgcsms_add_subscribe_nonce_field', true, true);
					include_once dirname(__FILE__) . "/includes/templates/subscribe/add-subscriber.php";

					if (isset($_POST['unifysgcsms_add_subscribe']) && wp_verify_nonce(sanitize_text_field($_POST['unifysgcsms_add_subscribe_nonce_field']), 'unifysgcsms_add_subscribe_nonce')) {
						// Sanitize input data
						$name = sanitize_text_field($_POST['unifysgcsms_notify_subscribe_name']);
						$mobile = sanitize_text_field($_POST['unifysgcsms_notify_subscribe_mobile']);
						$group_name = sanitize_text_field($_POST['unifysgcsms_notify_group_name']);
						$result = $this->subscribe->add_subscriber($name, $mobile, $group_name);
						echo wp_kses_post($this->unifysgcsms_show_notice($result['result'], $result['message']));
					}
					return;
				}

				// Update subscriber
				if ($_GET['action'] == 'edit') {
					echo '<div class="unify_sgc_sms_top_image_logo"></div>';
					$update_nonce_field = wp_nonce_field('unifysgcsms_update_subscribe_nonce', 'unifysgcsms_update_subscribe', true, false);
					$nonce_field = wp_nonce_field('unifysgcsms_update_subscribe_nonce', 'unifysgcsms_update_subscribe_nonce_field', true, true);
					$get_subscribe = $this->subscribe->get_subscriber(absint($_GET['ID']));
					include_once dirname(__FILE__) . "/includes/templates/subscribe/edit-subscriber.php";

					if (isset($_POST['unifysgcsms_update_subscribe']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['unifysgcsms_update_subscribe_nonce_field'])), 'unifysgcsms_update_subscribe_nonce')) {
						$result = $this->subscribe->update_subscriber(
							absint($_GET['ID']),
							sanitize_text_field($_POST['unifysgcsms_notify_subscribe_name']),
							sanitize_text_field($_POST['unifysgcsms_notify_subscribe_mobile']),
							sanitize_text_field($_POST['unifysgcsms_notify_group_name'])
						);
						echo wp_kses_post($this->unifysgcsms_show_notice($result['result'], $result['message']));
					}
					return;
				}

				// Import subscriber CSV
				if ($_GET['action'] == 'import') {
					echo '<div class="unify_sgc_sms_top_image_logo"></div>';
					include_once dirname(__FILE__) . "/includes/templates/subscribe/import-subscriber.php";
					return;
				}
			}

			// Default view: Display list of subscribers
			include_once dirname(__FILE__) . '/includes/class-unifysgcsms-subscribers-table.php';
			//Create an instance of our package class...
			$list_table = new UNIFYSGCSMS_ALERTS_Subscribers_List_Table();
			//Fetch, prepare, sort, and filter our data...
			$list_table->prepare_items();
			include_once dirname(__FILE__) . "/includes/templates/subscribe/subscribers.php";
		}

		/**
		 * Handles the subscriber groups page in the unifysgcsms admin interface.
		 *
		 * This function provides the functionality for adding, editing, and managing subscriber groups. It checks the 'action' parameter
		 * in the GET request to determine the appropriate action (add or edit a group) and includes the respective template file for rendering
		 * the interface. It also interacts with a subscription management object to perform add or update operations on the groups.
		 *
		 * Functionality:
		 * 1. 'Add Group': If the 'action' parameter is set to 'add', it includes the template for adding a new group. If the form
		 *    for adding a group is submitted (checked via POST request), it calls the 'add_group' method of the subscription management
		 *    object and displays a notice based on the result.
		 * 2. 'Edit Group': If the 'action' parameter is set to 'edit', it includes the template for editing an existing group. If the form
		 *    for updating a group is submitted, it calls the 'update_group' method and displays a notice based on the result.
		 * 3. Default View: If no specific action is defined, it displays the list of groups using the UNIFYSGCSMS_ALERTS_Subscribers_Groups_List_Table class.
		 *
		 * Note:
		 * - This function should only be called within the admin context as it directly includes PHP files for rendering admin templates.
		 * - The function assumes the existence of certain template files in the 'includes/templates/subscribe/' directory and a class file
		 *   for managing the groups list table.
		 * - Proper user permissions should be checked before allowing any add or edit operations on the groups.
		 */
		public function unify_sgc_sms_subscriber_groups() {
			if (isset($_GET['action'])) {

				// Add group page
				if ($_GET['action'] == 'add') {
					echo '<div class="unify_sgc_sms_top_image_logo"></div>';
					$nonce_field = wp_nonce_field('unifysgcsms_add_group_nonce', 'unifysgcsms_add_group_nonce_field', true, true);
					include_once dirname(__FILE__) . "/includes/templates/subscribe/add-group.php";
					if (isset($_POST['unifysgcsms_add_group']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['unifysgcsms_add_group_nonce_field'])), 'unifysgcsms_add_group_nonce')) {
						$result = $this->subscribe->add_group(sanitize_text_field($_POST['unifysgcsms_notify_group_name']));
						echo wp_kses_post($this->unifysgcsms_show_notice($result['result'], $result['message']));
					}
					return;
				}

				// Update group page
				if ($_GET['action'] == 'edit') {
					$nonce_field = wp_nonce_field('unifysgcsms_update_group_nonce', 'unifysgcsms_update_group_nonce_field', true, true);
					if (isset($_POST['unifysgcsms_update_group']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['unifysgcsms_update_group_nonce_field'])), 'unifysgcsms_update_group_nonce')) {
						$result = $this->subscribe->update_group(
							absint($_GET['ID']),
							sanitize_text_field($_POST['unifysgcsms_notify_group_name'])
						);
						echo wp_kses_post($this->unifysgcsms_show_notice($result['result'], $result['message']));
					}
					$get_group = $this->subscribe->get_group(absint($_GET['ID']));
					echo '<div class="unify_sgc_sms_top_image_logo"></div>';
					include_once dirname(__FILE__) . "/includes/templates/subscribe/edit-group.php";
					return;
				}
			}

			// Display the main groups management page
			include_once dirname(__FILE__) . '/includes/class-unifysgcsms-groups-table.php';

			// Create an instance of the groups list table class
			$list_table = new UNIFYSGCSMS_ALERTS_Subscribers_Groups_List_Table();

			// Fetch, prepare, sort, and filter the data for the list table
			$list_table->prepare_items();
			include_once dirname(__FILE__) . "/includes/templates/subscribe/groups.php";
		}

		/**
		 * Generates HTML for admin notices based on the result status and provided message.
		 *
		 * This function is responsible for creating and returning the HTML markup for displaying admin notices in the WordPress dashboard.
		 * It supports different types of notices based on the result status (e.g., error, update) and displays the provided message within the notice.
		 * The notices are styled according to WordPress admin styles and include a dismiss button.
		 *
		 * @param string $result The result status that determines the type of notice to display. Supported values are 'error' and 'update'.
		 * @param string $message The message text to be displayed inside the notice.
		 *
		 * @return string|null The HTML markup for the admin notice if the result is not empty, or null if the result is empty.
		 *
		 * Note:
		 * - If $result is 'error', the function returns an HTML string for an error notice.
		 * - If $result is 'update', the function returns an HTML string for an update notice.
		 * - The function returns null if $result is empty, indicating that no notice should be displayed.
		 * - The notices include a dismiss button, allowing users to close the notice.
		 * - The text domain 'unify_sgc_sms' is used for localization of the 'Close' button text.
		 */
		public function unifysgcsms_show_notice($result, $message) {
			if (empty($result)) {
				return;
			}
			if ($result == 'error') {
				return '<div class="updated settings-error notice error is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __('Close', 'unify_sgc_sms') . '</span></button></div>';
			}
			if ($result == 'update') {
				return '<div class="updated settings-update notice is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __('Close', 'unify_sgc_sms') . '</span></button></div>';
			}
		}
	}

	/**
	 * Initializes the unifysgcsms plugin within the WordPress admin area and includes necessary class files.
	 *
	 * This script checks if the current context is the WordPress admin area. If so, it creates an instance of the unifysgcsmsAlerts
	 * class, which likely initializes settings or functionalities specific to the admin dashboard. Additionally, it includes
	 * several PHP class files necessary for the plugin's functionality.
	 *
	 * Context Check:
	 * - is_admin(): Checks if the current request is for an administrative interface page. If true, it implies that the
	 *   script is running within the WordPress admin dashboard.
	 *
	 * Class Instantiation:
	 * - unifysgcsmsAlerts: This class is likely responsible for setting up the plugin's admin settings page and related functionalities.
	 *   An instance of this class is created when the script is executed within the admin context.
	 *
	 * Class File Inclusions:
	 * - waabaapi_user.class.php: Includes the class file that possibly handles user-related functionalities or data structures.
	 * - unify_woo_order.class.php: Includes the class file that likely deals with WooCommerce order interactions or modifications.
	 * - unifysgcsms.class.php: Includes the main class file for the unifysgcsms plugin, which might contain core functionalities.
	 *
	 * Constants:
	 * - UNIFYSGCSMSI_WOOCOM_ALERTS_DIR: Presumably a defined constant that provides the directory path where the plugin's files are located.
	 *
	 * Note:
	 * - This code should be placed in a main plugin file or in a file that's included during the plugin's initialization.
	 * - The use of `require_once` ensures that each class file is included only once to avoid redeclaration errors.
	 */
	if (is_admin()) {
		$my_settings_page = new unifySGCSMSAlerts();
	}

	/**
	 * Checks if WooCommerce is active and handles plugin deactivation if not.
	 *
	 * This function is hooked to 'admin_init'. It checks if WooCommerce is active and if not,
	 * it deactivates the unifysgcsms plugin. This is particularly important for
	 * cases where the functionality of the unifysgcsms plugin depends on WooCommerce. The function
	 * also sets a transient option to display an admin notice informing the user about the
	 * deactivation and redirects to the plugins page.
	 */
	if (!function_exists('unifysgcsms_check_woocommerce_active')) {
		add_action('admin_init', 'unifysgcsms_check_woocommerce_active');

		function unifysgcsms_check_woocommerce_active() {
			if (!function_exists('is_plugin_active')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			// Lets check if admin comes to any of our plugin pages then deactivate the plugin as woocommerce is not active
			$plugin_pages = array('unify_sgc_sms', 'unify_sgc_sms_dlr_page', 'unify_sgc_sms_subscribers', 'unify_sgc_sms_subscriber_groups');

			if (isset($_GET['page']) && in_array($_GET['page'], $plugin_pages) && !is_plugin_active('woocommerce/woocommerce.php')) {
				if (!is_plugin_active('woocommerce/woocommerce.php')) {
					//Lets deactivate the plugin as woocommerce is not active
					deactivate_plugins(plugin_basename(__FILE__));

					// Set a transient to show the admin notice
					update_option('unifysgcsms_woo_inactive_notice', '1');
					// Redirect to plugins page
					wp_redirect(admin_url('plugins.php'));
					exit;
				}
			}
		}

	}

	/**
	 * Displays an admin notice if WooCommerce is not active.
	 *
	 * This function checks if a specific option ('unifysgcsms_woo_inactive_notice') is set to '1', 
	 * which indicates that WooCommerce is not active and the unifysgcsms plugin 
	 * requires it. If so, it displays an error notice in the WordPress admin area informing the 
	 * user that WooCommerce needs to be installed and active for the plugin to work. After 
	 * displaying the notice, it deletes the option to ensure the notice only appears once.
	 */
	if (!function_exists('unifysgcsms_woocommerce_not_active_notice')) {

		function unifysgcsms_woocommerce_not_active_notice() {
			if (get_option('unifysgcsms_woo_inactive_notice') === '1') {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e('WooSMSConnect Unify SMS Gateway Center requires WooCommerce to be installed and active. The plugin has been deactivated.', 'unify_sgc_sms'); ?></p>
				</div>
				<?php
				// Delete the transient so that the notice only displays once
				delete_option('unifysgcsms_woo_inactive_notice');
			}
		}

		add_action('admin_notices', 'unifysgcsms_woocommerce_not_active_notice');
	}

	require_once( UNIFY_SGC_SMS_DIR . '/core/unifysgcsms_user.class.php' );
	require_once( UNIFY_SGC_SMS_DIR . '/core/unify_woo_order.class.php' );
	require_once( UNIFY_SGC_SMS_DIR . '/core/unifysgcsms.class.php' );

	if (!function_exists('unifysgcsms_plugin_page_settings_link')) {
		/**
		 * Add settings link to the plugin page.
		 *
		 * This filter adds a settings link to the plugin page in the WordPress admin area.
		 *
		 * @param array  $actions      An array of plugin action links.
		 * @param string $plugin_file  Path to the plugin file.
		 * @param array  $plugin_data  Plugin data.
		 * @param string $context      The plugin context. Usually, this is the plugin file path.
		 * @param string $network      Whether the plugin resides in the plugins folder for a network-activated installation.
		 *
		 * @return array Modified array of plugin action links.
		 */
		add_filter('plugin_action_links', 'unifysgcsms_plugin_page_settings_link', 10, 5);

		/**
		 * Adds custom links to the plugin's action links on the WordPress Plugins page.
		 *
		 * This function appends additional action links to the existing links for the unify.smsgateway.center plugin on the WordPress Plugins page.
		 * It adds links for 'Settings' and 'Support', allowing quick access to the plugin's settings page and external support page.
		 *
		 * @param array $actions Existing action links for the plugin.
		 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
		 *
		 * @return array Modified array of action links for the plugin.
		 *
		 * Process:
		 * - Checks if the function is processing the correct plugin based on the $plugin_file parameter.
		 * - If the plugin is matched, it creates 'Settings' and 'Support' links:
		 *   - 'Settings' link redirects to the plugin's settings page within the WordPress admin area.
		 *   - 'Support' link redirects to the external website 'https://unify.smsgateway.center' for support.
		 * - These links are then merged with the existing action links for the plugin.
		 *
		 * Note:
		 * - The function is designed to work only with the unify.smsgateway.center plugin.
		 * - The 'Settings' link is only added if the current user has the appropriate permissions to access the plugin settings.
		 * - The function uses `plugin_basename(__FILE__)` to get the plugin's basename and compare it with the current plugin being processed.
		 * - This function should ideally be hooked to the 'plugin_action_links_' . plugin_basename(__FILE__) filter in WordPress.
		 */
		function unifysgcsms_plugin_page_settings_link($actions, $plugin_file) {
			static $plugin;

			if (!isset($plugin)) {
				$plugin = plugin_basename(__FILE__);
			}
			if ($plugin == $plugin_file) {

				$wpsettingsurl = esc_url(add_query_arg(
						'page', 'unify_sgc_sms', get_admin_url() . 'admin.php'
				));
				$sgcsitesurl = esc_url(add_query_arg(
						'', '', 'https://unify.smsgateway.center'
				));
				$settings_link = "<a href='$wpsettingsurl'>" . __('Settings') . '</a>';

				$settings = array('settings' => $settings_link);
				$site_link = array('support' => '<a href="' . $sgcsitesurl . '" target="_blank">' . __('Support') . '</a>');

				$actions = array_merge($settings, $actions);
				$actions = array_merge($site_link, $actions);
			}
			return $actions;
		}

	}

	if (!function_exists('unify_sgc_sms_install')) {
		/**
		 * Register activation hook for plugin installation.
		 *
		 * This code registers the 'unify_sgc_sms_install' function to be executed when the plugin is activated.
		 * It is typically used to perform one-time setup tasks, such as creating database tables or setting default options.
		 */
		register_activation_hook(__FILE__, 'unify_sgc_sms_install');

		/**
		 * Installs the unify.smsgateway.center API  plugin.
		 *
		 * This function sets up the necessary database tables when the plugin is installed. It creates tables
		 * for storing subscribers and subscriber groups. The function uses the WordPress Database Access Abstraction Object ($wpdb)
		 * to interact with the database and ensure the correct charset and collation are used.
		 *
		 * Global Variables:
		 * - $wpdb (wpdb): Global WordPress database object used for direct database queries.
		 *
		 * Tables Created:
		 * 1. unify_sgc_sms_subscribers: Stores information about subscribers including ID, date, name, mobile number, group ID.
		 *    - ID: Primary key, auto-increment integer.
		 *    - date: Date and time of subscription.
		 *    - name: Name of the subscriber.
		 *    - mobile: Mobile number of the subscriber.
		 *    - group_ID: Group ID the subscriber belongs to.
		 *
		 * 2. unify_sgc_sms_subscribers_group: Stores information about subscriber groups including ID and name.
		 *    - ID: Primary key, auto-increment integer.
		 *    - name: Name of the group.
		 *
		 * Note:
		 * - The function uses dbDelta function for creating tables which is a safer way to create tables in WordPress as it
		 *   allows the function to be run multiple times without causing error or duplicating tables.
		 * - Ensure that this function is only called during the plugin installation process.
		 * - The commented 'status' field in the subscribers table schema can be uncommented and utilized if needed.
		 */
		function unify_sgc_sms_install() {
			global $wpdb;
			if (is_plugin_active('woocommerce/woocommerce.php')) {
				// WooCommerce is active, we can allow users to install our plugin
			} else {
				// WooCommerce is not active, handle the scenario appropriately
				wp_die(__('WooSMSConnect Unify SMS Gateway Center requires WooCommerce to be installed and active.', 'unify_sgc_sms'), 'Plugin dependency check', array('back_link' => true));
			}
			$charset_collate = $wpdb->get_charset_collate();

			// SQL for creating the subscribers table
			$sql_subscribe = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}unify_sgc_sms_subscribers(
			ID int(10) NOT NULL auto_increment,
			date DATETIME,
			name VARCHAR(20),
			mobile VARCHAR(20) NOT NULL,
			/*status tinyint(1),*/
			group_ID int(5),
			PRIMARY KEY(ID)) CHARSET=utf8";

			// SQL for creating the subscribers group table
			$sql_group = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}unify_sgc_sms_subscribers_group(
			ID int(10) NOT NULL auto_increment,
			name VARCHAR(250),
			PRIMARY KEY(ID)) CHARSET=utf8";

			// Include the WordPress upgrade script and run dbDelta to create/update tables
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta($sql_subscribe);
			dbDelta($sql_group);
		}

	}



	if (!function_exists('unifysgcsms_uninstall')) {
		/**
		 * Registers a function to be run when the plugin is uninstalled.
		 *
		 * The 'register_uninstall_hook' function is used to specify a callback function that will be executed
		 * when the plugin is uninstalled from the WordPress site. This is useful for performing cleanup tasks,
		 * like removing database tables or options that the plugin created during its operation.
		 *
		 * @param string __FILE__ A constant referring to the main plugin file, which uniquely identifies the plugin.
		 * @param string 'unifysgcsms_uninstall' The name of the function to be executed upon plugin uninstallation.
		 *
		 * Note:
		 * - The 'unifysgcsms_uninstall' function should handle all necessary cleanup to prevent leaving 
		 *   residual data in the WordPress installation after the plugin is removed.
		 * - This hook does not trigger on plugin deactivation, only on uninstallation.
		 */
		register_uninstall_hook(__FILE__, 'unifysgcsms_uninstall');

		/**
		 * Uninstalls the UnifySMSGatewayCenter API  Alerts plugin.
		 *
		 * This function performs the necessary cleanup when the plugin is uninstalled. It includes
		 * dropping database tables related to the plugin and deleting options set by the plugin.
		 * Specifically, it removes the subscribers and subscribers group tables and the database version option.
		 *
		 * Global Variables:
		 * - $wpdb (wpdb): Global WordPress database object used for direct database queries.
		 *
		 * Operations Performed:
		 * 1. Drops the 'unify_sgc_sms_subscribers' table if it exists.
		 * 2. Drops the 'unify_sgc_sms_subscribers_group' table if it exists.
		 * 3. Deletes the 'unify_sgc_sms_db_version' option from the WordPress options table.
		 *
		 * Note:
		 * - This function should only be called upon plugin uninstallation.
		 * - It directly manipulates the database to remove the tables, so it should be used with caution.
		 * - Ensure that this function is only accessible to authorized users, typically administrators,
		 *   to prevent unintended data loss.
		 */
		function unifysgcsms_uninstall() {
			global $wpdb;
			// Drop the subscribers table
			$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'unify_sgc_sms_subscribers');
			// Drop the subscribers group table
			$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'unify_sgc_sms_subscribers_group');
			// Delete the database version option
			delete_option("unify_sgc_sms_db_version");
		}

	}