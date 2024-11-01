<?php

class WPC_admin_screen_settings_overview
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function chart() {return; /*PRO feature*/}

	public function conversations() {

		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;
		$stmt = "SELECT distinct `PM_ID` FROM $table ORDER BY `ID` DESC";
		
		if ( $q = wpc_get_search_query() ) {
			$stmt = "SELECT distinct `PM_ID` FROM $table WHERE `message` LIKE '%$q%' ORDER BY `ID` DESC";
		}
		
		$query = $wpdb->get_results( $stmt );
		
		if ( ! empty( $query ) ) :;
			foreach ( $query as $i => $pm ) :;
				$query[$i] = wpc_get_conversation( $pm->PM_ID );
				if ( ! empty( $q ) ) {
					$sq = $wpdb->get_results( "SELECT distinct `ID` FROM $table WHERE `PM_ID` = '$pm->PM_ID' AND `message` LIKE '%$q%' ORDER BY `ID` DESC" );
					foreach ( $sq as $m ) {
						$query[$i]->last_message = wpc_get_message( $m->ID );
						break;
					}
				}
				if ( empty( $query[$i] ) ) $query[$i] = new stdClass();
				$query[$i]->id = ! empty( $query[$i]->last_message->ID ) ? $query[$i]->last_message->ID : 0; #~
				$data = $query[$i];
				unset( $query[$i] );
				$query[$i] = new stdClass;
				foreach ( $data as $prop => $val ) {
					$query[$i]->$prop = $val;
				}
			endforeach;
		endif;

		if ( ! empty( $query ) ) rsort( $query );
		$pagi = wpc_paginate( $query, 10, true );
		$query = wpc_paginate( $query, 10 );

		return array( 'data' => $query, 'pagi' => $pagi );

	}

	public function basic_stats() {
		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;
		$query = $wpdb->get_results( "SELECT distinct `PM_ID` FROM $table" );
		$stats = array();

		$stats['conversations'] = count( $query );
		$stats['messages'] = 0;

		if ( ! empty( $query ) ) {
			foreach ( $query as $pm ) {
				if ( !$pm_id = (int) $pm->PM_ID ) continue;
				$mq = $wpdb->get_results( "SELECT COUNT(*) AS count FROM $table WHERE `PM_ID` = '$pm_id'" );
				if ( $count = (int) $mq[0]->count ) {
					$stats['messages'] += $count;
				}
			}
		}

		$recipients = $wpdb->get_results( "SELECT distinct `recipient` FROM $table" );
		$senders = $wpdb->get_results( "SELECT distinct `sender` FROM $table" );
		$stats['using_chat'] = array();

		if ( ! empty( $recipients ) ) {
			foreach ( $recipients as $id ) {
				if( $user = (int) $id->recipient ) {
					$stats['using_chat'][] = $user;
				}
			}
		}
		if ( ! empty( $senders ) ) {
			foreach ( $senders as $id ) {
				if( $user = (int) $id->sender ) {
					$stats['using_chat'][] = $user;
				}
			}
		}

		if ( ! empty( $stats['using_chat'] ) ) {
			$stats['using_chat'] = array_filter( array_unique( $stats['using_chat'] ) );
		}
		$stats['using_chat'] = count( $stats['using_chat'] );

		$stats['reports'] = (int) get_option( "_wpc_overview_stats_reports" );
		$stats['awaiting_reports'] = (int) get_option( "_wpc_reports_count" );

		$stats['banned'] = $stats['moderators'] = $stats['lifetime_uploads_size'] = 0;

		foreach ( get_users() as $user ) {
			$stats['lifetime_uploads_size'] += (int) get_user_meta( $user->ID, "_wpc_lifetime_uploaded_size", 1 );
			if ( wpc_is_banned( $user->ID ) ) {
				$stats['banned'] += 1;
			} else if ( wpc_is_mod( $user->ID ) ) {
				$stats['moderators'] += 1;
			}
		}

		if ( ! empty( $stats['lifetime_uploads_size'] ) ) {
			$stats['lifetime_uploads_size'] = $stats['lifetime_uploads_size'] * 0.000001; // byte to MB
		}

		$stats['lifetime_uploads'] = (int) get_option( "_wpc_overview_stats_uploads" );
		$stats['sent_mails'] = (int) get_option( "_wpc_overview_stats_sent_nmails" );
		
		return (object) $stats;
	}

	public function screen() {

		?>

			<h2 id="conversations" style="text-align:center;padding:1em;color:#555;text-transform:uppercase;box-shadow:0 0 2px #bfbfbf;margin-top:1em">Conversations</h2>

			<?php $pms_data = $this->conversations(); ?>

			<div>
				<form method="get" action="admin.php" style="float:right;margin-bottom:7px">
					<input type="hidden" name="page" value="wpchats">
					<input type="hidden" name="conversations" value="1">
					<input type="text" name="q" value="<?php echo wpc_get_search_query(); ?>" placeholder="search .." />
				</form>
			</div>

			<?php if ( $pms = $pms_data['data'] ) : ?>

				<table class="widefat striped">
				<tr style="background: #ececec;">
					<th style="text-decoration:underline">Between</th>
					<th style="text-decoration:underline">And</th>
					<th style="text-decoration:underline">Total messages</th>
					<th style="text-decoration:underline">Last message</th>
					<th style="text-decoration:underline">actions</th>
				</tr>
				<?php foreach ( $pms as $pm ) : ?>
					<?php require 'overview-loop-single-pm.php'; ?>
				<?php endforeach; ?>
				<tr style="background: #ececec;">
					<th style="text-decoration:underline">Between</th>
					<th style="text-decoration:underline">And</th>
					<th style="text-decoration:underline">Total messages</th>
					<th style="text-decoration:underline">Last message</th>
					<th style="text-decoration:underline">actions</th>
				</tr>
				</table>
				<?php wpc_admin_the_pagination( $pms_data['pagi'], ( "admin.php?page=wpchats&conversations=1" . ( $q = wpc_get_search_query() ) ? "&q={$q}" : '' ) ); ?>

			<?php else : ?>
				<p>There are no conversations to show.</p>
			<?php endif; ?>

			<h2 id="basic-stats" style="text-align:center;padding:1em;color:#555;text-transform:uppercase;box-shadow:0 0 2px #bfbfbf;margin-top:1em">Basic stats</h2>

			<?php $stats = $this->basic_stats(); ?>
			<table class="widefat striped">
				
				<tr style="background: #ececec;">
					<th style="text-decoration:underline">Property</th>
					<th style="text-decoration:underline">Count</th>
				</tr>
				
				<tr>
					<td>Total conversations</td>
					<td><?php echo $stats->conversations; ?></td>
				</tr>
				<tr>
					<td>Total messages</td>
					<td><?php echo $stats->messages; ?></td>
				</tr>
				<tr>
					<td>Total chat users</td>
					<td><?php echo $stats->using_chat; ?></td>
				</tr>
				<tr>
					<td>Total banned users</td>
					<td><?php echo $stats->banned; ?></td>
				</tr>
				<tr>
					<td>Total moderators</td>
					<td><?php echo $stats->moderators; ?></td>
				</tr>
				<tr>
					<td>Total reports</td>
					<td><?php echo $stats->reports; ?></td>
				</tr>
				<tr>
					<td>Total reports awaiting moderation</td>
					<td><?php echo $stats->awaiting_reports; ?></td>
				</tr>
				<tr>
					<td>Total uploads</td>
					<td><?php echo $stats->lifetime_uploads; ?></td>
				</tr>
				<tr>
					<td>Total uploads size</td>
					<td><?php echo $stats->lifetime_uploads_size; ?> MB</td>
				</tr>
				<tr>
					<td>Total sent emails</td>
					<td><?php echo $stats->sent_mails; ?></td>
				</tr>

				<tr style="background: #ececec;">
					<th style="text-decoration:underline">Property</th>
					<th style="text-decoration:underline">Count</th>
				</tr>

			</table>


		<?php

	}

}

if ( isset( $_REQUEST['conversations'] ) ) {
	require 'overview-conversations.php';
} else {
	WPC_admin_screen_settings_overview::instance()->screen();
}