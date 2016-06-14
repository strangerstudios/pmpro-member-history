<?php
/*
Plugin Name: Paid Memberships Pro - Member History Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-member-history/
Description: Display a history of a user's Membership on the User Profile for admins only.
Version: .2.2
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
        $invoices = $wpdb->get_results("SELECT mo.*, UNIX_TIMESTAMP(mo.timestamp) as timestamp, du.code_id as code_id FROM $wpdb->pmpro_membership_orders mo LEFT JOIN $wpdb->pmpro_discount_codes_uses du ON mo.id = du.order_id WHERE mo.user_id = '$user->ID' AND (mo.status = 'success' OR mo.status = 'cancelled' OR mo.status = 'refunded' OR mo.status = '') ORDER BY mo.timestamp DESC");
        $levelshistory = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = '$user->ID' ORDER BY id DESC");
	if($invoices || $levelshistory)
	{
		?>
		<hr />
		<h3><?php _e("Member History", "pmpro"); ?></h3>
		<ul id="member-history-filters" class="subsubsub">
			<li id="member-history-filters-orders"><a href="javascript:void(0);" class="current orders tab">Order History</a> <span>(<?php echo count($invoices); ?>)</span></li>
			<li id="member-history-filters-memberships">| <a href="javascript:void(0);" class="tab">Membership Levels History</a> <span>(<?php echo count($levelshistory); ?>)</span></li>
		</ul>
		<br class="clear"/>
		<div id="member-history-orders" class="widgets-holder-wrap">
		<?php if($invoices) { ?>
			<table class="wp-list-table widefat fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php _e('Date', 'pmpro'); ?></th>
					<th><?php _e('Membership Level', 'pmpro'); ?></th>
					<th><?php _e('Invoice ID', 'pmpro'); ?></th>
					<th><?php _e('Total Billed', 'pmpro'); ?></th>
					<th><?php _e('Discount Used', 'pmpro'); ?></th>
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
								if(empty($invoice->code_id)){
									echo '-';
								}else{
                                                                        $discountQuery = "SELECT c.code FROM $wpdb->pmpro_discount_codes c WHERE c.id = ".$invoice->code_id." LIMIT 1";                        
                                                                        $discount_code = $wpdb->get_row( $discountQuery );
									echo '<a href="admin.php?page=pmpro-discountcodes&edit='.$invoice->code_id.'">'.$discount_code->code.'</a>'; 
                                                                }
							?>
                                                </td>	
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
			<?php } else { echo 'No membership orders found.'; } ?>
		</div>
		<div id="member-history-memberships" class="widgets-holder-wrap" style="display: none;">
		<?php if($levelshistory) { ?>
			<table class="wp-list-table widefat fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php _e('Membership Level', 'pmpro'); ?></th>
					<th><?php _e('Start Date', 'pmpro'); ?></th>
					<th><?php _e('Date Modified', 'pmpro'); ?></th>
					<th><?php _e('End Date', 'pmpro'); ?></th>
					<th><?php _e('Level Cost', 'pmpro'); ?></th>
					<th><?php _e('Status', 'pmpro'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
				$count = 0;
				foreach($levelshistory as $levelhistory)
				{
					$level = pmpro_getLevel($levelhistory->membership_id);
					
					if($levelhistory->enddate === null)
						$levelhistory->enddate = "Never";
					else
						$levelhistory->enddate = date(get_option("date_format"), strtotime($levelhistory->enddate));
					?>
					<tr<?php if($count++ % 2 == 1) { ?> class="alternate"<?php } ?>>
						<td><?php echo $level->name;?></td>
						<td><?php echo date(get_option("date_format"), strtotime($levelhistory->startdate))?></td>
						<td><?php echo date(get_option("date_format"), strtotime($levelhistory->modified))?></td>
						<td><?php echo $levelhistory->enddate;?></td>
						<td><?php echo pmpro_getLevelCost($levelhistory, true, true)?></td>					
						<td>
							<?php 
								if(empty($levelhistory->status))
									echo '-';
								else
									echo $levelhistory->status; 
							?>
						</td>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php } else { echo 'No membership history found.'; } ?>
		</div>
		<script>
			//tabs
			jQuery(document).ready(function() {
				jQuery('#member-history-filters a.tab').click(function() {
					//which tab?
					var tab = jQuery(this).parent().attr('id').replace('member-history-filters-', '');
					
					//un select tabs
					jQuery('#member-history-filters a.tab').removeClass('current');
					
					//select this tab
					jQuery('#member-history-filters-'+tab+' a').addClass('current');
					
					//show orders?
					if(tab == 'orders')
					{
						jQuery('#member-history-memberships').hide();
						jQuery('#member-history-orders').show();
					}
					else
					{
						jQuery('div#member-history-orders').hide();
						jQuery('#member-history-memberships').show();
					}
				});
			});
		</script>
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
