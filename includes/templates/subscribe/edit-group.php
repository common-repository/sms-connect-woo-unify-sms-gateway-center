<?php
	if (!defined('ABSPATH'))
		exit;
?>
<div class="wrap">
	<h2><?php esc_html_e('Add Group', 'unify_sgc_sms'); ?></h2>
	<form action="" method="post">
		<?php wp_nonce_field('unifysgcsms_update_group_nonce', 'unifysgcsms_update_group_nonce_field'); ?>
		<table>
			<tr>
				<td colspan="2"><h3><?php esc_html_e('Group Info:', 'unify_sgc_sms'); ?></h3></td>
			</tr>
			<tr>
				<td><span class="label_td" for="unifysgcsms_notify_group_name"><?php esc_html_e('Name', 'unify_sgc_sms'); ?>:</span></td>
				<td><input type="text" id="unifysgcsms_notify_group_name" name="unifysgcsms_notify_group_name" value="<?php echo esc_attr($get_group->name); ?>"/></td>
				</td>
			</tr>

			<tr>
				<td colspan="2">
					<a href="admin.php?page=unifysgcsms_wa_alerts_subscriber_groups" class="button"><?php esc_html_e('Back', 'unify_sgc_sms'); ?></a>
					<input type="submit" class="button-primary" name="unifysgcsms_update_group" value="<?php esc_html_e('Add', 'unify_sgc_sms'); ?>"/>
				</td>
			</tr>
		</table>
	</form>
</div>