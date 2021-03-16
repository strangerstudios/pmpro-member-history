<?php
/*
Plugin Name: Paid Memberships Pro - Member History Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-member-history/
Description: Display a history of a user's Membership on the User Profile for admins only.
Version: .3.1
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/

/**
 * Add the history view to the user profile
 *
 */
function pmpro_member_history_profile_fields( $user ) {
	global $current_user;
	$membership_level_capability = apply_filters( 'pmpro_edit_member_capability', 'manage_options' );

	if ( ! current_user_can( $membership_level_capability ) ) {
		return false;
	}

	global $wpdb;

	//Show all invoices for user
	$invoices = $wpdb->get_results("SELECT mo.*, UNIX_TIMESTAMP(mo.timestamp) as timestamp, du.code_id as code_id FROM $wpdb->pmpro_membership_orders mo LEFT JOIN $wpdb->pmpro_discount_codes_uses du ON mo.id = du.order_id WHERE mo.user_id = '$user->ID' ORDER BY mo.timestamp DESC");	

	$levelshistory = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id = '$user->ID' ORDER BY id DESC");

	$totalvalue = $wpdb->get_var("SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE user_id = '$user->ID' AND status NOT IN('token','review','pending','error','refunded')");

	if ( $invoices || $levelshistory ) { ?>
		<hr />
		<h3><?php esc_html_e( 'Member History', 'pmpro-member-history' ); ?></h3>
		<p><strong><?php esc_html_e( 'Total Paid', 'pmpro-member-history' ); ?></strong> <?php echo pmpro_formatPrice( $totalvalue ); ?></p>
		<ul id="member-history-filters" class="subsubsub">
			<li id="member-history-filters-orders"><a href="javascript:void(0);" class="current orders tab"><?php esc_html_e( 'Order History', 'pmpro-member-history' ); ?></a> <span>(<?php echo count( $invoices ); ?>)</span></li>
			<li id="member-history-filters-memberships">| <a href="javascript:void(0);" class="tab"><?php esc_html_e( 'Membership Levels History', 'pmpro-member-history' ); ?></a> <span>(<?php echo count( $levelshistory ); ?>)</span></li>
		</ul>
		<br class="clear" />
		<div id="member-history-orders" class="widgets-holder-wrap">
		<?php if ( $invoices ) { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Invoice ID', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Level', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Level ID', 'pmpro-member-history' ); ?>
					<th><?php esc_html_e( 'Total Billed', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Discount Code', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pmpro-member-history' ); ?></th>
					<?php do_action('pmpromh_orders_extra_cols_header');?>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ( $invoices as $invoice ) { 
					$level = pmpro_getLevel( $invoice->membership_id );
					?>
					<tr>
						<td><?php echo date_i18n( get_option( 'date_format'), $invoice->timestamp ); ?></td>
						<td class="order_code column-order_code has-row-actions">
							<a href="<?php echo add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url('admin.php' ) ); ?>"><?php echo $invoice->code; ?></a><br />
							<div class="row-actions">
								<span class="edit">
									<a title="<?php esc_html_e( 'Edit', 'pmpro-member-history' ); ?>" href="<?php echo add_query_arg( array( 'page' => 'pmpro-orders', 'order' => $invoice->id ), admin_url('admin.php' ) ); ?>"><?php esc_html_e( 'Edit', 'pmpro-member-history' ); ?></a>
								</span> |
								<span class="print">
									<a target="_blank" title="<?php _e( 'Print', 'pmpro-member-history' ); ?>" href="<?php echo add_query_arg( array( 'action' => 'pmpro_orders_print_view', 'order' => $invoice->id ), admin_url('admin-ajax.php' ) ); ?>"><?php esc_html_e( 'Print', 'pmpro-member-history' ); ?></a>
								</span>
								<?php if ( function_exists( 'pmpro_add_email_order_modal' ) ) { ?>
									 |
									<span class="email">
										<a title="<?php esc_html_e( 'Email', 'pmpro-member-history' ); ?>" href="#TB_inline?width=600&height=200&inlineId=email_invoice" class="thickbox email_link" data-order="<?php echo esc_attr( $invoice->id ); ?>"><?php _e( 'Email', 'pmpro-member-history' ); ?></a>
									</span>
								<?php } ?>
							</div> <!-- end .row-actions -->
						</td>
						<td><?php if ( ! empty( $level ) ) { echo $level->name; } else { _e( 'N/A', 'pmpro-member-history'); } ?></td>
						<td><?php if ( ! empty( $level ) ) { echo $level->id; } else { _e( 'N/A', 'pmpro-member-history'); } ?></td>
						<td><?php echo pmpro_formatPrice( $invoice->total ); ?></td>
						<td><?php 
							if ( empty( $invoice->code_id ) ) {
								echo '-';
							} else {
								$discountQuery = "SELECT c.code FROM $wpdb->pmpro_discount_codes c WHERE c.id = ".$invoice->code_id." LIMIT 1";
								$discount_code = $wpdb->get_row( $discountQuery );
								echo '<a href="admin.php?page=pmpro-discountcodes&edit='.$invoice->code_id.'">'. esc_attr( $discount_code->code ) . '</a>';
							}
						?></td>
						<td>
							<?php
								if ( empty( $invoice->status ) ) {
									echo '-';
								} else {
									echo esc_html( $invoice->status );
								}
							?>
						</td>
						<?php do_action( 'pmpromh_orders_extra_cols_body', $invoice ); ?>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php } else { 
				esc_html_e( 'No membership orders found.', 'pmpro-member-history' );
			} ?>
		</div>
		<div id="member-history-memberships" class="widgets-holder-wrap" style="display: none;">
		<?php if ( $levelshistory ) { ?>
			<table class="wp-list-table widefat striped fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Level ID', 'pmpro-member-history' ); ?>
					<th><?php esc_html_e( 'Level', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Start Date', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Date Modified', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'End Date', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Level Cost', 'pmpro-member-history' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pmpro-member-history' ); ?></th>
					<?php do_action( 'pmpromh_member_history_extra_cols_header' ); ?>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach ( $levelshistory as $levelhistory ) {
					$level = pmpro_getLevel( $levelhistory->membership_id );

					if ( $levelhistory->enddate === null || $levelhistory->enddate == '0000-00-00 00:00:00' ) {
						$levelhistory->enddate = 'Never';
					} else {
						$levelhistory->enddate = date_i18n( get_option( 'date_format'), strtotime( $levelhistory->enddate ) );
					} ?>
					<tr>
						<td><?php if ( ! empty( $level ) ) { echo $level->id; } else { _e( 'N/A', 'pmpro-member-history' ); } ?></td>
						<td><?php if ( ! empty( $level ) ) { echo $level->name; } else { _e( 'N/A', 'pmpro-member-history' ); } ?></td>
						<td><?php echo ( $levelhistory->startdate === '0000-00-00 00:00:00' ? __('N/A', 'pmpro-member-history') : date_i18n( get_option( 'date_format' ), strtotime( $levelhistory->startdate ) ) ); ?></td>
						<td><?php echo date_i18n( get_option( 'date_format'), strtotime( $levelhistory->modified ) ); ?></td>
						<td><?php echo esc_html( $levelhistory->enddate ); ?></td>
						<td><?php echo pmpro_getLevelCost( $levelhistory, true, true ); ?></td>
						<td>
							<?php 
								if ( empty( $levelhistory->status ) ) {
									echo '-';
								} else {
									echo esc_html( $levelhistory->status ); 
								}
							?>
						</td>
						<?php do_action( 'pmpromh_member_history_extra_cols_body', $user, $level ); ?>
					</tr>
					<?php
				}
			?>
			</tbody>
			</table>
			<?php } else { 
				esc_html_e( 'No membership history found.', 'pmpro-member-history');
			} ?>
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

/**
 * Allow orders to be emailed from the member history section on user profile.
 *
 */
function pmpro_member_history_add_email_order_modal() {
	$screen = get_current_screen();
	if ( $screen->base == 'user-edit' || $screen->base == 'profile' ) {
		// Require the core Paid Memberships Pro Admin Functions.
		if ( defined( 'PMPRO_DIR' ) ) {
			require_once( PMPRO_DIR . '/adminpages/functions.php' );
		}

		// Load the email order modal.
		if ( function_exists( 'pmpro_add_email_order_modal' ) ) {
			pmpro_add_email_order_modal();
		}
	}
}
add_action( 'in_admin_header', 'pmpro_member_history_add_email_order_modal' );


/**
 * Display a Member Value report widget on the Memberships > Reports page.
 *
 */
global $pmpro_reports;
$pmpro_reports[ 'member_value' ] = __( 'Member Value Report', 'pmpro-member-history' );
function pmpro_report_member_value_widget() {	
	global $wpdb;
	
	$top_ten_members = get_transient( 'pmpro_member_history_top_ten_members', false );
	
	if ( empty( $top_ten_members ) ) {
		$sqlQuery = $wpdb->prepare("
			SELECT user_id, SUM(total) as totalvalue
			FROM $wpdb->pmpro_membership_orders
			WHERE membership_id > 0
				AND gateway_environment = %s
				AND status NOT IN('token','review','pending','error','refunded')
			GROUP BY user_id ORDER BY totalvalue DESC
			LIMIT 10
			", pmpro_getOption( 'gateway_environment' ) );
		$top_ten_members = $wpdb->get_results( $sqlQuery );
		set_transient( 'pmpro_member_history_top_ten_members', $top_ten_members, 3600 );
	}
	
	if ( empty ( $top_ten_members ) ) {
		esc_html_e( 'No paying members found.', 'pmpro-member-history' );
	} else {
		esc_html_e( 'Your Top 10 Members', 'pmpro-member-history' );
		?>
		<span id="pmpro_report_member_value" class="pmpro_report-holder">
		<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Member', 'pmpro-member-history' ) ;?></th>
				<th scope="col"><?php esc_html_e( 'Start Date', 'pmpro-member-history' ) ;?></th>
				<th scope="col"><?php esc_html_e( 'Total Value', 'pmpro-member-history' ) ;?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
				foreach ( $top_ten_members as $member ) {
					$totalvalue = $member->totalvalue;
					$theuser = get_userdata( $member->user_id );
					?>
					<tr>
						<td scope="row">
							<?php if ( ! empty( $theuser ) ) { ?>
								<a title="<?php esc_html_e( 'Edit User', 'pmpro-member-history' ); ?>" href="<?php echo get_edit_user_link( $theuser->ID ); ?>"><?php echo $theuser->display_name; ?></a>
							<?php } elseif ( $member->user_id > 0 ) { ?>
								[<?php _e( 'deleted', 'paid-memberships-pro' ); ?>]
							<?php } else { ?>
								[<?php _e( 'none', 'paid-memberships-pro' ); ?>]
							<?php } ?>
						</td>
						<td>
							<?php if ( ! empty( $theuser ) ) { ?>
								<?php echo date_i18n( get_option( 'date_format' ), strtotime( $theuser->user_registered, current_time( 'timestamp' ) ) ); ?>
							<?php } else { ?>
								-
							<?php } ?>
						</td>
						<td><?php echo pmpro_formatPrice( $totalvalue ); ?></td>
					</tr>
					<?php
				}
			?>
		</tbody>
		</table>
		<?php if ( function_exists( 'pmpro_report_member_value_page' ) ) { ?>
			<p class="pmpro_report-button">
				<a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=pmpro-reports&report=member_value' ); ?>"><?php esc_html_e('Details', 'pmpro-member-history' );?></a>
			</p>
		<?php } ?>
		</span>
		<?php
	}
}

/**
 * Display a custom report for Member Value.
 *
 */
function pmpro_report_member_value_page() {
?>
<h2><?php esc_html_e( 'Member Value Report', 'pmpro-member-history'); ?></h2>
<?php
	//vars
	global $wpdb;
	if ( isset( $_REQUEST['s'] ) ) {
		$s = sanitize_text_field( trim( $_REQUEST['s'] ) );
	} else {
		$s = '';
	}

	if ( isset( $_REQUEST['l'] ) ) {
		$l = sanitize_text_field( $_REQUEST['l'] );
	} else {
		$l = false;
	}
?>
	<form id="posts-filter" method="get" action="">
	<ul class="subsubsub">
		<li>
			<?php _e( 'Show', 'pmpro-member-history') ;?>
			<select name="l" onchange="jQuery( '#posts-filter' ).submit();">
				<option value="" <?php if( ! $l ) { ?>selected="selected"<?php } ?>><?php _e( 'All Levels', 'pmpro-member-history'); ?></option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
					foreach( $levels as $level ) { ?>
						<option value="<?php echo $level->id; ?>" <?php if ( $l == $level->id ) { ?>selected="selected"<?php } ?>><?php echo esc_html( $level->name ); ?></option>
					<?php } ?>
			</select>
		</li>
	</ul>
	<p class="search-box">
		<label class="hidden" for="post-search-input"><?php _e( 'Search Members', 'pmpro-member-history' );?>:</label>
		<input type="hidden" name="page" value="pmpro-reports" />
		<input type="hidden" name="report" value="member_value" />
		<input id="post-search-input" type="text" value="<?php echo esc_attr( $s ); ?>" name="s" />
		<input class="button" type="submit" value="<?php esc_html_e( 'Search Members', 'pmpro-member-history' ); ?>" />
	</p>
	<?php
		//some vars for the search
		if ( isset( $_REQUEST['pn'] ) ) {
			$pn = intval( $_REQUEST['pn'] );
		} else {
			$pn = 1;
		}

		if ( isset( $_REQUEST['limit'] ) ) {
			$limit = intval( $_REQUEST['limit'] );
		} else {
			/**
			 * Filter to set the default number of items to show per page
			 * on the Members List page in the admin.
			 *
			 * @since 1.8.4.5
			 *
			 * @param int $limit The number of items to show per page.
			 */
			$limit = apply_filters( 'pmpro_memberslist_per_page', 15 );
		}

		$end = $pn * $limit;
		$start = $end - $limit;

		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership, SUM(mo.total) as totalvalue FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id LEFT JOIN $wpdb->pmpro_membership_orders mo ON u.ID = mo.user_id LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id";

		if ( $s ) {
			$sqlQuery .= " WHERE mu.membership_id > 0 AND (u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%') ";
		} else {
			$sqlQuery .= " WHERE mu.membership_id > 0  ";
		}
		

		if( $l ) {
			$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql( $l ) . "' ";
		} else {
			$sqlQuery .= " AND mu.status = 'active' ";
		}
		
		$sqlQuery .= " AND mo.gateway_environment = '" . pmpro_getOption( 'gateway_environment' ) . "' ";
		$sqlQuery .= " AND mo.status NOT IN('token','review','pending','error','refunded') ";
		
		$sqlQuery .= "GROUP BY u.ID ORDER BY totalvalue DESC ";

		$sqlQuery .= "LIMIT $start, $limit";

		$sqlQuery = apply_filters( 'pmpro_members_list_sql', $sqlQuery );

		$theusers = $wpdb->get_results( $sqlQuery );

		// var_dump( $theusers );

		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");
		
		if ( $theusers ) { ?>
			<p class="clear"><?php printf(__( '%d members found.', 'pmpro-member-history' ), $totalrows ); ?></span></p>
		<?php } ?>
	<table class="widefat striped">
		<thead>
			<tr class="thead">
				<th><?php esc_html_e( 'ID', 'pmpro-member-history' ); ?></th>
				<th><?php esc_html_e( 'Username', 'pmpro-member-history' ); ?></th>
				<th><?php esc_html_e( 'Name', 'pmpro-member-history' ); ?></th>
				<th><?php esc_html_e( 'Current Membership', 'pmpro-member-history' ); ?></th>
				<th><?php esc_html_e( 'Joined', 'pmpro-member-history' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'pmpro-member-history' ); ?></th>
				<th><?php esc_html_e( 'Total Paid', 'pmpro-member-history' ); ?></th>
			</tr>
		</thead>
		<tbody id="users" class="list:user user-list">
			<?php
				foreach( $theusers as $auser ) {
					//get meta
					$theuser = get_userdata( $auser->ID );
					
					//get total value
					$totalvalue2 = $wpdb->get_var("SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE user_id = '$auser->ID' AND status NOT IN('review','pending','error','refunded')");
					?>
						<tr>
							<td><?php echo esc_html( $theuser->ID ); ?></td>
							<td class="username column-username">
								<?php echo get_avatar( $theuser->ID, 32 ); ?>
								<strong>
									<?php
										$userlink = '<a href="user-edit.php?user_id=' . esc_attr( $theuser->ID ) . '">' . esc_html( $theuser->user_login ) . '</a>';
										$userlink = apply_filters( 'pmpro_members_list_user_link', $userlink, $theuser );
										echo $userlink;
									?>
								</strong>
							</td>
							<td>
								<?php echo esc_html( $theuser->first_name ); ?> <?php echo esc_html( $theuser->last_name ); ?>
								<?php if ( ! empty( $theuser->first_name ) ) echo '<br />'; ?>
								<a href="mailto:<?php echo esc_attr( $theuser->user_email ); ?>"><?php echo $theuser->user_email; ?></a>
							</td>
							<td><?php echo esc_html( $auser->membership ); ?></td>
							<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $theuser->user_registered, current_time( 'timestamp' ) ) ); ?></td>
							<td>
								<?php
									if ( $auser->enddate ) {
										echo apply_filters( 'pmpro_memberslist_expires_column', date_i18n( get_option( 'date_format' ), $auser->enddate ), $auser );
									} else {
										echo __( apply_filters( 'pmpro_memberslist_expires_column', 'Never', $auser ), 'pmpro-member-history' );
									} ?>
							</td>
							<td>
								<?php echo pmpro_formatPrice( $totalvalue2 ); ?>
							</td>
						</tr>
					<?php
				}

				if( ! $theusers ) { ?>
				<tr>
					<td colspan="9"><p><?php esc_html_e( 'No members found.', 'pmpro-member-history'); ?> <?php if( $l ) { ?><a href="?page=pmpro-reports&report=member_value&s=<?php echo esc_attr( $s );?>"><?php _e( 'Search all levels', 'pmpro-member-history' ); ?></a>.<?php } ?></p></td>
				</tr>
				<?php } ?>
		</tbody>
	</table>
	</form>

	<?php
	echo pmpro_getPaginationString( $pn, $totalrows, $limit, 1, add_query_arg( array( 's' => urlencode( $s ), 'l' => $l, 'limit' => $limit ) ) );
	?>

<?php
}

/**
 * Function to add links to the plugin row meta
 *
 */
function pmpro_member_history_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-member-history.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-member-history/' )  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-member-history' ) ) . '">' . __( 'Docs', 'pmpro-member-history' ) . '</a>',
			'<a href="' . esc_url( 'http://paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-member-history' ) ) . '">' . __( 'Support', 'pmpro-member-history' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_member_history_plugin_row_meta', 10, 2 );
