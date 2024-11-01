<?php
	if (!defined('ABSPATH'))
		exit;
	$reqPage = isset($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';
?>
<div class="wrap">
	<div class="unify_sgc_sms_top_image_logo"></div>
	<h2><?php esc_html_e('Subscribers', 'unify_sgc_sms'); ?></h2>

	<div class="unify_sgc_sms_notifications-button-group">
		<a href="admin.php?page=unify_sgc_sms_subscribers&action=add" class="button"><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Add Subscriber', 'unify_sgc_sms'); ?>
		</a>
		<a href="admin.php?page=unify_sgc_sms_subscriber_groups" class="button"><span class="dashicons dashicons-category"></span> <?php esc_html_e('Manage Groups', 'unify_sgc_sms'); ?>
		</a>
		<a href="admin.php?page=unify_sgc_sms_subscribers&action=import" class="button"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Import Subscribers', 'unify_sgc_sms'); ?>
		</a>
	</div>

	<form id="subscribers-filter" method="get">
		<input type="hidden" name="page" value="<?php echo !empty($reqPage) ? esc_attr($reqPage) : ''; ?>"/>
		<?php $list_table->search_box(esc_attr__('Search', 'unify_sgc_sms'), 'search_id'); ?>
		<?php $list_table->display(); ?>
	</form>
</div>