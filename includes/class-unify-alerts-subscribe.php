<?php

	if (!defined('ABSPATH'))
		exit;
	/**
	 * Defines the current date and time based on WordPress settings.
	 *
	 * This constant is set to the current date and time using WordPress's current_time function,
	 * formatted as 'Year-Month-Day Hours:Minutes:Seconds'. This ensures that the time is consistent
	 * with the WordPress configured timezone settings.
	 */
	define('UNIFYSGCSMS_ALERTS_CURR_DATE', date('Y-m-d H:i:s', current_time('timestamp')));

	/**
	 * Class unifySMS_Alerts_Subscriptions
	 *
	 * This class is responsible for managing subscriptions related to unify.smsgateway.center alerts.
	 * It includes methods for adding, updating, and removing subscribers, as well as handling
	 * group subscriptions and potentially other subscription-related functionalities.
	 */
	class unifySMS_Alerts_Subscriptions {

		/**
		 * Current date and time in WordPress format.
		 *
		 * @var string 
		 */
		public $date;

		/**
		 * Reference to the WordPress database object.
		 *
		 * @var wpdb
		 */
		protected $db;

		/**
		 * Prefix used for WordPress database tables.
		 *
		 * @var string
		 */
		protected $tb_prefix;

		/**
		 * Constructor for the unifySMS_Alerts_Subscriptions class.
		 *
		 * Initializes the class properties by setting the current date, 
		 * getting the global WordPress database object, and the table prefix.
		 */
		public function __construct() {
			global $wpdb;

			$this->date = UNIFYSGCSMS_ALERTS_CURR_DATE;
			$this->db = $wpdb;
			$this->tb_prefix = $wpdb->prefix;
		}

		/**
		 * Adds a new subscriber to the database.
		 *
		 * This method inserts a new subscriber into the database with the provided name,
		 * mobile number, and group ID. It checks for duplicates before insertion. If a
		 * duplicate is found, it returns an error; otherwise, it adds the subscriber and
		 * triggers a hook after successful addition.
		 *
		 * @param string $name The name of the subscriber.
		 * @param string $mobile The mobile number of the subscriber.
		 * @param string $group_id The group ID the subscriber belongs to (optional).
		 * @param string $status The status of the subscription (optional, defaults to '1').
		 * @param mixed $key Additional key for internal use (optional).
		 * @return array An associative array with 'result' (error or update) and 'message'.
		 */
		public function add_subscriber($name, $mobile, $group_id = '', $status = '1', $key = null) {
			global $wpdb;
			$table_name = $wpdb->prefix . "unify_sgc_sms_subscribers";
			if ($this->is_duplicate($mobile, $group_id)) {
				return array('result' => 'error',
					'message' => __('The mobile number already exists.', 'unify_sgc_sms')
				);
			}

			$result = $wpdb->insert(
				$table_name, array(
				'date' => $this->date,
				'name' => $name,
				'mobile' => $mobile,
				'group_ID' => $group_id,
				)
			);

			if ($result) {
				return array('result' => 'update', 'message' => __('Subscriber successfully added.', 'unify_sgc_sms'));
			}
		}

		/**
		 * Retrieves a subscriber's details from the database.
		 *
		 * This method fetches the details of a subscriber based on the provided ID.
		 * It queries the database for the subscriber's information and returns the result.
		 *
		 * @param int $id The unique identifier of the subscriber.
		 * @return object|null The subscriber object if found, or null if not found.
		 */
		public function get_subscriber($id) {
			global $wpdb;
			$query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}unify_sgc_sms_subscribers WHERE ID = %d", $id);
			$result = $wpdb->get_row($query);
			if ($result) {
				return $result;
			}
		}

		/**
		 * Deletes a subscriber from the database.
		 *
		 * This method removes a subscriber's record from the database based on the provided ID.
		 * It performs a deletion operation and returns the result of that operation.
		 * Additionally, it triggers an action hook after successful deletion.
		 *
		 * @param int $id The unique identifier of the subscriber to be deleted.
		 * @return false|int Returns the number of rows affected, or false on failure.
		 */
		public function delete_subscriber($id) {
			global $wpdb;
			$table_name = $wpdb->prefix . "unify_sgc_sms_subscribers";
			$result = $wpdb->delete(
				$table_name, array(
				'ID' => $id,
				)
			);
			if ($result) {
				return $result;
			}
		}

		/**
		 * Deletes a subscriber by their mobile number.
		 *
		 * This method allows for the removal of a subscriber's record from the database using their mobile number.
		 * Optionally, a group ID can be specified to target a specific group. It returns an array indicating the result of the operation.
		 * The method also triggers an action hook after a successful deletion.
		 *
		 * @param string $mobile The mobile number of the subscriber to be deleted.
		 * @param int|null $group_id Optional. The group ID to which the subscriber belongs, if applicable.
		 * @return array Associative array containing the result status ('error' or 'update') and a message.
		 */
		public function delete_subscriber_by_number($mobile, $group_id = null) {
			global $wpdb;
			$table_name = $wpdb->prefix . "unify_sgc_sms_subscribers";
			$result = $wpdb->delete(
				$table_name, array(
				'mobile' => $mobile,
				'group_id' => $group_id,
				)
			);

			if (!$result) {
				return array('result' => 'error', 'message' => __('The subscribe does not exist.', 'unify_sgc_sms'));
			}

			return array('result' => 'update', 'message' => __('Subscribe successfully removed.', 'unify_sgc_sms'));
		}

		/**
		 * Updates a subscriber's details in the database.
		 *
		 * This method updates the information of an existing subscriber based on the subscriber's ID. 
		 * It checks for empty values and duplicates before proceeding with the update. 
		 * The method returns an array with the operation result and a message. 
		 * Additionally, an action hook is triggered after a successful update.
		 *
		 * @param int $id The unique identifier of the subscriber to update.
		 * @param string $name The name of the subscriber.
		 * @param string $mobile The mobile number of the subscriber.
		 * @param string $group_id Optional. The group ID to which the subscriber belongs.
		 * @param string $status Optional. The status of the subscriber.
		 * @return array|void An array with 'result' and 'message' keys on success, or void if there are missing parameters.
		 */
		public function update_subscriber($id, $name, $mobile, $group_id = '', $status = '1') {
			global $wpdb;
			$table_name = $wpdb->prefix . "unify_sgc_sms_subscribers";
			if (empty($id) or empty($name) or empty($mobile)) {
				return;
			}
			if ($this->is_duplicate($mobile, $group_id, $id)) {
				return array('result' => 'error',
					'message' => __('The mobile numbers has been already duplicate.', 'unify_sgc_sms')
				);
			}
			$result = $this->db->update(
				$table_name, array(
				'name' => $name,
				'mobile' => $mobile,
				'group_ID' => $group_id,
				), array(
				'ID' => $id
				)
			);

			if ($result) {
				return array('result' => 'update', 'message' => __('Subscriber successfully updated.', 'unify_sgc_sms'));
			}
		}

		/**
		 * Retrieves all subscriber groups from the database.
		 *
		 * This method queries the database to fetch all entries from the subscriber groups table. 
		 * It returns a list of all groups, each represented as an object within an array.
		 *
		 * @return array|null|object An array of objects representing each group, or null if no groups are found.
		 */
		public function get_groups() {
			global $wpdb;
			$query = "SELECT * FROM {$wpdb->prefix}unify_sgc_sms_subscribers_group";
			$result = $wpdb->get_results($query);
			if ($result) {
				return $result;
			}
		}

		/**
		 * Retrieves a specific subscriber group based on the given group ID.
		 *
		 * This method queries the database to fetch the details of a subscriber group identified by the specified group ID.
		 * If the group is found, it returns an object representing the group's details.
		 *
		 * @param int $group_id The ID of the subscriber group to retrieve.
		 * @return object|null The object containing the group's details if found, or null if no group matches the given ID.
		 */
		public function get_group($group_id) {
			global $wpdb;
			$query = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}unify_sgc_sms_subscribers_group WHERE ID = %d",
				$group_id
			);
			$result = $wpdb->get_row($query);
			if ($result) {
				return $result;
			}
		}

		/**
		 * Adds a new subscriber group to the database.
		 *
		 * This method attempts to add a new subscriber group with the specified name to the database.
		 * It first checks if the name is empty or if the group already exists to prevent duplicates.
		 * On successful addition, it triggers an action hook and returns a success message.
		 * If the group name is empty or already exists, it returns an error message.
		 *
		 * @param string $name The name of the subscriber group to be added.
		 * @return array An associative array containing the result status ('error' or 'update') and a message.
		 */
		public function add_group($name) {
			global $wpdb;
			$table_name = $wpdb->prefix . "unify_sgc_sms_subscribers_group";
			if (empty($name)) {
				return array('result' => 'error', 'message' => __('Name is empty!', 'unify_sgc_sms'));
			}
			if ($this->is_duplicate_group($name)) {
				return array('result' => 'error',
					'message' => __('The group already exists.', 'unify_sgc_sms')
				);
			}
			$result = $wpdb->insert(
				$table_name, array(
				'name' => $name,
				)
			);
			if ($result) {
				return array('result' => 'update', 'message' => __('Group successfully added.', 'unify_sgc_sms'));
			}
		}

		/**
		 * Deletes a subscriber group from the database.
		 *
		 * This method deletes a subscriber group identified by the given ID from the database.
		 * It first checks if the ID is not empty and then proceeds to delete the group.
		 * On successful deletion, it triggers an action hook and returns the result of the deletion operation.
		 * If the ID is empty, the method returns without performing any action.
		 *
		 * @param int $id The ID of the subscriber group to be deleted.
		 * @return false|int Returns the result of the deletion operation or void if the ID is empty.
		 */
		public function delete_group($id) {
			global $wpdb;
			$table_name = $wpdb->prefix . "unify_sgc_sms_subscribers_group";
			if (empty($id)) {
				return;
			}
			$result = $wpdb->delete(
				$table_name, array(
				'ID' => $id,
				)
			);
			if ($result) {
				return $result;
			}
		}

		/**
		 * Updates the name of an existing subscriber group.
		 *
		 * This method allows updating the name of a subscriber group in the database, identified by its ID.
		 * It first checks whether both the ID and name are provided. If either is missing, the method returns without any action.
		 * On successful update, it triggers an action hook and returns a success message.
		 * If the update operation does not occur, possibly due to no changes in the group name, the method simply exits.
		 *
		 * @param int $id The ID of the subscriber group to be updated.
		 * @param string $name The new name for the subscriber group.
		 * @return array|void Returns an associative array with the update status and message on success, or void if inputs are missing.
		 */
		public function update_group($id, $name) {
			global $wpdb;
			$table_name = $wpdb->prefix . "unify_sgc_sms_subscribers_group";
			if (empty($id) or empty($name)) {
				return;
			}
			$result = $this->db->update(
				$table_name, array(
				'name' => $name,
				), array(
				'ID' => $id
				)
			);
			if ($result) {
				return array('result' => 'update', 'message' => __('Group successfully updated.', 'unify_sgc_sms'));
			}
		}

		/**
		 * Checks if a subscriber group with the specified name already exists in the database.
		 *
		 * This method is used to prevent the creation of duplicate subscriber groups by validating
		 * if a group with the given name already exists. It queries the database using the group name
		 * and returns the result.
		 *
		 * @param string $name The name of the subscriber group to check for duplication.
		 * @return array|null|object|void If a duplicate group exists, returns the group data; otherwise, returns null.
		 * @access private
		 */
		private function is_duplicate_group($name) {
			global $wpdb;
			$sql = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}unify_sgc_sms_subscribers_group WHERE name = %s",
				$name
			);
			$result = $wpdb->get_row($sql);

			return $result;
		}

		/**
		 * Checks if a subscriber with the given mobile number already exists in the database.
		 *
		 * This method is used to ensure that there are no duplicate entries for a mobile number in the subscribers' list.
		 * It performs a database query checking for the existence of the specified mobile number. Optionally,
		 * it can also consider the group ID and exclude a specific subscriber ID (useful for update operations).
		 *
		 * @param string $mobile_number The mobile number to check for duplication.
		 * @param int|null $group_id Optional. The group ID to narrow down the search. Defaults to null.
		 * @param int|null $id Optional. The subscriber ID to exclude from the check (useful when updating). Defaults to null.
		 * @return array|null|object|void The subscriber data if a duplicate exists; otherwise, returns null.
		 * @access private
		 */
		private function is_duplicate($mobile_number, $group_id = null, $id = null) {
			global $wpdb;
			$sql = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}unify_sgc_sms_subscribers WHERE mobile = %s",
				$mobile_number
			);

			if ($group_id) {
				$sql .= $wpdb->prepare(" AND group_id = %d", $group_id);
			}

			if ($id) {
				$sql .= $wpdb->prepare(" AND id != %d", $id);
			}

			$result = $wpdb->get_row($sql);
			return $result;
		}
	}
	