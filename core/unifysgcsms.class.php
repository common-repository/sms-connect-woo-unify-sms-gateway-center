<?php

	if (!defined('ABSPATH'))
		exit;

	global $wpdb, $woocommerce, $product;

	if (!function_exists('unify_sgc_sms_shortcode_variable')) {

		/**
		 * Processes and replaces shortcodes in a text message with dynamic data from a WooCommerce order.
		 *
		 * This function takes a text message containing various shortcodes and a WooCommerce order object. 
		 * It replaces the shortcodes with actual data from the order, such as order number, status, amount, etc. 
		 * It is designed to dynamically generate personalized text messages based on order details.
		 *
		 * @param string $textMsg The text message containing shortcodes to be replaced.
		 * @param WC_Order $order The WooCommerce order object from which data is extracted.
		 *
		 * @return string The processed text message with all shortcodes replaced by their corresponding order data.
		 *
		 * The function supports the following shortcodes:
		 * - {WOOCOM_SHOP_NAME}: Replaced with the name of the WooCommerce shop.
		 * - {WOOCOM_ORDER_NUMBER}: Replaced with the order ID.
		 * - {WOOCOM_ORDER_STATUS}: Replaced with the status of the order.
		 * - {WOOCOM_ORDER_AMOUNT}: Replaced with the total amount of the order.
		 * - {WOOCOM_ORDER_DATE}: Replaced with the date the order was created.
		 * - {WOOCOM_ORDER_ITEMS}: Replaced with a list of items in the order.
		 * - {WOOCOM_BILLING_FNAME}: Replaced with the billing first name of the order.
		 * - {WOOCOM_BILLING_LNAME}: Replaced with the billing last name of the order.
		 * - {WOOCOM_BILLING_EMAIL}: Replaced with the billing email of the order.
		 * - {WOOCOM_CURRENT_DATE}: Replaced with the current date.
		 * - {WOOCOM_CURRENT_TIME}: Replaced with the current time.
		 *
		 * If the text message or order object is not provided, the function returns without processing.
		 * It uses regular expressions to find and replace shortcodes and gets the current date and time using WordPress settings.
		 */
		function unify_sgc_sms_shortcode_variable($textMsg, $order) {
			if (!$textMsg || !is_object($order)) {
				return;
			}
			$woocom_orderId = $order->get_id();
			$order_custom_fields = get_post_custom($woocom_orderId);
			$current_date_time = current_time('timestamp');

			if (preg_match("/{WOOCOM_SHOP_NAME}/i", $textMsg)) {
				$WOOCOM_SHOP_NAME = get_option("blogname");
				$textMsg = @str_replace("{WOOCOM_SHOP_NAME}", $WOOCOM_SHOP_NAME, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_NUMBER}/i", $textMsg)) {
				$WOOCOM_ORDER_NUMBER = isset($woocom_orderId) ? $woocom_orderId : "";
				$textMsg = @str_replace("{WOOCOM_ORDER_NUMBER}", $WOOCOM_ORDER_NUMBER, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_STATUS}/i", $textMsg)) {
				$WOOCOM_ORDER_STATUS = @ucfirst($order->get_status());
				$textMsg = @str_replace("{WOOCOM_ORDER_STATUS}", $WOOCOM_ORDER_STATUS, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_AMOUNT}/i", $textMsg)) {
				$WOOCOM_ORDER_AMOUNT = $order_custom_fields["_order_total"][0];
				$textMsg = @str_replace("{WOOCOM_ORDER_AMOUNT}", $WOOCOM_ORDER_AMOUNT, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_DATE}/i", $textMsg)) {
				$order_date_format = get_option("date_format");
				$WOOCOM_ORDER_DATE = date_i18n($order_date_format, strtotime($order->get_date_created()));
				$textMsg = @str_replace("{WOOCOM_ORDER_DATE}", $WOOCOM_ORDER_DATE, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_ITEMS}/i", $textMsg)) {
				$order_items = $order->get_items(apply_filters("woocommerce_admin_order_item_types", array("line_item")));
				$WOOCOM_ORDER_ITEMS = "";
				if (count($order_items)) {
					$item_cntr = 0;
					foreach ($order_items as $order_item) {
						if ($order_item["type"] == "line_item") {
							if ($item_cntr == 0)
								$WOOCOM_ORDER_ITEMS = $order_item["name"];
							else
								$WOOCOM_ORDER_ITEMS .= ", " . $order_item["name"];
							$item_cntr++;
						}
					}
				}
				$textMsg = @str_replace("{WOOCOM_ORDER_ITEMS}", $WOOCOM_ORDER_ITEMS, $textMsg);
			}
			if (preg_match("/{WOOCOM_BILLING_FNAME}/i", $textMsg)) {
				$WOOCOM_BILLING_FNAME = $order_custom_fields["_billing_first_name"][0];
				$textMsg = @str_replace("{WOOCOM_BILLING_FNAME}", $WOOCOM_BILLING_FNAME, $textMsg);
			}

			if (preg_match("/{WOOCOM_BILLING_LNAME}/i", $textMsg)) {
				$WOOCOM_BILLING_LNAME = $order_custom_fields["_billing_last_name"][0];
				$textMsg = @str_replace("{WOOCOM_BILLING_LNAME}", $WOOCOM_BILLING_LNAME, $textMsg);
			}
			if (preg_match("/{WOOCOM_BILLING_EMAIL}/i", $textMsg)) {
				$WOOCOM_BILLING_EMAIL = $order_custom_fields["_billing_email"][0];
				$textMsg = @str_replace("{WOOCOM_BILLING_EMAIL}", $WOOCOM_BILLING_EMAIL, $textMsg);
			}
			if (preg_match("/{WOOCOM_CURRENT_DATE}/i", $textMsg)) {
				$wp_date_format = get_option("date_format");
				$WOOCOM_CURRENT_DATE = date_i18n($wp_date_format, $current_date_time);
				$textMsg = @str_replace("{WOOCOM_CURRENT_DATE}", $WOOCOM_CURRENT_DATE, $textMsg);
			}
			if (preg_match("/{WOOCOM_CURRENT_TIME}/i", $textMsg)) {
				$wp_time_format = get_option("time_format");
				$WOOCOM_CURRENT_TIME = date_i18n($wp_time_format, $current_date_time);
				$textMsg = @str_replace("{WOOCOM_CURRENT_TIME}", $WOOCOM_CURRENT_TIME, $textMsg);
			}
			return $textMsg;
		}

	}

	/**
	 * Replaces placeholders in a text message with registration-related variables.
	 *
	 * This function is intended to process a text message by substituting placeholders
	 * with actual registration details like the first name and last name of a user. 
	 * It searches for specific placeholders in the message and replaces them with the 
	 * provided values.
	 *
	 * @param string $textMsg The original text message containing placeholders.
	 * @param string $firstname The first name to replace the corresponding placeholder in the text message.
	 * @param string $lastname The last name to replace the corresponding placeholder in the text message.
	 *
	 * @return string|null The modified text message with placeholders replaced by actual names.
	 *                     Returns the original message if all replacements are successful, or null if input parameters are invalid.
	 *
	 * Supported placeholders and their replacements:
	 * - {WOOCOM_FIRST_NAME}: Replaced by the value of `$firstname`.
	 * - {WOOCOM_LAST_NAME}: Replaced by the value of `$lastname`.
	 *
	 * If either the text message or the first name is not provided, the function returns without performing any replacement.
	 * Note: The use of `@` before `str_replace` is to suppress any error messages that might arise during the replacement process.
	 */
	function unify_sgc_sms_regi_variable($textMsg, $firstname, $lastname) {
		// Check if required parameters are provided
		if (!$textMsg || !($firstname)) {
			return;
		}

		// Replace the first name placeholder
		if (preg_match("/{WOOCOM_FIRST_NAME}/i", $textMsg)) {
			$textMsg = @str_replace("{WOOCOM_FIRST_NAME}", $firstname, $textMsg);
		}

		// Replace the last name placeholder
		if (preg_match("/{WOOCOM_LAST_NAME}/i", $textMsg)) {
			$textMsg = @str_replace("{WOOCOM_LAST_NAME}", $lastname, $textMsg);
		}

		// Return the processed text message
		return $textMsg;
	}

	/**
	 * unifysgcsms class for handling SMS Business API integration.
	 *
	 * This class provides methods to interact with SMS Business API for sending messages,
	 * listing delivery reports, and other related functionalities. It uses specific endpoints
	 * and requires authentication credentials such as username, password, and API key.
	 */
	class unifysgcsms {

		const REQUEST_URL = 'https://unify.smsgateway.center/';  // Base URL for API requests.
		const REQUEST_TIMEOUT = 60;  // Default request timeout.
		const REQUEST_HANDLER = 'curl';  // Default request handler.

		private $username;  // Username for API authentication.
		private $password;  // Password for API authentication.
		private $apiKey;    // API key for additional authentication.
		public $errors = array();      // Array to store error messages.
		public $warnings = array();    // Array to store warning messages.
		public $lastRequest = array(); // Array to store information about the last request.

		/**
		 * Constructor for unifysgcsms class.
		 *
		 * @param string $username The username for API authentication.
		 * @param string $password The password for API authentication.
		 * @param string|false $apiKey The API key for authentication, if available.
		 */
		function __construct($username, $password, $apiKey = false) {
			$this->username = $username;
			$this->password = $password;
			if ($apiKey) {
				$this->apiKey = $apiKey;
			}
		}

		/**
		 * Retrieves default parameters for API requests.
		 *
		 * @return array An array containing default parameters like userid, password, and output format.
		 */
		function unifysgcsmsGetDefaultParams() {
			$params['userid'] = $this->username;
			$params['password'] = $this->password;
			$params['output'] = 'json';
			
			// Return the params array
			return $params;
		}

		/**
		 * Sends a request to the SMS API using the cURL method.
		 *
		 * @param string $endpoint The specific API endpoint to target.
		 * @param array $params The parameters to send with the request.
		 * @return mixed The response from the API, decoded from JSON.
		 * @throws Exception If any error occurs during the request.
		 */
		private function unifysgcsmsCurlRequest($endpoint, $params) {
			$url = self::REQUEST_URL . $endpoint;
//			print_r($url);
//			print_r($params);
			$response = wp_remote_post($url, array('body' => $params));
			//print_r($response);
			$jsonResponse = wp_remote_retrieve_body($response);
			$rawResponse = json_decode($jsonResponse);
			//Return the JSON decoded array
			return $rawResponse;
		}

		/**
		 * Sends a simple SMS message using the POST method.
		 *
		 * @param string $msgType The type of message to send.
		 * @param string $templateName The name of the message template.
		 * @param string $textMsg The text message to send.
		 * @param array $phones The array of phone numbers to send the message to.
		 * @param string $mediaType The type of media to send (if any).
		 * @param string $mediaUrl The URL of the media to send (if any).
		 * @param string $header The header of the message (if any).
		 * @param string $footer The footer of the message (if any).
		 * @param string $btnPayload The payload for any buttons in the message (if any).
		 * @return boolean|string True on success, an error message string on failure.
		 */
		public function sendunifysgcsmsPost($textMsg, $phones = array()) {
			$options = get_option('unify_sgc_sms_option_name');
			$baseURL = self::REQUEST_URL;
			$params['userid'] = $options['unify_sgc_sms_userId'];
			$params['password'] = $options['unify_sgc_sms_password'];
			$params['senderid'] = $options['unify_sgc_sms_sendername'];
			$params['sendMethod'] = 'quick';
			$params['msgType'] = 'dynamic';
			$params['msg'] = $textMsg;
			$params['output'] = 'json';

			$isAdminNotifyEnabled = $options['unify_sgc_sms_admin_mobile'];

			$sendSMSAPIUrl = $baseURL . "SMSApi/send";
			if ($isAdminNotifyEnabled == 'on' && !empty($options['unify_sgc_sms_admin_mobile']) && is_array($phones)) {
				$params['mobile'] = implode(',', $phones);
			} else {
				$params['mobile'] = $phones;
			}

			$response = wp_remote_post($sendSMSAPIUrl, array('body' => $params));
			$jsonResponse = wp_remote_retrieve_body($response);
			$jsonResponse = json_decode($jsonResponse);
			if ($jsonResponse->status == 'error') {
				return $jsonResponse->reason;
			} elseif ($jsonResponse->status == 'success') {
				return true;
			}
		}

		/**
		 * Retrieves a list of Delivery Reports (DLRs) for a given date range and phone number.
		 *
		 * @param string $fromDate The start date for the report.
		 * @param string $toDate The end date for the report.
		 * @param string $mobileNo The mobile number to filter the report.
		 * @return array|mixed The response from the API, containing the delivery reports.
		 */
		public function unifysgcsmsListReports($fromDate, $toDate, $mobileNo) {
			$options = get_option('unify_sgc_sms_option_name');
			$pageLimit = 5000;
			$convertedFromDate = $fromDate == '' ? date('Y-m-d 00:00:00') : date('Y-m-d 00:00:00', strtotime($fromDate));
			$convertedToDate = $toDate == '' ? date('Y-m-d 11:59:59') : date('Y-m-d 23:59:59', strtotime($toDate));
			$extra = '';
			if (!empty($mobileNo)) {
				$extra .= '&mobile=' . $mobileNo;
			}//=
			$response = wp_remote_get(self::REQUEST_URL . 'SMSApi/reports/status?'
				. 'userid=' . $this->username . ''
				. '&password=' . $this->password . ''
				. '&method=getDlr'
				. '&fromDate=' . $convertedFromDate . ''
				. '&toDate=' . $convertedToDate . ''
				. '&pageLimit=' . $pageLimit . $extra);
			$rawResponse = wp_remote_retrieve_body($response);
			$decodeResponse = json_decode($rawResponse);
//			print_r($response);
//			print_r($decodeResponse);
			return $decodeResponse;
		}
		
		/**
		 * List Sender names
		 * @return array|mixed
		 */
		public function unifysgcsmsListSenderNames() {
			$params = $this->unifysgcsmsGetDefaultParams();
			return $this->unifysgcsmsCurlRequest('SMSApi/senderid/read', $params);
		}
	}
	