<?php
	if (!defined('ABSPATH'))
		exit;
	$reqPage = isset($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';
?>
<div class="wrap">
	<div class="unify_sgc_sms_top_image_logo"></div>
	<h2><?php esc_html_e('Groups', 'unify_sgc_sms'); ?></h2>
	<p class="color-333">You can only send maximum 1000 mobile numbers from a group. Do not select multiple groups and kindly restrict per group to maximum 1000 numbers.</p>
	<div class="unifysgcsms_notifications-button-group">
		<a href="admin.php?page=unify_sgc_sms_subscriber_groups&action=add" class="button"><span class="dashicons dashicons-groups"></span> <?php esc_html_e('Add Group', 'unify_sgc_sms'); ?></a>
	</div>

	<form id="subscribers-filter" method="get">
		<input type="hidden" name="page" value="<?php echo !empty($reqPage) ? esc_attr($reqPage) : ''; ?>"/>
		<?php $list_table->search_box(esc_attr__('Search', 'unify_sgc_sms'), 'search_id'); ?>
		<?php $list_table->display(); ?>
	</form>
</div>