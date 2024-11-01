<?php

class WPC_admin_screen_settings_overview_conversations
{
	protected static $instance = null;

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

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
				$query[$i]->id = $data->id;
				$query[$i]->contact = $data->contact;
				$query[$i]->contact_slug = $data->contact_slug;
				$query[$i]->last_message = $data->last_message;
			endforeach;
		endif;

		if ( ! empty( $query ) ) rsort( $query );
		$pagi = wpc_paginate( $query, 10, true );
		$query = wpc_paginate( $query, 10 );

		return array( 'data' => $query, 'pagi' => $pagi );

	}

	public function screen() {

		?>
		
			<p><a href="admin.php?page=wpchats" class="button">&lsaquo; Back to overview</a></p>

			<h2 style="color:#555;text-transform:uppercase">Conversations</h2>

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

		<?php

	}

}

WPC_admin_screen_settings_overview_conversations::instance()->screen();