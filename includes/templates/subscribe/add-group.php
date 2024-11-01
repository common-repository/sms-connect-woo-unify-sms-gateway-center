<?php
	if (!defined('ABSPATH'))
		exit;
?>
<div class="wrap">
	<h2><?php esc_html_e('Add Group', 'unify_sgc_sms'); ?></h2>
	<p class="color-333">You can only send maximum 1000 mobile numbers from a group. Do not select multiple groups and kindly restrict per group to maximum 1000 numbers.</p>
	<form action="" method="post">
		<?php wp_nonce_field('unifysgcsms_add_group_nonce', 'unifysgcsms_add_group_nonce_field'); ?>
		<table>
			<tr>
				<td colspan="2"><h3><?php esc_html_e('Add New Group:', 'unify_sgc_sms'); ?></h3></td>
			</tr>
			<tr>
				<td><span class="label_td" for="unifysgcsms_notify_group_name"><?php esc_html_e('Name', 'unify_sgc_sms'); ?>:</span></td>
				<td><input type="text" id="unifysgcsms_notify_group_name" name="unifysgcsms_notify_group_name"/></td>
			</tr>

			<tr>
				<td colspan="2">
					<a style="margin-top:30px;margin-left:20px" href="admin.php?page=unify_sgc_sms_subscriber_groups" class="button"><?php esc_html_e('Back', 'unify_sgc_sms'); ?></a>
					<input style="margin-top:30px" type="submit" class="button-primary" name="unifysgcsms_add_group" value="<?php esc_html_e('Add', 'unify_sgc_sms'); ?>"/>
				</td>
			</tr>
		</table>
	</form>
</div>  