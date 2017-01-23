<?php
/*
Plugin Name: Paid Memberships Pro - Member History Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-member-history/
Description: Display a history of a user's Membership on the User Profile for admins only.
Version: .2.4
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
	$invoices = $wpdb->get_results("SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$user->ID' ORDER BY timestamp DESC");
	$levelshistory = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = '$user->ID' ORDER BY id DESC");
	$totalvalue = $wpdb->get_var("SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE user_id = '$user->ID'");
	if($invoices || $levelshistory)
	{
		?>
		<hr />
		<h3><?php _e('Member History', 'pmpro'); ?></h3>
		<p><strong><?php _e('Total Paid', 'pmpro'); ?></strong> <?php echo pmpro_formatPrice($totalvalue); ?></p>
		<ul id="member-history-filters" class="subsubsub">
			<li id="member-history-filters-orders"><a href="javascript:void(0);" class="current orders tab"><?php _e('Order History', 'pmpro'); ?></a> <span>(<?php echo count($invoices); ?>)</span></li>
			<li id="member-history-filters-memberships">| <a href="javascript:void(0);" class="tab"><?php _e('Membership Levels History', 'pmpro'); ?></a> <span>(<?php echo count($levelshistory); ?>)</span></li>
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
						<td><?php if(!empty($level)) { echo $level->name; } else { _e('N/A', 'pmpro'); } ;?></td>
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
			<?php } else { _e('No membership orders found.', 'pmpro'); } ?>
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
										
					if($levelhistory->enddate === null || $levelhistory->enddate == '0000-00-00 00:00:00')
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
			<?php } else { _e('No membership history found.', 'pmpro'); } ?>
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


global $pmpro_reports;
$pmpro_reports['member_value'] = __('Member Value Report', 'pmpromh');
function pmpro_report_member_value_widget()
{	
?>
<p>Show Top 10 Members Here</p>
<?php
}

function pmpro_report_member_value_page()
{
?>
<h2>
	<?php _e('Member Value Report', 'pmpromh');?>
</h2>
<?php
	
	//vars
	global $wpdb;
	if(isset($_REQUEST['s']))
		$s = sanitize_text_field(trim($_REQUEST['s']));
	else
		$s = "";

	if(isset($_REQUEST['l']))
		$l = sanitize_text_field($_REQUEST['l']);
	else
		$l = false;

?>

	<form id="posts-filter" method="get" action="">
	<ul class="subsubsub">
		<li>
			<?php _e('Show', 'pmpro');?>
			<select name="l" onchange="jQuery('#posts-filter').submit();">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php _e('All Levels', 'pmpro');?></option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo $level->name?></option>
				<?php
					}
				?>
			</select>
		</li>
	</ul>
	<p class="search-box">
		<label class="hidden" for="post-search-input"><?php _e('Search Members', 'pmpro');?>:</label>
		<input type="hidden" name="page" value="pmpro-memberslist" />
		<input id="post-search-input" type="text" value="<?php echo esc_attr($s);?>" name="s"/>
		<input class="button" type="submit" value="<?php _e('Search Members', 'pmpro');?>"/>
	</p>
	<?php
		//some vars for the search
		if(isset($_REQUEST['pn']))
			$pn = intval($_REQUEST['pn']);
		else
			$pn = 1;

		if(isset($_REQUEST['limit']))
			$limit = intval($_REQUEST['limit']);
		else
		{
			/**
			 * Filter to set the default number of items to show per page
			 * on the Members List page in the admin.
			 *
			 * @since 1.8.4.5
			 *
			 * @param int $limit The number of items to show per page.
			 */
			$limit = apply_filters('pmpro_memberslist_per_page', 15);
		}

		$end = $pn * $limit;
		$start = $end - $limit;

		if($s)
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels mo ON mu.membership_id = mo.id ";

			$sqlQuery .= " WHERE mu.membership_id > 0 AND (u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%') ";

			if($l)
				$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
			else
				$sqlQuery .= " AND mu.status = 'active' ";

			$sqlQuery .= "GROUP BY u.ID ORDER BY u.user_registered DESC ";

			$sqlQuery .= "LIMIT $start, $limit";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership, SUM(mo.total) as totalvalue FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id LEFT JOIN $wpdb->pmpro_membership_orders mo ON u.ID = mo.user_id";
			
			$sqlQuery .= " WHERE mu.membership_id > 0  ";

			if($l)
				$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
			else
				$sqlQuery .= " AND mu.status = 'active' ";
			
			$sqlQuery .= " AND mo.status NOT IN('review','pending','error','refunded') ";
			
			$sqlQuery .= "GROUP BY u.ID ORDER BY totalvalue DESC ";

			$sqlQuery .= "LIMIT $start, $limit";
		}

		$sqlQuery = apply_filters("pmpro_members_list_sql", $sqlQuery);

		$theusers = $wpdb->get_results($sqlQuery);
		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");

