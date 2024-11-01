<?php

	if (!defined('ABSPATH'))
		exit;

	require_once (UNIFY_SGC_SMS_DIR . '/core/unifysgcsms.class.php');

	global $wpdb, $woocommerce, $product;
	//check wooecommerce plugin
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

		// Check if the function hasn't been defined already to prevent function redeclaration errors.
		if (!function_exists('unifysgcsms_post_comment')) {
			/**
			 * Hooks a custom function to the 'woocommerce_order_status_changed' action in WooCommerce.
			 *
			 * This action is triggered whenever an order's status changes in WooCommerce. The custom function
			 * 'unifysgcsms_order_status' is used to send notifications based on the new order status.
			 */
			add_action("woocommerce_order_status_changed", "unifysgcsms_order_status");

			/**
			 * Sends SMS alert based on the order status change in WooCommerce.
			 *
			 * @global WC_Order $order The WooCommerce order object.
			 * @param int $order_id The ID of the order whose status has changed.
			 *
			 * This function checks the new status of the order and sends an appropriate SMS notification to the customer. 
			 * The notification varies depending on whether the order is completed, processing, pending, on-hold, cancelled, refunded, 
			 * or failed. It includes the use of dynamic text replacement to personalize the message for each order and customer.
			 *
			 * Process:
			 * - Create a new instance of the WC_Order class to handle the order data.
			 * - Retrieve the configured options for message templates and settings.
			 * - Determine the order's new status and prepare the corresponding message and template.
			 * - Send the SMS using the 'sendunifysgcsmsPost' method of the 'unifysgcsms' class if notifications are enabled for the respective status.
			 *
			 * Note:
			 * - The function supports multiple order statuses and can be extended or modified to fit specific needs.
			 * - It is part of a system integrated with WooCommerce, ensuring seamless e-commerce functionalities.
			 */
			function unifysgcsms_order_status($order_id) {
				global $woocommerce;
				$order = new WC_Order($order_id);
				$options = get_option('unify_sgc_sms_option_name');
				$isAdminNotifyEnabled = $options['unify_sgc_sms_notify_admin'];
				$unifysgcsms = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], FALSE);

				//error_log(print_r($order, true));
				//default phones
				$woocom_shop_phone = $order->get_billing_phone();
				if ($isAdminNotifyEnabled == 'on') {
					$customerPhones = array();
					$customerPhones[] = $options['unify_sgc_sms_admin_mobile'];
					array_push($customerPhones, $woocom_shop_phone);
				} else {
					$customerPhones = $woocom_shop_phone;
				}
				//completed
				if ($order->get_status() === 'completed' && $options['unify_sgc_sms_order_completed_status'] == 'on') {
					$textMsg = unify_sgc_sms_shortcode_variable($options['unify_sgc_sms_order_complete'], $order);
					$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
				}
				//processing
				if ($order->get_status() === 'processing' && $options['unify_sgc_sms_order_status_processing'] == 'on') {
					$textMsg = unify_sgc_sms_shortcode_variable($options['unify_sgc_sms_order_processing'], $order);
					$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
				}
				//pending
				if ($order->get_status() === 'pending' && $options['unify_sgc_sms_order_status_pending_payment'] == 'on') {
					$textMsg = unify_sgc_sms_shortcode_variable($options['unify_sgc_sms_order_pending_payment'], $order);
					$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
				}
				//on-hold
				if ($order->get_status() === 'on-hold' && $options['unify_sgc_sms_order_status_onhold'] == 'on') {
					$textMsg = unify_sgc_sms_shortcode_variable($options['unify_sgc_sms_order_onhold'], $order);
					$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
				}
				//cancelled
				if ($order->get_status() === 'cancelled' && $options['unify_sgc_sms_order_status_cancelled'] == 'on') {
					$textMsg = unify_sgc_sms_shortcode_variable($options['unify_sgc_sms_order_cancelled'], $order);
					$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
				}
				//refunded
				if ($order->get_status() === 'refunded' && $options['unify_sgc_sms_order_status_refunded'] == 'on') {
					$textMsg = unify_sgc_sms_shortcode_variable($options['unify_sgc_sms_order_refunded'], $order);
					$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
				}
				//failed
				if ($order->get_status() === 'failed' && $options['unify_sgc_sms__order_status_failed'] == 'on') {
					$textMsg = unify_sgc_sms_shortcode_variable($options['unify_sgc_sms_order_failed'], $order);
					$result = $unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
				}
			}

		} // End of function_exists check
		// Check if the function hasn't been defined already to prevent function redeclaration errors.
		if (!function_exists('unifysgcsms_post_comment')) {
			/**
			 * Hooks a custom function to the 'comment_post' action in WordPress.
			 *
			 * This action is triggered whenever a new comment is posted, but before it is saved in the database.
			 * The custom function 'unifysgcsms_post_comment' is used to send notifications when a new comment is posted.
			 */
			add_action('comment_post', 'unifysgcsms_post_comment');

			/**
			 * Sends an SMS alert when a new comment is posted for moderation.
			 *
			 * @global WP_Post $product The product associated with the comment.
			 * @global WP_User $current_user The current user making the comment.
			 * @param int $commentId The ID of the newly posted comment.
			 *
			 * This function constructs and sends an SMS notification to the user who posted the comment. The notification
			 * informs them that their review is awaiting approval. The message includes the product's title and the user's first name,
			 * personalizing the alert.
			 *
			 * Process:
			 * - Retrieve user information and the product details associated with the comment.
			 * - Format a message to inform the user that their review is under moderation.
			 * - Send the SMS using the 'sendunifysgcsmsPost' method of the 'unifysgcsms' class if notifications are enabled.
			 *
			 * Note:
			 * - The function is part of a WooCommerce-based system, where product reviews are a key interaction.
			 * - It leverages WooCommerce and WordPress functions to extract relevant user and product information.
			 */
			function unifysgcsms_post_comment($commentId) {
				global $product, $current_user;
				if (isset($_POST['_wpnonce'])) {
					$nonce = sanitize_text_field($_POST['_wpnonce']);
					if (!wp_verify_nonce($nonce, 'comment_form_' . get_the_ID())) {
						// Nonce verification failed, handle accordingly (e.g., show an error message or exit)
						return;
					}
					$options = get_option('unify_sgc_sms_option_name');
					wp_get_current_user();
					$user_id = get_current_user_id();
					$name = $current_user->user_firstname;
					$customerPhones = get_user_meta($user_id, 'billing_phone', true);
					$post_id = isset($_POST['comment_post_ID']) ? (int) sanitize_text_field($_POST['comment_post_ID']) : 0;
					$product = wc_get_product($post_id);
					$title = $product->get_title(); // Use get_title() method to get the product title
					$textMsg = "Thank You! " . $name . ", \nYour review on " . $title . " is awaiting for approval. Your feedback will help millions of other customers, we really appreciate the time and effort you spent in sharing your personal experience with us.";
					$unifysgcsms = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], false);
					if ($options['unify_sgc_sms_product_review_notification'] == 'on') {
						$unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
					}
				}
			}

		} // End of function_exists check
		// Check if the function doesn't already exist to avoid function redeclaration errors.
		if (!function_exists('unifysgcsms_comment_approved')) {
			/**
			 * Hooks a custom function to the 'transition_comment_status' action in WordPress.
			 *
			 * This action is triggered whenever a comment's status transitions, for example from 'pending' to 'approved'.
			 * The custom function 'unifysgcsms_comment_approved' is used to send notifications when a comment is approved.
			 */
			add_action('transition_comment_status', 'unifysgcsms_comment_approved', 10, 3);

			/**
			 * Sends SMS alert when a comment is approved.
			 *
			 * @global WP_Post $product The product associated with the comment.
			 * @param string $new_status The new status of the comment.
			 * @param string $old_status The old status of the comment.
			 * @param WP_Comment $comment The comment object.
			 *
			 * This function checks if the comment's status has changed to 'approved'. If so, it retrieves the user's phone number
			 * and sends SMS notification to thank them for their product review. The message includes the product's title and
			 * the customer's name, personalizing the alert.
			 *
			 * Process:
			 * - Verify that the comment's status has transitioned to 'approved'.
			 * - Retrieve user information and product details associated with the comment.
			 * - Format a personalized thank-you message for the approved review.
			 * - Send the SMS using the 'sendunifysgcsmsPost' method of the 'unifysgcsms' class if notifications are enabled.
			 *
			 * Note:
			 * - The function is part of a WooCommerce-based system, where product reviews are crucial.
			 * - It utilizes WooCommerce and WordPress functions to get user and product data.
			 */
			function unifysgcsms_comment_approved($new_status, $old_status, $comment) {
				if ($old_status != $new_status) {
					if ($new_status == 'approved') {
						global $product;
						$options = get_option('unify_sgc_sms_option_name');
						$userid = $comment->user_id;
						$user_data = get_userdata($userid);
						$name = $user_data->display_name;
						$post_id = $comment->comment_post_ID;
						$customerPhones = get_user_meta($userid, 'billing_phone', true);
						$product = wc_get_product($post_id);
						$title = $product->post->post_title;
						$textMsg = "Thank You " . $name . ", \nYour review on " . $title . " has been published. Your feedback will help millions of other customers, we really appreciate the time and effort you spent in sharing your personal experience with us.";
						$unifysgcsms = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], FALSE);
						if ($options['unify_sgc_sms_product_review_notification'] == 'on') {
							$unifysgcsms->sendunifysgcsmsPost($textMsg, $customerPhones);
						}
					}
				}
			}

		} // End of function_exists check
	}