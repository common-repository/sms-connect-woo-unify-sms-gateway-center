<?php

	if (!defined('ABSPATH'))
		exit;
	
	/**
	 * Check if the WP_List_Table class exists, and if not, include the necessary file.
	 *
	 * This code ensures that the WP_List_Table class is available before using it.
	 * If the class doesn't exist, it includes the required file from the WordPress admin.
	 * This is useful for compatibility with different WordPress versions.
	 */
	if (!class_exists('WP_List_Table')) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	// Check if the function hasn't been defined already.
	if (!function_exists('unify_sgc_sms_get_total_subscribers')) {

		/**
		 * Retrieves the total number of subscribers from the database.
		 * 
		 * @global wpdb $wpdb WordPress database access abstraction object.
		 * @global string $wpdb->prefix Prefix for the WordPress database tables.
		 * @param int|null $group_id Optional. ID of the subscriber group to count. Defaults to null.
		 * 
		 * This function queries the database to count the number of subscribers. If a group ID is provided,
		 * it counts only subscribers belonging to that group. Otherwise, it counts all subscribers.
		 * 
		 * @return int|null The number of subscribers, or null if the query fails.
		 */
		function unify_sgc_sms_get_total_subscribers($group_id = null) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'unify_sgc_sms_subscribers';
			if ($group_id) {
				$result = $wpdb->query($wpdb->prepare("SELECT name FROM `{$table_name}` WHERE group_ID = %d", $group_id));
			} else {
				$query = $wpdb->prepare("SELECT * FROM `{$table_name}`");
				$result = $wpdb->get_results($query, ARRAY_A);
			}
			if ($result) {
				return $result;
			}
		}

	}

	/**
	 * Custom WordPress List Table for managing subscribers groups.
	 *
	 * This class extends the WP_List_Table class to create a custom table for managing subscribers groups in the WordPress admin area.
	 * It provides methods to display and manage the data in the table.
	 */
	class UNIFYSGCSMS_ALERTS_Subscribers_Groups_List_Table extends WP_List_Table {

		var $data;

		/**
		 * Constructor method for the custom WordPress List Table.
		 *
		 * Initializes the custom list table with necessary parameters, such as the singular and plural names, and retrieves data from the database.
		 */
		function __construct() {
			global $status, $page, $wpdb;

			// Set parent defaults
			parent::__construct(array(
				'singular' => 'ID', //singular name of the listed records
				'plural' => 'IDs', //plural name of the listed records
				'ajax' => false    //does this table support ajax?
			));
			// Retrieve data from the database
			$table_name = $wpdb->prefix . 'unify_sgc_sms_subscribers_group';
			$this->data = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
		}

		/**
		 * Determines the default output for each column in a list table.
		 *
		 * @param array $item Data for the current row.
		 * @param string $column_name The name of the current column.
		 *
		 * This function handles the display of each column's content in a list table. Depending on the column name,
		 * it either returns the corresponding item value or executes a specific function. For unrecognized columns,
		 * it prints the entire item array, which is useful for debugging.
		 *
		 * @return mixed The content to be displayed in the current column.
		 */
		function column_default($item, $column_name) {
			switch ($column_name) {
				case 'name':
					return $item[$column_name];
				case 'total_subscribers':
					return unify_sgc_sms_get_total_subscribers($item['ID']);
				default:
					return print_r($item, true); //Show the whole array for troubleshooting purposes
			}
		}

		/**
		 * Render the "name" column for each item in the list table.
		 *
		 * @param array $item The current item's data.
		 *
		 * @return string HTML content for the "name" column.
		 */
		function column_name($item) {
			//Table row actions
			$page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
			$actions = array(
				'edit' => sprintf(
					'<a href="?page=%s&action=%s&ID=%s">' . esc_html__('Edit', 'unify_sgc_sms') . '</a>',
					esc_attr($page),
					esc_attr('edit'),
					esc_attr($item['ID'])
				),
				'delete' => sprintf(
					'<a href="?page=%s&action=%s&ID=%s">' . esc_html__('Delete', 'unify_sgc_sms') . '</a>',
					esc_attr($page),
					esc_attr('delete'),
					esc_attr($item['ID'])
				)
			);

			//Return the title contents
			return sprintf('%1$s %3$s',
				/* $1%s */ esc_attr($item['name']),
				/* $1%s */ esc_attr($item['ID']),
				/* $2%s */ $this->row_actions($actions)
			);
		}

		/**
		 * Generates the checkbox column for each record in a list table.
		 *
		 * @param array $item Data for the current row item.
		 *
		 * This function creates a checkbox input for each row in the table. The checkbox name is derived from the table's singular label,
		 * and its value is set to the ID of the current item.
		 *
		 * @return string HTML checkbox element for the row.
		 */
		function column_cb($item) {
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/* $1%s */ $this->_args['singular'], //Let's repurpose the singular label of the table
				/* $2%s */ $item['ID']  //The checkbox value should correspond to the record's ID.
			);
		}

		/**
		 * Defines the columns to be displayed in the list table.
		 *
		 * This function specifies the columns that will appear in the list table. Each column is defined with a unique key and a label.
		 * The 'cb' column is used for checkboxes, while other columns like 'name' and 'total_subscribers' display specific data.
		 *
		 * @return array Associative array of columns, where the key is the column ID and the value is the column title.
		 */
		function get_columns() {
			$columns = array(
				'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
				'name' => __('Name', 'unify_sgc_sms'),
				'total_subscribers' => __('Total subscribers', 'unify_sgc_sms'),
			);

			return $columns;
		}

		/**
		 * Defines sortable columns for the list table.
		 *
		 * This function identifies which columns in the list table are sortable. Each sortable column is associated with
		 * a database field used for sorting. The boolean value indicates whether the column is already sorted by default.
		 *
		 * @return array An associative array of sortable columns, with each key being the column ID and the value an array of
		 *               the database field and a boolean indicating the default sort order.
		 */
		function get_sortable_columns() {
			$sortable_columns = array(
				'ID' => array('ID', true), //true means it's already sorted
				'name' => array('name', false), //true means it's already sorted
				'total_subscribers' => array('group_ID', false), //true means it's already sorted
			);

			return $sortable_columns;
		}

		/**
		 * Defines the bulk actions available for the list table.
		 *
		 * This function specifies the actions that can be applied to multiple items in the list table simultaneously. 
		 * It includes actions like 'bulk_delete' for deleting multiple items and 'group' for sending SMS messages 
		 * to selected groups.
		 *
		 * @return array An associative array of bulk actions where the key is the action name and the value is the action label.
		 */
		function get_bulk_actions() {
			$actions = array(
				'bulk_delete' => esc_html__('Delete', 'unify_sgc_sms'),
				'group' => esc_html__('Send SMS for Coupon Message Content', 'unify_sgc_sms'),
			);

			return $actions;
		}

		/**
		 * Processes the bulk actions for the list table.
		 *
		 * This function handles various bulk actions including search, delete, and custom actions like sending SMS messages to groups. 
		 * It detects the current action being performed and processes it accordingly, either by modifying database records or performing 
		 * other actions like sending messages.
		 */
		function process_bulk_action() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'unify_sgc_sms_subscribers_group';
			$table_name_subscriber = $wpdb->prefix . 'unify_sgc_sms_subscribers';

			//Detect when a bulk action is being triggered...
			// Search action
			$sanitizedSearchTerm = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

			if (!empty($sanitizedSearchTerm)) {
				$this->data = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM `{$table_name}` WHERE `name` LIKE %s;",
						'%' . $wpdb->esc_like($sanitizedSearchTerm) . '%'
					),
					ARRAY_A
				);
			}

			// Bulk delete action
			if ('bulk_delete' == $this->current_action()) {
				foreach ($_GET['id'] as $id) {
					$sanitized_id = absint(sanitize_text_field($id));
					$wpdb->delete($table_name, array('ID' => $sanitized_id));
				}

				$this->data = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
				echo '<div class="updated notice is-dismissible below-h2"><p>' . esc_html__('Items removed.', 'unify_sgc_sms') . '</p></div>';
			}

			//Group Msg
			if ('group' == $this->current_action()) {
				foreach ($_GET['id'] as $id) {
					$sanitized_id = absint(sanitize_text_field($id));

					// Validate the ID
					if ($sanitized_id > 0) {
						$data = $wpdb->get_results(
							$wpdb->prepare(
								"SELECT `mobile` FROM `{$table_name_subscriber}` WHERE `group_ID` = %d",
								$sanitized_id
							)
						);
					}
				}
				$subscribersPhones = array();
				foreach ($data as $key => $value) {
					$subscribersPhones[] = $value->mobile;
				}

				$options = get_option('unify_sgc_sms_option_name');
				$textMsg = $options['unify_sgc_sms_coupon_announcement'];
				$unifysgcsms_grp = new unifysgcsms($options['unify_sgc_sms_userId'], $options['unify_sgc_sms_password'], false);
				$result = $unifysgcsms_grp->sendunifysgcsmsPost($textMsg, $subscribersPhones);
				if ($result) {
					echo '<div class="updated notice is-dismissible below-h2"><p>' . esc_html__('Group Messages Sent Successfully.', 'unify_sgc_sms') . '</p></div>';
				} else {
					echo '<div class="updated notice is-dismissible below-h2"><p>' . sprintf(esc_html__('Group Messages has not been sent. Reason: %s', 'unify_sgc_sms'), esc_html($result)) . '</p></div>';
				}
			}

			// Single delete action
			if ('delete' == $this->current_action()) {
				$wpdb->delete($table_name, array('ID' => absint($_GET['ID'])));
				$this->data = $wpdb->get_results("SELECT * FROM `{$table_name}p`", ARRAY_A);
				echo '<div class="updated notice is-dismissible below-h2"><p>' . esc_html__('Item removed.', 'unify_sgc_sms') . '</p></div>';
			}
		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * This function sets up the list table with pagination, sorting, and any necessary data manipulation.
		 * It defines column headers, processes any bulk actions, sorts the data, and calculates pagination for display.
		 */
		function prepare_items() {
			global $wpdb; //This is used only if making any database queries

			/**
			 * First, lets decide how many records per page to show
			 */
			$per_page = 10;

			/**
			 * REQUIRED. Now we need to define our column headers. This includes a complete
			 * array of columns to be displayed (slugs & titles), a list of columns
			 * to keep hidden, and a list of columns that are sortable. Each of these
			 * can be defined in another method (as we've done here) before being
			 * used to build the value for our _column_headers property.
			 */
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();

			/**
			 * REQUIRED. Finally, we build an array to be used by the class for column
			 * headers. The $this->_column_headers property takes an array which contains
			 * 3 other arrays. One for all columns, one for hidden columns, and one
			 * for sortable columns.
			 */
			$this->_column_headers = array($columns, $hidden, $sortable);

			/**
			 * Optional. You can handle your bulk actions however you see fit. In this
			 * case, we'll handle them within our package just to keep things clean.
			 */
			$this->process_bulk_action();

			/**
			 * Instead of querying a database, we're going to fetch the example data
			 * property we created for use in this plugin. This makes this example
			 * package slightly different than one you might build on your own. In
			 * this example, we'll be using array manipulation to sort and paginate
			 * our data. In a real-world implementation, you will probably want to
			 * use sort and pagination data to build a custom query instead, as you'll
			 * be able to use your precisely-queried data immediately.
			 */
			$data = $this->data;

			/**
			 * This checks for sorting input and sorts the data in our array accordingly.
			 *
			 * In a real-world situation involving a database, you would probably want
			 * to handle sorting by passing the 'orderby' and 'order' values directly
			 * to a custom query. The returned data will be pre-sorted, and this array
			 * sorting technique would be unnecessary.
			 */
			function unifysgcsms_usort_reorder_groups($a, $b) {
				$orderby = (!empty($_REQUEST['orderby']) ) ? sanitize_text_field(wp_unslash($_REQUEST['orderby'])) : 'ID'; //If no sort, default to sender
				$order = (!empty($_REQUEST['order']) ) ? sanitize_text_field(wp_unslash($_REQUEST['order'])) : 'desc'; //If no order, default to asc
				$result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order

				return ( $order === 'asc' ) ? $result : - $result; //Send final sort direction to usort
			}

			usort($data, 'unifysgcsms_usort_reorder_groups');

			/**
			 * REQUIRED for pagination. Let's figure out what page the user is currently
			 * looking at. We'll need this later, so you should always include it in
			 * your own package classes.
			 */
			$current_page = $this->get_pagenum();

			/**
			 * REQUIRED for pagination. Let's check how many items are in our data array.
			 * In real-world use, this would be the total number of items in your database,
			 * without filtering. We'll need this later, so you should always include it
			 * in your own package classes.
			 */
			$total_items = count($data);

			/**
			 * The WP_List_Table class does not handle pagination for us, so we need
			 * to ensure that the data is trimmed to only the current page. We can use
			 * array_slice() to
			 */
			$data = array_slice($data, ( ( $current_page - 1 ) * $per_page), $per_page);

			/**
			 * REQUIRED. Now we can add our *sorted* data to the items property, where
			 * it can be used by the rest of the class.
			 */
			$this->items = $data;

			/**
			 * REQUIRED. We also have to register our pagination options & calculations.
			 */
			$this->set_pagination_args(array(
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page' => $per_page, //WE have to determine how many items to show on a page
				'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
			));
		}
	}
	