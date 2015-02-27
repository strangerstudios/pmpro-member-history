<?php
/*
Plugin Name: Paid Memberships Pro - Member History Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-member-history/
Description: Display a history of a user's Membership on the User Profile for admins only.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	Add the history view to the user profile
*/
function pmpro_member_history_profile_fields($user)
{
	global $current_user;
	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;
	global $wpdb;
	
	//Show all invoices for user
	$invoices = $wpdb->get_results("SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$user->ID' AND (status = 'success' OR status = 'cancelled' OR status = 'refunded' OR status = '') ORDER BY timestamp DESC");
	if($invoices)
	{
		?>
		<hr />
		<h3><?php _e("Member History", "pmpro"); ?></h3>
		<table class="wp-list-table widefat fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
		<thead>
			<tr>
				<th><?php _e('Date', 'pmpro'); ?></th>
				<th><?php _e('Membership Level', 'pmpro'); ?></th>
				<th><?php _e('Invoice ID', 'pmpro'); ?></th>
				<th><?php _e('Total Billed', 'pmpro'); ?></th>
				<th><?php _e('Status', 'pmpro'); ?></th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		<?php
			$count = 0;
			foreach($invoices as $invoice)
			{ 
				$level = pmpro_getLevel($invoice->membership_id);
				?>
				<tr<?php if($count++ % 2 == 1) { ?> class="alternate"<?php } ?>>
					<td><?php echo date(get_option("date_format"), $invoice->timestamp)?></td>
					<td><?php echo $level->name;?></td>
					<td><a href="admin.php?page=pmpro-orders&order=<?php echo $invoice->id;?>"><?php echo $invoice->code; ?></a></td>
					<td><?php echo pmpro_formatPrice($invoice->total);?></td>					
					<td>
						<?php 
							if(empty($invoice->status))
								echo '-';
							else
								echo $invoice->status; 
						?>
					</td>
					<td><a href="admin.php?page=pmpro-orders&order=<?php echo $invoice->id;?>"><?php _e('View Order', 'pmpro'); ?></a>
					</td>
				</tr>
				<?php
			}
		?>
		</tbody>
		</table>
		<?php
	}	
}
add_action('edit_user_profile', 'pmpro_member_history_profile_fields');
add_action('show_user_profile', 'pmpro_member_history_profile_fields');

/*
Function to add links to the plugin row meta
*/
function pmpro_member_history_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-member-history.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plugins-on-github/pmpro-member-history/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpro_member_history_plugin_row_meta', 10, 2);
