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

	if (!function_exists('unifysgcsms_get_group_by_id')) {

		/**
		 * Retrieves the name of a subscriber group by its ID.
		 *
		 * This function is a utility to fetch the name of a group from the 'unify_sgc_sms_subscribers_group' table
		 * in the WordPress database, based on a provided group ID. It uses global WordPress database ($wpdb) functionality
		 * to perform a prepared SQL query. This is useful for operations that require displaying or processing the name
		 * of a specific subscriber group.
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 * @global string $table_prefix The prefix for the WordPress database tables.
		 *
		 * @param int|null $group_id The ID of the group to retrieve. If null, no query is executed.
		 * @return string|null The name of the group if found, or null if not found or if $group_id is null.
		 */
		function unifysgcsms_get_group_by_id($group_id = null) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'unify_sgc_sms_subscribers_group';
			$result = $wpdb->get_row($wpdb->prepare("SELECT `name` FROM `{$table_name}` WHERE `ID` = %d", $group_id));
			if ($result) {
				return $result->name;
			}
		}

	}

	/**
	 * WordPress List Table extension for displaying and managing subscribers in the unifysgcsms Alerts plugin.
	 *
	 * This class extends the WP_List_Table to provide a customizable table structure
	 * for displaying and managing subscribers in the WordPress admin interface.
	 *
	 * @package YourPluginName
	 * @since Version 1.0.0
	 */
	class UNIFYSGCSMS_ALERTS_Subscribers_List_Table extends WP_List_Table {

		/**
		 * Array to store the data to be displayed in the table.
		 *
		 * @var array
		 */
		var $data;

		/**
		 * Constructor for the subscribers list table.
		 *
		 * This constructor initializes the list table for displaying subscribers in the WordPress admin area.
		 * It sets up the singular and plural labels for records, determines whether AJAX is supported, and fetches
		 * the data from the 'unify_sgc_sms_subscribers' table in the WordPress database. The data is retrieved
		 * as an array and stored in the object's 'data' property for later use in rendering the table.
		 *
		 * @global string $status The current status filter.
		 * @global int $page The current page number.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		function __construct() {
			global $status, $page, $wpdb;

			//Set parent defaults
			parent::__construct(array(
				'singular' => 'ID',
				'plural' => 'ID',
				'ajax' => false
			));
			$table_name = $wpdb->prefix . 'unify_sgc_sms_subscribers';
			$this->data = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
		}

		/**
		 * Render the default columns for the subscribers list table.
		 *
		 * This function outputs the content for each default column on the subscribers list table
		 * in the WordPress admin area. It uses a switch statement to handle different column names
		 * and formats the output accordingly. For specific columns like 'group_ID' and 'date',
		 * it performs additional processing to display the group name and formatted date.
		 *
		 * @param array $item The current item's data.
		 * @param string $column_name The name of the current column.
		 * @return string The formatted data to be displayed in the column.
		 */
		function column_default($item, $column_name) {
			switch ($column_name) {
				case 'name':
				case 'mobile':
					return $item[$column_name];
				case 'group_ID':
					return unifysgcsms_get_group_by_id($item[$column_name]);
				case 'date':
					return sprintf(__('%s <span class="unifysgcsmsapi_alerts-time">Time: %s</span>', 'unify_sgc_sms'), date_i18n('Y-m-d', strtotime($item[$column_name])), date_i18n('H:i:s', strtotime($item[$column_name])));
				default:
					return print_r($item, true);
			}
		}

		/**
		 * Generate the content for the 'name' column with action links.
		 *
		 * This function creates the content for the 'name' column in the subscribers list table.
		 * It includes action links (Edit and Delete) allowing the user to perform operations on each record.
		 * The actions are formatted as HTML anchor tags with query parameters indicating the page,
		 * action, and the ID of the record.
		 *
		 * @param array $item The current item's data.
		 * @return string The formatted name field with action links for editing and deleting the record.
		 */
		function column_name($item) {
			$page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';

			$actions = array(
				'edit' => sprintf(
					'<a href="?page=%s&action=%s&ID=%s">' . __('Edit', 'unify_sgc_sms') . '</a>',
					esc_attr($page),
					esc_attr('edit'),
					esc_attr($item['ID'])
				),
				'delete' => sprintf(
					'<a href="?page=%s&action=%s&ID=%s">' . __('Delete', 'unify_sgc_sms') . '</a>',
					esc_attr($page),
					esc_attr('delete'),
					esc_attr($item['ID'])
				),
			);
			return sprintf('%1$s %3$s',
				/* $1%s */ esc_attr($item['name']),
				/* $2%s */ esc_attr($item['ID']),
				/* $2%s */ $this->row_actions($actions)
			);
		}

		/**
		 * Call back
		 * @param type $item
		 * @return type
		 */
		function column_cb($item) {
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/* $1%s */ $this->_args['singular'],
				/* $2%s */ esc_attr($item['ID'])
			);
		}
		
		/**
		 * Get columns
		 * @return string
		 */
		function get_columns() {
			$columns = array(
				'cb' => '<input type="checkbox" />',
				'name' => __('Name', 'unify_sgc_sms'),
				'mobile' => __('Mobile', 'unify_sgc_sms'),
				'group_ID' => __('Group', 'unify_sgc_sms'),
				'date' => __('Date', 'unify_sgc_sms'),
			);

			return $columns;
		}

		/**
		 * Sort
		 * @return array
		 */
		function get_sortable_columns() {
			$sortable_columns = array(
				'ID' => array('ID', true),
				'name' => array('name', false),
				'mobile' => array('mobile', false),
				'group_ID' => array('group_ID', false),
				'date' => array('date', false)
			);

			return $sortable_columns;
		}

		/**
		 * Get bulk action
		 * @return type
		 */
		function get_bulk_actions() {
			$actions = array(
				'bulk_delete' => __('Delete', 'unify_sgc_sms')
			);

			return $actions;
		}

		/**
		 * Bulk Action
		 * @global wpdb $wpdb
		 */
		function process_bulk_action() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'unify_sgc_sms_subscribers';
			$sanitizedSearchTerm = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

			if (!empty($sanitizedSearchTerm)) {
				$this->data = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM `{$table_name}` WHERE `name` LIKE %s OR `mobile` LIKE %s;",
						'%' . $wpdb->esc_like($sanitizedSearchTerm) . '%',
						'%' . $wpdb->esc_like($sanitizedSearchTerm) . '%'
					),
					ARRAY_A
				);
			}

			if ('bulk_delete' == $this->current_action()) {
				foreach ($_GET['id'] as $id) {
					$sanitized_id = absint(sanitize_text_field($id));
					$wpdb->delete($table_name, array('ID' => $sanitized_id));
				}

				$this->data = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
				echo '<div class="updated notice is-dismissible below-h2"><p>' . esc_html__('Items removed.', 'unify_sgc_sms') . '</p></div>';
			}

			if ('delete' == $this->current_action()) {
				$wpdb->delete($table_name, array('ID' => absint($_GET['ID'])));
				$this->data = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
				echo '<div class="updated notice is-dismissible below-h2"><p>' . esc_html__('Item removed.', 'unify_sgc_sms') . '</p></div>';
			}
		}
		
		/**
		 * Prepare items
		 * @global wpdb $wpdb
		 */
		function prepare_items() {
			global $wpdb;
			$per_page = 10;
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);
			$this->process_bulk_action();
			$data = $this->data;

			function unifysgcsms_usort_reorder_subscribers($a, $b) {
				$orderby = (!empty($_REQUEST['orderby']) ) ? sanitize_text_field(wp_unslash($_REQUEST['orderby'])) : 'date';
				$order = (!empty($_REQUEST['order']) ) ? sanitize_text_field(wp_unslash($_REQUEST['order'])) : 'desc';
				$result = strcmp($a[$orderby], $b[$orderby]);
				return ( $order === 'asc' ) ? $result : - $result;
			}

			usort($data, 'unifysgcsms_usort_reorder_subscribers');

			$current_page = $this->get_pagenum();

			$total_items = count($data);

			$data = array_slice($data, ( ( $current_page - 1 ) * $per_page), $per_page);

			$this->items = $data;

			$this->set_pagination_args(array(
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil($total_items / $per_page)
			));
		}
	}
	