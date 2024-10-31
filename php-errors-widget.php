<?php
/*
	Plugin Name: PHP Errors Widget
	Description: Display PHP errors on the Dashboard.
	Author: Gyrus, T1gr0u
	Plugin URI: http://sltaylor.co.uk/blog/wordpress-dashboard-widget-php-errors-log/
	Version: 0.1
*/

/*  Copyright 2010

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// PHP errors widget
function slt_PHPErrorsWidget() {
	$slt_PHPErrors = unserialize( get_option('slt_PHPErrors') );
	$logfile = $slt_PHPErrors['path']; // Enter the server path to your logs file here
	$displayErrorsLimit = ( $slt_PHPErrors['errorLimits'] ) ? $slt_PHPErrors['errorLimits'] : 100; // The maximum number of errors to display in the widget
	$errorLengthLimit = ( $slt_PHPErrors['errorLength'] ) ? $slt_PHPErrors['errorLength'] : 300; // The maximum number of characters to display for each error
	$fileCleared = false;
	$userCanClearLog = current_user_can( 'manage_options' );
	// Clear file?
	if ( $userCanClearLog && isset( $_GET["slt-php-errors"] ) && $_GET["slt-php-errors"]=="clear" ) {
		$handle = fopen( $logfile, "w" );
		fclose( $handle );
		$fileCleared = true;
	}
	// Read file
	if ( file_exists( $logfile ) ) {
		$errors = file( $logfile );
		$errors = array_reverse( $errors );
		if ( $fileCleared ) echo '<p><em>File cleared.</em></p>';
		if ( $errors ) {
			echo '<p>'.count( $errors ).' error';
			if ( $errors != 1 ) echo 's';
			echo '.';
			if ( $userCanClearLog ) echo ' [ <b><a href="'.get_bloginfo("url").'/wp-admin/?slt-php-errors=clear" onclick="return confirm(\'Are you sure?\');">CLEAR LOG FILE</a></b> ]';
			echo '</p>';
			echo '<div id="slt-php-errors" style="height:250px;overflow:scroll;padding:2px;background-color:#faf9f7;border:1px solid #ccc;">';
			echo '<ol style="padding:0;margin:0;">';
			$i = 0;
			foreach ( $errors as $error ) {
				echo '<li style="padding:2px 4px 6px;border-bottom:1px solid #ececec;">';
				$errorOutput = preg_replace( '/\[([^\]]+)\]/', '<b>[$1]</b>', $error, 1 );
				if ( strlen( $errorOutput ) > $errorLengthLimit ) {
					echo substr( $errorOutput, 0, $errorLengthLimit ).' [...]';
				} else {
					echo $errorOutput;
				}
				echo '</li>';
				$i++;
				if ( $i > $displayErrorsLimit ) {
					echo '<li style="padding:2px;border-bottom:2px solid #ccc;"><em>More than '.$displayErrorsLimit.' errors in log...</em></li>';
					break;
				}
			}
			echo '</ol></div>';
		} else {
			echo '<p>No errors currently logged.</p>';
		}
	} else {
		echo '<p><em>There was a problem reading the error log file.<br/>
			You can setup the path <a href="'.get_bloginfo("url").'/wp-admin/options-general.php?page=php-errors-widget/php-errors-widget.php">here</a>
		</em></p>';
	}
}

// Add widgets
function slt_dashboardWidgets() {
	wp_add_dashboard_widget( 'slt-php-errors', 'PHP errors', 'slt_PHPErrorsWidget' );
}
add_action( 'wp_dashboard_setup', 'slt_dashboardWidgets' );



// Plugin Settings
global $wp_plugin;
function slt_PHPErrorsWidget_page() {
	$slt_PHPErrors = unserialize( get_option('slt_PHPErrors') );
	
	echo '<div class="wrap">
			<h2>PHP Error Widget</h2>
			<p>
				Change the path to your path for the PHP error log file.
			</p>' ;
			
	if ( ( $_POST['errorPath'] or $_POST['errorLimits'] or $_POST['errorLength'] ) and $_POST['submit'] ) {
		$slt_PHPErrors = array( 
				'path' => $_POST['errorPath'],
				'errorLimits' => ( ( $_POST['errorLimits'] and is_numeric( $_POST['errorLimits'] ) ) ? $_POST['errorLimits'] : 100),
				'errorLength' => (( $_POST['errorLength'] and is_numeric( $_POST['errorLength'] ) ) ? $_POST['errorLength'] : 300)
			);
		
		update_option('slt_PHPErrors', serialize( $slt_PHPErrors ) );
		echo '<p>
				Your path is now: <b>' . $slt_PHPErrors['path'] . '</b><br/>
				Error Limits: <b>' . $slt_PHPErrors['errorLimits'] . '</b><br/>
				Error length: <b>' . $slt_PHPErrors['errorLength'] . '</b><br/>
				<br/>
				<a href="' . $_SERVER["REQUEST_URI"] . '" class="button-primary">Back to \'PHP Errors Widget\'</a>
			</p>';
		
	} else {
		?>	
		 <p>
				<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<label>PHP error log file path: </label><input type="text" name="errorPath" value="<?php echo (($slt_PHPErrors['path']) ? $slt_PHPErrors['path']:'/home/path/logs/php-errors.log'); ?>" size="50" /><br/>
					<label>Error limits: </label><input type="text" name="errorLimits" value="<?php echo (($slt_PHPErrors['errorLimits']) ? $slt_PHPErrors['errorLimits']: 100); ?>" /> <i>(The maximum number of errors to display in the widget)</i><br/>
					<label>Error length: </label><input type="text" name="errorLength" value="<?php echo (($slt_PHPErrors['errorLength']) ? $slt_PHPErrors['errorLength']: 300); ?>" />  <i>(The maximum number of characters to display for each error)</i><br/>
					<br/>
					<input type="submit" name="submit" value="<?php _e('Save Options', 'phperrorswidget' ); ?>" id="phperrorswidget-button" class="button-primary" />
				</form>
			</p>
		<?php
	}
			
	echo '</div>';
}


function slt_PHPErrorsWidget_menu() {
	add_options_page( __( 'PHP Errors Widget', 'phperrorswidget' ), 'PHP Errors Widget', 9, __FILE__, slt_PHPErrorsWidget_page);
}
add_action('admin_menu', 'slt_PHPErrorsWidget_menu');



?>