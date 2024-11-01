<?php
	if (!defined('ABSPATH'))
		exit;
?>
<div class="wrap">
	<h2><?php esc_html_e('Add New Subscriber', 'unify_sgc_sms'); ?></h2>
	<form action="" method="post">
		<?php wp_nonce_field('unifysgcsms_add_subscribe_nonce', 'unifysgcsms_add_subscribe_nonce_field'); ?>
		<table>
			<tr>
				<td colspan="2"><h3><?php esc_html_e('Subscriber Info:', 'unify_sgc_sms'); ?></h3></td>
			</tr>
			<tr>
				<td><span class="form-input" for="unifysgcsms_notify_subscribe_name"><?php esc_html_e('Name', 'unify_sgc_sms'); ?>:</span></td>
				<td><input type="text" id="unifysgcsms_notify_subscribe_name" name="unifysgcsms_notify_subscribe_name" class="form-input" /></td>
			</tr>
			<tr>
				<td><span class="form-input" for="unifysgcsms_notify_subscribe_mobile"><?php esc_html_e('Mobile', 'unify_sgc_sms'); ?>:</span></td>
				<td><input type="text" name="unifysgcsms_notify_subscribe_mobile" id="unifysgcsms_notify_subscribe_mobile" class="form-input" /></td>
				<td><span class="form-input">Add with country code</span></td>
			</tr>
			<?php if ($this->subscribe->get_groups()): ?>
					<tr>
						<td><span class="form-input" for="unifysgcsms_notify_group_name"><?php esc_html_e('Group', 'unify_sgc_sms'); ?>:</span></td>
						<td>
							<select name="unifysgcsms_notify_group_name" id="unifysgcsms_notify_group_name" class="form-input">
								<?php foreach ($this->subscribe->get_groups() as $items): ?>
									<option value="<?php echo esc_attr($items->ID); ?>"><?php echo esc_html($items->name); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php else: ?>
					<tr class="form-input">
						<td><span for="unifysgcsms_alerts_group_name"><?php esc_html_e('Group', 'unify_sgc_sms'); ?>:</span></td>
						<td class="form-input"><?php echo sprintf(esc_html__('There is no group! <a href="%s">Add</a>', 'unify_sgc_sms'),  esc_url('admin.php?page=unify_sgc_sms_subscriber_groups')); ?></td>
					</tr>
			<?php endif; ?>
			<tr>
				<td colspan="2">
					<input type="hidden" name="unifysgcsms_add_subscribe_nonce" value="<?php echo esc_attr(wp_create_nonce('unifysgcsms_add_subscribe_nonce')); ?>" />
					<a style="margin-top:30px;margin-left:20px" href="admin.php?page=unify_sgc_sms_subscribers" class="button"><?php esc_html_e('Back', 'unify_sgc_sms'); ?></a>
					<input style="margin-top:30px" type="submit" class="button-primary" name="unifysgcsms_add_subscribe" value="<?php esc_html_e('Add', 'unify_sgc_sms'); ?>"/>
				</td>
			</tr>
		</table>
	</form>
</div>