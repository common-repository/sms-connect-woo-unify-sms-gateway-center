<?php
	if (!defined('ABSPATH'))
		exit;

	include_once (UNIFY_SGC_SMS_DIR . '/core/unifysgcsms.class.php');
	global $wpdb, $woocommerce;

	if (!function_exists('unify_sgc_sms_regi_form')) {
		/**
		 * Hooked function to display the mobile number field on the registration form.
		 *
		 * This function is hooked to the 'register_form' action and is responsible for rendering the mobile number field
		 * on the WooCommerce registration form. It retrieves the entered mobile number from the submitted POST data if available.
		 *
		 * @return void
		 */
		add_action('register_form', 'unify_sgc_sms_regi_form');

		/**
		 * Display the mobile number field on the registration form.
		 *
		 * This function is responsible for rendering the mobile number field on the WooCommerce registration form.
		 * It retrieves the entered mobile number from the submitted POST data if available.
		 *
		 * @return void
		 */
		function unify_sgc_sms_regi_form() {
			// Retrieve the submitted mobile number from POST data or set it as empty if not present
			$billing_phone = '';
			$nonce_field = 'unify_sgc_sms_regi_nonce';

			// Add nonce field to the form
			wp_nonce_field($nonce_field, $nonce_field);

			if (isset($_POST['billing_phone'], $_POST[$nonce_field]) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST[$nonce_field])), $nonce_field)) {
				$billing_phone = sanitize_text_field($_POST['billing_phone']);
			}
			?>
			<p>
				<label for="billing_phone"><?php esc_html_e('Mobile No', 'unify_sgc_sms') ?><br />
					<input type="text" name="billing_phone" id="billing_phone" class="input" value="<?php echo esc_attr(sanitize_text_field($billing_phone)); ?>" size="10" /></label>
			</p>
			<?php
		}

	}

	if (!function_exists('unify_sgc_sms_regi_errors')) {
		/**
		 * Hooked function to validate the mobile number during user registration.
		 *
		 * This function is hooked to the 'registration_errors' filter and checks if a mobile number is provided during user registration.
		 * If the mobile number is empty, it adds an error to the registration errors array.
		 *
		 * @param WP_Error $errors              WordPress Error object containing registration errors.
		 * @param string   $sanitized_user_login Sanitized username.
		 * @param string   $user_email           User email address.
		 *
		 * @return WP_Error Modified WordPress Error object.
		 */
		add_filter('registration_errors', 'unify_sgc_sms_regi_errors', 10, 3);

		/**
		 * Validate the mobile number during user registration.
		 *
		 * This function is responsible for checking if a mobile number is provided during user registration.
		 * If the mobile number is empty, it adds an error to the registration errors array.
		 *
		 * @param WP_Error $errors              WordPress Error object containing registration errors.
		 * @param string   $sanitized_user_login Sanitized username.
		 * @param string   $user_email           User email address.
		 *
		 * @return WP_Error Modified WordPress Error object.
		 */
		function unify_sgc_sms_regi_errors($errors, $sanitized_user_login, $user_email) {
			// Verify nonce
			$nonce_field = 'unify_sgc_sms_regi_nonce';

			if (!isset($_POST[$nonce_field]) || !wp_verify_nonce(wp_unslash(sanitize_text_field($_POST[$nonce_field])), $nonce_field)) {
				// Nonce is not valid
				$errors->add('nonce_error', esc_html_e('<strong>ERROR</strong>: Security check failed. Please try again.', 'unify_sgc_sms'));
			} else {
				// Check if billing phone is empty
				if (empty($_POST['billing_phone'])) {
					$errors->add('billing_phone_error', esc_html_e('<strong>ERROR</strong>: Please enter a valid mobile number', 'unify_sgc_sms'));
				}
			}

			return $errors;
		}

	}

	if (!function_exists('unify_sgc_sms_customer_register')) {
		/**
		 * Hooked function to update user billing phone on user registration.
		 *
		 * This function is hooked to the 'user_register' action and is responsible for updating the user's billing phone.
		 * It retrieves the billing phone from the registration form, updates the user meta, and sends a SMS alert using unifysgcsms.
		 *
		 * @param int $user_id User ID of the newly registered user.
		 */
		add_action('user_register', 'unify_sgc_sms_customer_register');

		/**
		 * Update user billing phone on user registration.
		 *
		 * @param int $user_id User ID of the newly registered user.
		 */
		function unify_sgc_sms_customer_register($user_id) {
			// Retrieve options from the unifysgcsms settings
			$options = get_option('unify_sgc_sms_option_name');

			// Check if nonce is set and verify it
			if (isset($_POST['unify_sgc_sms_register_nonce']) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['unify_sgc_sms_register_nonce'])), 'unify_sgc_sms_register_nonce')) {
				if (isset($_POST['billing_phone']) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['_wpnonce'])), 'woocommerce-register')) {
					// Get billing phone, first name, and last name from the registration form
					$woocom_shop_phone = sanitize_text_field($_POST['billing_phone']);
					$woocom_first_name = sanitize_text_field($_POST['billing_first_name']);
					$woocom_last_name = sanitize_text_field($_POST['billing_last_name']);

					// Check if billing phone is not empty
					if (!empty($woocom_shop_phone)) {
						// Update user meta with sanitized billing phone
						update_user_meta($user_id, 'billing_phone', $woocom_shop_phone);

						// Create a new instance of unifysgcsms
						$unifysgcsms = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], false);

						// Generate SMS alert message using helper function
						$textMsg = unify_sgc_sms_regi_variable($options['unify_sgc_sms_regi_status'], $woocom_first_name, $woocom_last_name);

						// Send SMS alert using unifysgcsms
						$unifysgcsms->sendunifysgcsmsPost($textMsg, $woocom_shop_phone);
					}
				}
			}
		}

	}

	if (!function_exists('unify_sgc_sms_wooc_validate_extra_register_fields')) {
		/**
		 * Hooked function to validate extra registration fields.
		 *
		 * This function is hooked to the 'woocommerce_register_post' action and is responsible for validating
		 * additional fields during user registration. It checks if the first name and last name are provided,
		 * and if not, it adds corresponding validation errors.
		 *
		 * @param string $username            The submitted username.
		 * @param string $email               The submitted email address.
		 * @param WP_Error $validation_errors WordPress Error object containing validation errors.
		 *
		 * @return WP_Error Modified WordPress Error object.
		 */
		add_action('woocommerce_register_post', 'unify_sgc_sms_wooc_validate_extra_register_fields', 10, 3);

		/**
		 * Validate extra registration fields.
		 *
		 * @param string $username            The submitted username.
		 * @param string $email               The submitted email address.
		 * @param WP_Error $validation_errors WordPress Error object containing validation errors.
		 *
		 * @return WP_Error Modified WordPress Error object.
		 */
		function unify_sgc_sms_wooc_validate_extra_register_fields($username, $email, $validation_errors) {
			if (isset($_POST['billing_first_name']) && empty($_POST['billing_first_name'])) {
				$validation_errors->add('billing_first_name_error', __('<strong>Error</strong>: First name is required!', 'unify_sgc_sms'));
			}
			if (isset($_POST['billing_last_name']) && empty($_POST['billing_last_name'])) {
				$validation_errors->add('billing_last_name_error', __('<strong>Error</strong>: Last name is required!.', 'unify_sgc_sms'));
			}
			return $validation_errors;
		}

	}

	if (!function_exists('unify_sgc_sms_wooc_save_extra_register_fields')) {
		/**
		 * Hooked function to save extra registration fields.
		 *
		 * This function is hooked to the 'woocommerce_created_customer' action and is responsible for saving
		 * additional fields during user registration. It updates the user meta data for the phone number, first name,
		 * and last name based on the submitted POST data.
		 *
		 * @param int $customer_id The ID of the newly created customer.
		 *
		 * @return void
		 */
		add_action('woocommerce_created_customer', 'unify_sgc_sms_wooc_save_extra_register_fields');

		/**
		 * Save extra registration fields.
		 *
		 * @param int $customer_id The ID of the newly created customer.
		 *
		 * @return void
		 */
		function unify_sgc_sms_wooc_save_extra_register_fields($customer_id) {
			if (isset($_POST['billing_phone']) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['_wpnonce'])), 'woocommerce-register')) {
				if (isset($_POST['billing_phone'])) {
					// Phone input filed which is used in WooCommerce
					update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
				}
				if (isset($_POST['billing_first_name'])) {
					//First name field which is by default
					update_user_meta($customer_id, 'first_name', sanitize_text_field($_POST['billing_first_name']));
					// First name field which is used in WooCommerce
					update_user_meta($customer_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
				}
				if (isset($_POST['billing_last_name'])) {
					// Last name field which is by default
					update_user_meta($customer_id, 'last_name', sanitize_text_field($_POST['billing_last_name']));
					// Last name field which is used in WooCommerce
					update_user_meta($customer_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
				}
			}
		}

	}

	if (!function_exists('unify_sgc_sms_subscriber_data_uninstall')) {
		/**
		 * Hooked function to display additional fields on the admin new user registration form.
		 *
		 * This function is hooked to the 'user_new_form' action and is responsible for displaying
		 * additional fields (e.g., mobile number) on the admin new user registration form.
		 * It checks if the operation is 'add-new-user' before rendering the form fields.
		 *
		 * @param string $operation The operation being performed (e.g., 'add-new-user').
		 *
		 * @return void
		 */
		add_action('user_new_form', 'unify_sgc_sms_admin_regi_form');

		/**
		 * Uninstall subscriber data based on the operation.
		 *
		 * This function uninstalls subscriber data if the operation is 'add-new-user'.
		 * It retrieves the mobile number from the submitted POST data to pre-fill the form field.
		 *
		 * @param string $operation The operation being performed.
		 *
		 * @return void
		 */
		function unify_sgc_sms_subscriber_data_uninstall($operation) {
			if ('add-new-user' !== $operation) {
				return;
			}
			$billing_phone = !empty($_POST['billing_phone']) ? sanitize_text_field($_POST['billing_phone']) : '';
			?>
			<h3><?php esc_html_e('Personal Information', 'unify_sgc_sms'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="billing_phone"><?php esc_html_e('Mobile No', 'unify_sgc_sms'); ?></label> <span class="description"><?php esc_html_e('(required)', 'unify_sgc_sms'); ?></span></th>
					<td>
						<input type="text" name="billing_phone" id="billing_phone" class="input" value="<?php echo esc_attr(stripslashes($billing_phone)); ?>" size="50" /></label>
					</td>
				</tr>
			</table>
			<?php
		}

	}

	if (!function_exists('unify_sgc_sms_show_extra_profile_fields')) {
		/**
		 * Hooked function to display the billing phone field on the user's profile and edit profile page.
		 *
		 * This function is hooked to the 'show_user_profile' and 'edit_user_profile' actions and is responsible for displaying
		 * the billing phone field on the user's profile and edit profile page. It retrieves the user's ID and uses it to fetch
		 * the billing phone information from user meta.
		 *
		 * @param WP_User $user The WP_User object for the current user.
		 *
		 * @return void
		 */
		add_action('show_user_profile', 'unify_sgc_sms_show_extra_profile_fields');
		add_action('edit_user_profile', 'unify_sgc_sms_show_extra_profile_fields');

		/**
		 * Display extra profile fields.
		 *
		 * This function displays the billing phone field under the "Personal Information" section
		 * on the user's profile and edit profile page.
		 *
		 * @param WP_User $user The WP_User object for the current user.
		 *
		 * @return void
		 */
		function unify_sgc_sms_show_extra_profile_fields($user) {
			?>
			<h3><?php esc_html_e('Personal Information', 'unify_sgc_sms'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="billing_phone"><?php esc_html_e('Mobile No', 'unify_sgc_sms'); ?></label></th>
					<td><?php echo esc_html(get_the_author_meta('billing_phone', $user->ID)); ?></td>
				</tr>
			</table>
			<?php
		}

	}

	if (!function_exists('unify_sgc_sms_show_mobile_field_my_account')) {
		/**
		 * Hooked function to display the billing phone field on the WooCommerce edit account form.
		 *
		 * This function is hooked to the 'woocommerce_edit_account_form' action and is responsible for displaying
		 * the billing phone field on the WooCommerce edit account form. It retrieves the current user and checks
		 * if a billing phone is provided in the POST data. If not, it uses the billing phone from the user object.
		 *
		 * @return void
		 */
		add_action('woocommerce_edit_account_form', 'unify_sgc_sms_show_mobile_field_my_account');

		/**
		 * Display the billing phone field on the WooCommerce edit account form.
		 *
		 * This function displays the billing phone field, allowing users to edit their billing phone information.
		 * It checks if a billing phone is provided in the POST data and uses the billing phone from the user object
		 * if not. The billing phone is displayed in an input field with the corresponding label.
		 *
		 * @return void
		 */
		function unify_sgc_sms_show_mobile_field_my_account() {
			$user = wp_get_current_user();
			if (isset($_POST['billing_phone']) && wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['_wpnonce'])), 'woocommerce-register')) {
				if (!empty($_POST['billing_phone'])) {
					$billPhone = esc_attr(sanitize_text_field($_POST['billing_phone']));
				} else {
					$billPhone = esc_attr($user->billing_phone);
				}
			}
			?>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="billing_phone">Billing Phone&nbsp;<span class="required">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="billing_phone" autocomplete="off" value="<?php echo esc_attr($billPhone); ?>">
			</p>
			<?php
		}

	}

	if (!function_exists('unify_sgc_sms_save_billing_phone_account_details')) {
		/**
		 * Hooks a custom function to the 'woocommerce_save_account_details' action.
		 *
		 * This action is triggered when a user saves their account details on a WooCommerce site. The custom function
		 * 'unify_sgc_sms_save_billing_phone_account_details' is used to update and save the user's billing phone information.
		 */
		add_action('woocommerce_save_account_details', 'unify_sgc_sms_save_billing_phone_account_details', 12, 1);

		/**
		 * Saves the billing phone number in user meta when a user updates their account details.
		 *
		 * @param int $user_id The ID of the user whose account details are being saved.
		 *
		 * This function checks if a billing phone number has been posted and, if so, sanitizes and saves it
		 * as user meta. The billing phone number is a custom field added to the user's WooCommerce account details.
		 *
		 * Process:
		 * - Check if the 'billing_phone' field is set in the $_POST array.
		 * - If set, sanitize the text field to ensure clean, safe data.
		 * - Update the user meta for 'billing_phone' with the sanitized value.
		 *
		 * Note:
		 * - The function is hooked to the 'woocommerce_save_account_details' action, which is triggered when a user
		 *   saves their WooCommerce account details.
		 * - The 'sanitize_text_field' function is used to clean the input data to prevent security issues like XSS attacks.
		 */
		function unify_sgc_sms_save_billing_phone_account_details($user_id) {
			// For Favorite color
			if (isset($_POST['billing_phone']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['save_account_details'])), 'save-account-details-nonce')) {
				update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
			}
		}

	}

	if (!function_exists('unify_sgc_sms_update_profile')) {
		/**
		 * Hooked function to send SMS alert on user profile update.
		 *
		 * This function is hooked to the 'profile_update' action and is responsible for sending an SMS alert
		 * when a user's profile is updated. It checks if the admin notification is enabled in the plugin options,
		 * retrieves user metadata, constructs the SMS message using provided variables, and sends the SMS alert
		 * to the specified phone numbers using the unifysgcsms class.
		 *
		 * @param int $userid User ID being updated.
		 * @return void
		 */
		add_action('profile_update', 'unify_sgc_sms_update_profile', 10, 1);

		/**
		 * Send SMS alert on user profile update.
		 *
		 * This function sends an SMS alert when a user's profile is updated. It checks if the admin notification
		 * is enabled, retrieves user metadata, constructs the SMS message, and sends the SMS alert using the
		 * unifysgcsms class.
		 *
		 * @param int $userid User ID being updated.
		 * @return void
		 */
		function unify_sgc_sms_update_profile($userid) {
			$options = get_option('unify_sgc_sms_option_name');
			$isAdminNotifyEnabled = $options['unify_sgc_sms_notify_admin'];
			$wpMeta = get_user_meta($userid);
			$woocom_shop_phone = $wpMeta['billing_phone'][0];
			$woocom_first_name = $wpMeta['first_name'][0];
			$woocom_last_name = $wpMeta['last_name'][0];
			if ($isAdminNotifyEnabled == 'on') {
				$customerPhones = array();
				$customerPhones[] = $options['unify_sgc_sms_wp_admin_mobile'];
				array_push($customerPhones, $woocom_shop_phone);
			} else {
				$customerPhones = $woocom_shop_phone;
			}
			$textMsg = unify_sgc_sms_regi_variable($options['unify_sgc_sms_update_profile'], $woocom_first_name, $woocom_last_name);
			$unifysgcsms = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], false);
			$unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
		}

	}

	/**
	 * Hook with WooCommerce to save user address.
	 *
	 * This code checks if the WooCommerce plugin is active and hooks into the 'woocommerce_customer_save_address' action.
	 * When a customer's address is saved, it triggers the 'unify_sgc_sms_update_profile' function to handle any necessary
	 * updates or notifications related to the user's profile.
	 */
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		add_action('woocommerce_customer_save_address', 'unify_sgc_sms_update_profile');
	}

	if (!function_exists('unify_sgc_sms_password_reset')) {
		/**
		 * Hooks a custom function to the 'password_reset' action in WordPress.
		 *
		 * This action is triggered when a user successfully resets their password. The custom function
		 * 'unify_sgc_sms_password_reset' is used to send an SMS alert notifying the user of the password change.
		 */
		add_action('password_reset', 'unify_sgc_sms_password_reset', 10, 1);

		/**
		 * Sends an SMS alert when a user's password is reset.
		 *
		 * @param WP_User $user The user object of the account whose password was reset.
		 *
		 * This function retrieves the necessary user information and sends an SMS alert to the user's phone number
		 * as well as optionally to the admin's phone number. The message content is customized using the user's first and
		 * last names and the specified template in the plugin's settings.
		 *
		 * Process:
		 * - Retrieve plugin options to get the admin's mobile number and SMS settings.
		 * - Fetch user meta data like phone number and names.
		 * - Determine the recipients based on admin notification settings.
		 * - Format the SMS message using a template and user data.
		 * - Send the SMS using the 'sendunifysgcsmsPost' method of the 'unifysgcsms' class.
		 *
		 * Note:
		 * - The function checks if admin notifications are enabled and includes the admin's phone number accordingly.
		 * - The SMS message is customized based on the user's details and plugin settings.
		 */
		function unify_sgc_sms_password_reset($user) {
			$options = get_option('unify_sgc_sms_option_name');

			$isAdminNotifyEnabled = $options['unify_sgc_sms_notify_admin'];
			$userid = $user->ID;
			$wpMeta = get_user_meta($userid);
			$woocom_shop_phone = $wpMeta['billing_phone'][0];
			$woocom_first_name = $wpMeta['first_name'][0];
			$woocom_last_name = $wpMeta['last_name'][0];
			if ($isAdminNotifyEnabled == 'on') {
				$customerPhones = array();
				$customerPhones[] = $options['unify_sgc_sms_wp_admin_mobile'];
				array_push($customerPhones, $woocom_shop_phone);
			} else {
				$customerPhones = $woocom_shop_phone;
			}
			$textMsg = unify_sgc_sms_regi_variable($options['unify_sgc_sms_pass_reset'], $woocom_first_name, $woocom_last_name);
			$unifysgcsms = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], false);
			$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
		}

	}


	if (!function_exists('unify_sgc_sms_wooc_extra_register_fields')) {
		/**
		 * Hooks a custom function to the 'woocommerce_register_form_start' action.
		 *
		 * This action is used to add extra fields at the beginning of the WooCommerce registration form.
		 * The custom function 'unify_sgc_sms_wooc_extra_register_fields' is responsible for rendering these additional fields.
		 */
		add_action('woocommerce_register_form_start', 'unify_sgc_sms_wooc_extra_register_fields');

		/**
		 * Adds extra fields to the WooCommerce registration form.
		 *
		 * This function outputs HTML markup to add additional fields for the phone number, first name, and last name
		 * at the start of the WooCommerce registration form. These fields enhance the user registration process by
		 * capturing more detailed user information.
		 *
		 * Field Details:
		 * - Phone: A text input for the user's phone number.
		 * - First Name: A text input for the user's first name, marked as required.
		 * - Last Name: A text input for the user's last name, also marked as required.
		 *
		 * Note:
		 * - The values for these fields are pre-populated if available in the $_POST array, ensuring that user input
		 *   is retained in case of form validation errors.
		 * - The 'esc_attr' function is used to safely output data, preventing XSS attacks.
		 * - This function is intended to be used with WooCommerce, a popular e-commerce plugin for WordPress.
		 */
		function unify_sgc_sms_wooc_extra_register_fields() {
			?>
			<p class="form-row form-row-wide">
				<label for="reg_billing_phone"><?php esc_html_e('Phone', 'unify_sgc_sms'); ?></label>
				<input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php echo esc_attr(sanitize_text_field($_POST['billing_phone'])); ?>" />
			</p>
			<p class="form-row form-row-first">
				<label for="reg_billing_first_name"><?php esc_html_e('First name', 'unify_sgc_sms'); ?><span class="required">*</span></label>
				<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if (!empty($_POST['billing_first_name'])) printf(esc_attr__('%s', 'unify_sgc_sms'), esc_attr(sanitize_text_field($_POST['billing_first_name']))); ?>" />
			</p>
			<p class="form-row form-row-last">
				<label for="reg_billing_last_name"><?php esc_html_e('Last name', 'unify_sgc_sms'); ?><span class="required">*</span></label>
				<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if (!empty($_POST['billing_last_name'])) printf(esc_attr__('%s', 'unify_sgc_sms'), esc_attr(sanitize_text_field($_POST['billing_last_name']))); ?>" />
			</p>
			<div class="clear"></div>
			<?php
		}

	}
	