//		d($theusers);
		if($theusers)
		{
			?>
			<p class="clear"><?php printf(__("%d members found.", "pmpro"), $totalrows);?></span></p>
			<?php
		}
	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th><?php _e('ID', 'pmpro');?></th>
				<th><?php _e('Username', 'pmpro');?></th>
				<th><?php _e('Name', 'pmpro');?></th>
				<th><?php _e('Current&nbsp;Membership', 'pmpro');?></th>
				<th><?php _e('Joined', 'pmpro');?></th>
				<th><?php _e('Expires', 'pmpro'); ?></th>
				<th><?php _e('Total&nbsp;Paid', 'pmpro');?></th>
			</tr>
		</thead>
		<tbody id="users" class="list:user user-list">
			<?php
				$count = 0;
				foreach($theusers as $auser)
				{
					//get meta
					$theuser = get_userdata($auser->ID);
					
					//get total value
					$totalvalue2 = $wpdb->get_var("SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE user_id = '$auser->ID' AND status NOT IN('review','pending','error','refunded')");
					?>
						<tr <?php if($count++ % 2 == 0) { ?>class="alternate"<?php } ?>>
							<td><?php echo $theuser->ID; ?></td>
							<td class="username column-username">
								<?php echo get_avatar($theuser->ID, 32); ?>
								<strong>
									<?php
										$userlink = '<a href="user-edit.php?user_id=' . $theuser->ID . '">' . $theuser->user_login . '</a>';
										$userlink = apply_filters("pmpro_members_list_user_link", $userlink, $theuser);
										echo $userlink;
									?>
								</strong>
							</td>
							<td>
								<?php echo $theuser->first_name; ?> <?php echo $theuser->last_name; ?>
								<?php if(!empty($theuser->first_name)) echo '<br />'; ?>
								<a href="mailto:<?php echo esc_attr($theuser->user_email); ?>"><?php echo $theuser->user_email; ?></a>
							</td>
							<td><?php echo $auser->membership; ?></td>
							<td><?php echo date(get_option("date_format"), strtotime($theuser->user_registered, current_time("timestamp"))); ?></td>
							<td>
								<?php
									if($auser->enddate)
										echo apply_filters("pmpro_memberslist_expires_column", date(get_option('date_format'), $auser->enddate), $auser);
									else
										echo __(apply_filters("pmpro_memberslist_expires_column", "Never", $auser), "pmpro");
								?>
							</td>
							<td>
								WRONG: <?php echo pmpro_formatPrice($auser->totalvalue); ?><br />
								RIGHT: <?php echo pmpro_formatPrice($totalvalue2); ?>						
							</td>
						</tr>
					<?php
				}

				if(!$theusers)
				{
				?>
				<tr>
					<td colspan="9"><p><?php _e("No members found.", "pmpro"); ?> <?php if($l) { ?><a href="?page=pmpro-memberslist&s=<?php echo esc_attr($s);?>"><?php _e("Search all levels", "pmpro");?></a>.<?php } ?></p></td>
				</tr>
				<?php
				}
			?>
		</tbody>
	</table>
	</form>

	<?php
	echo pmpro_getPaginationString($pn, $totalrows, $limit, 1, add_query_arg(array("s" => urlencode($s), "l" => $l, "limit" => $limit)));
	?>

<?php
}

/*
Function to add links to the plugin row meta
*/
function pmpro_member_history_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-member-history.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plus-add-ons/pmpro-member-history/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpro_member_history_plugin_row_meta', 10, 2);
