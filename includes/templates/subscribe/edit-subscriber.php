<?php
	if (!defined('ABSPATH'))
		exit;
?>
<div class="wrap">
	<h2><?php esc_html_e('Edit Subscriber', 'unify_sgc_sms'); ?></h2>
	<form action="" method="post">
		<?php wp_nonce_field('unifysgcsms_update_subscribe_nonce', 'unifysgcsms_update_subscribe_nonce_field'); ?>
		<table>
			<tr>
				<td colspan="2"><h3><?php esc_html_e('Subscriber Info:', 'unify_sgc_sms'); ?></h3></td>
			</tr>
			<tr>
				<td><span class="label_td" for="unifysgcsms_notify_subscribe_name"><?php esc_html_e('Name', 'unify_sgc_sms'); ?>:</span></td>
				<td><input type="text" id="unifysgcsms_notify_subscribe_name" name="unifysgcsms_notify_subscribe_name" value="<?php echo esc_attr($get_subscribe->name); ?>"/></td>
			</tr>
			<tr>
				<td><span class="label_td" for="unifysgcsms_notify_subscribe_mobile"><?php esc_html_e('Mobile', 'unify_sgc_sms'); ?>:</span></td>
				<td><input type="text" name="unifysgcsms_notify_subscribe_mobile" id="unifysgcsms_notify_subscribe_mobile" value="<?php echo esc_attr($get_subscribe->mobile); ?>" class="code"/></td>
			</tr>
			<?php if ($this->subscribe->get_groups()): ?>
					<tr>
						<td><span class="label_td" for="unifysgcsms_notify_group_name"><?php esc_html_e('Group', 'unify_sgc_sms'); ?>:</span></td>
						<td>
							<select name="unifysgcsms_notify_group_name" id="unifysgcsms_notify_group_name">
								<?php foreach ($this->subscribe->get_groups() as $items): ?>
									<option value="<?php echo esc_attr($items->ID); ?>" <?php selected($get_subscribe->group_ID, $items->ID); ?>>
										<?php echo esc_html($items->name); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php else: ?>
					<tr>
						<td><span class="label_td" for="unifysgcsms_alerts_group_name"><?php esc_html_e('Group', 'unify_sgc_sms'); ?>:</span></td>
						<td><?php echo sprintf(esc_html__('There is no group! <a href="%s">Add</a>', 'unify_sgc_sms'), esc_url('admin.php?page=unify_sgc_sms_subscriber_groups&action=add')); ?></td>
					</tr>
			<?php endif; ?>
			<tr>
				<td colspan="2">
					<a href="admin.php?page=unifysgcsms_wa_alerts_subscribers" class="button"><?php esc_html_e('Back', 'unify_sgc_sms'); ?></a>
					<input type="submit" class="button-primary" name="unifysgcsms_update_subscribe" value="<?php esc_html_e('Update', 'unify_sgc_sms'); ?>"/>
				</td>
			</tr>
		</table>
	</form>
</div>