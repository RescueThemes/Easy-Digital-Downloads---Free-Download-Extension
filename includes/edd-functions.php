<?php

/**
 * All modified EDD native functions will be put all together here for better tracking
 * in order to adapt changes with EDD future updates.
 */

/**
 * Get final price of a download after discount
 * 
 * Modified From:
 * includes/download-functions.php -> edd_price()
 * Modified Parts:
 * Remove the price as a number, without the html formatting.
 * 
 * @param  int   $download_id ID of the download
 * @return float              Download price
 */
function vp_edd_fd_get_calculated_price($download_id)
{
	if( edd_has_variable_prices( $download_id ) ) {
		$prices = edd_get_variable_prices( $download_id );
		// Return the lowest price
		$price_float = 0;
		foreach($prices as $key => $value)
			if( ( ( (float)$prices[$key]['amount']) < $price_float) or ($price_float==0) )
				$price_float = (float)$prices[$key]['amount'];
		$price = edd_sanitize_amount($price_float);
	} else {
		$price = edd_get_download_price( $download_id );
	}

	if( edd_use_taxes() && edd_taxes_on_prices() )
		$price += edd_calculate_tax( $price );

	return $price;
}

function edd_add_download_as_payment( $download, $file_key, $user_info, $ip, $payment ) {
#echo'<pre>';var_dump( $download, $file_key, $user_info, $ip, $payment );die;#!DEBUG remove
	if ( $payment < 0 ) $payment = 0;
	$date = date( 'Y-m-d H:i:s' );
	if ( ! isset( $user_info['discount'] ) ) {
		$user_info['discount'] = 'none';
	}
  $key = strtolower( md5( $user_info['email'] . $date . uniqid( 'edd', true ) ) );
  $cart_details = array();
  $cart_details[$key] = array(
    'id'          => $download,
    'name'        => get_the_title( $download ),
    'item_number' => array(
      'id'      => $download,
      'options' => array(),
    ),
    'price'       => 0,
    'quantity'    => 1,
    'tax'         => 0,
    //'in_bundle'   => 0,
    'parent'    => NULL, /*array(
      'id'      => $cart_item['id'],
      'options' => isset( $cart_item['item_number']['options'] ) ? $cart_item['item_number']['options'] : array()
    ) */
  );
	$payment_data = array( 
		'price' => $payment, 
		'date' => $date,
		'user_email' => $user_info['email'],
		'purchase_key' => $key,
		'currency' => NULL,
		'downloads' => array($download),
		'user_info' => $user_info,
		'cart_details' => $cart_details,
		'status' => 'publish'
	);
#echo'<pre>';var_dump( $payment_data ); die; #!DEBUG remove
	#var_dump( $download, $file_key, $user_info, $ip, $payment );die;#!DEBUG
	#var_dump($payment_data);die;
	$payment = edd_insert_payment( $payment_data );
}

/**
 * The free download process.
 * 
 * Modified from:
 * /includes/process-download.php -> edd_process_download()
 * Modifed parts:
 * Stripping the purchase validation process.
 *
 * @return void
 */
function vp_edd_fd_process_download()
{

	global $edd_options;

	$valid    = true;
	$payment  = -1;
	$download = isset( $_GET['did'] )    ? (int) $_GET['did']                               : '';
	$expire   = isset( $_GET['expire'] ) ? base64_decode( rawurldecode( $_GET['expire'] ) ) : '';
	$file_key = isset( $_GET['file'] )   ? (int) $_GET['file']                              : '';

	// if( $download === '' || $email === '' || $file_key === '' )
	if( $download === '' || $file_key === '' )
		return false;

	// make sure user logged in
	$must_logged_in = isset($edd_options['vp_edd_fd_must_logged_in']) ? $edd_options['vp_edd_fd_must_logged_in'] : false;
	if($must_logged_in)
	{
		if( !is_user_logged_in() ) {
			$valid = false;
		}
	}

	// Make sure the link hasn't expired
	if ( current_time( 'timestamp' ) > $expire )
		wp_die( apply_filters( 'edd_download_link_expired_text', __( 'Sorry but your download link has expired.', 'edd' ) ), __( 'Error', 'edd' ) );

	// Check to see if the file download limit has been reached
	if ( edd_is_file_at_download_limit( $download, -1, $file_key ) )
		wp_die( apply_filters( 'edd_download_limit_reached_text', __( 'Sorry but you have hit your download limit for this file.', 'edd' ) ), __( 'Error', 'edd' ) );

	if( $valid ) {

		// setup the download
		$download_files = edd_get_download_files( $download );
		$requested_file = apply_filters( 'edd_requested_file', $download_files[ $file_key ]['file'], $download_files, $file_key );

		// gather user data
		$user_info = array();

		if($must_logged_in)
		{
			global $user_ID;
			$user_data 			= get_userdata( $user_ID );
			$user_info['email'] = $user_data->user_email;
			$user_info['id'] 	= $user_ID;
			$user_info['name'] 	= $user_data->display_name;
		}
		else
		{
			$user_info['email'] = 'anonymous';
			$user_info['id']    = 'anonymous';
		}

		edd_record_download_in_log( $download, $file_key, $user_info, edd_get_ip(), $payment );
		edd_add_download_as_payment( $download, $file_key, $user_info, edd_get_ip(), $payment );

		$file_extension = edd_get_file_extension( $requested_file );
		$ctype          = edd_get_file_ctype( $file_extension );

		if ( !edd_is_func_disabled( 'set_time_limit' ) && !ini_get('safe_mode') ) {
			set_time_limit(0);
		}
		if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() ) {
			set_magic_quotes_runtime(0);
		}

		@session_write_close();
		if( function_exists( 'apache_setenv' ) ) @apache_setenv('no-gzip', 1);
		@ini_set( 'zlib.output_compression', 'Off' );

		nocache_headers();
		header("Robots: none");
		header("Content-Type: " . $ctype . "");
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=\"" . apply_filters( 'edd_requested_file_name', basename( $requested_file ) ) . "\";");
		header("Content-Transfer-Encoding: binary");

		$file_path = realpath( $requested_file );

		if ( strpos( $requested_file, 'http://' ) === false && strpos( $requested_file, 'https://' ) === false && strpos( $requested_file, 'ftp://' ) === false && file_exists( $file_path ) ) {

			/** This is an absolute path */

			edd_deliver_download( $file_path );

		} else if( strpos( $requested_file, WP_CONTENT_URL ) !== false ) {

			/** This is a local file given by URL */
			$upload_dir = wp_upload_dir();

			$file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $requested_file );
			$file_path = realpath( $file_path );

			if ( file_exists( $file_path ) ) {

				edd_deliver_download( $file_path );

			} else {
				// Absolute path couldn't be discovered so send straight to the file URL
				header( "Location: " . $requested_file );
			}
		} else {
			// This is a remote file
			header( "Location: " . $requested_file );
		}

		exit;

	} else {
		wp_die( apply_filters( 'edd_deny_download_message', __( 'You do not have permission to download this file.', 'vp_edd_fd' ) ), __( 'Error', 'edd' ) );
	}
	exit;
}

/**
 * Get Register Fields
 *
 * @access      private
 * @since       1.0
 * @return      string
 */
function vp_edd_fd_get_register_fields() {
	global $edd_options;
	global $user_ID;

	if ( is_user_logged_in() )
	$user_data = get_userdata( $user_ID );

	ob_start(); ?>
	<fieldset id="edd_register_fields">
		<?php do_action('edd_register_account_fields_before'); ?>
		<p id="edd-user-login-wrap">
			<label for="edd_user_login"><?php _e( 'Username', 'edd' ); ?></label>
			<input name="edd_user_login" id="edd_user_login" class="<?php if(edd_no_guest_checkout()) { echo 'required '; } ?>edd-input" type="text" placeholder="<?php _e( 'Username', 'edd' ); ?>" title="<?php _e( 'Username', 'edd' ); ?>"/>
		</p>
		<p id="edd-user-email-wrap">
			<label for="edd-email"><?php _e( 'Email', 'edd' ); ?></label>
			<input name="edd_email" id="edd-email" class="required edd-input" type="email" placeholder="<?php _e( 'Email', 'edd' ); ?>" title="<?php _e( 'Email', 'edd' ); ?>"/>
		</p>
		<p id="edd-user-pass-wrap">
			<label for="password"><?php _e( 'Password', 'edd' ); ?></label>
			<input name="edd_user_pass" id="edd_user_pass" class="<?php if(edd_no_guest_checkout()) { echo 'required '; } ?>edd-input" placeholder="<?php _e( 'Password', 'edd' ); ?>" type="password"/>
		</p>
		<p id="edd-user-pass-confirm-wrap" class="edd_register_password">
			<label for="password_again"><?php _e( 'Password Again', 'edd' ); ?></label>
			<input name="edd_user_pass_confirm" id="edd_user_pass_confirm" class="<?php if(edd_no_guest_checkout()) { echo 'required '; } ?>edd-input" placeholder="<?php _e( 'Confirm password', 'edd' ); ?>" type="password"/>
		</p>
		<p id="edd-user-first-name-wrap">
			<label class="edd-label" for="edd-first"><?php _e( 'First Name', 'edd' ); ?></label>
			<input class="edd-input required" type="text" name="edd_first" placeholder="<?php _e( 'First Name', 'edd' ); ?>" id="edd-first" value="<?php echo is_user_logged_in() ? $user_data->user_firstname : ''; ?>"/>
		</p>
		<p id="edd-user-last-name-wrap">
			<label class="edd-label" for="edd-last"><?php _e( 'Last Name', 'edd' ); ?></label>
			<input class="edd-input" type="text" name="edd_last" id="edd-last" placeholder="<?php _e( 'Last name', 'edd' ); ?>" value="<?php echo is_user_logged_in() ? $user_data->user_lastname : ''; ?>"/>
		</p>
		<?php do_action( 'edd_register_account_fields_after' ); ?>
		<?php do_action( 'edd_purchase_form_user_info' ); ?>
	</fieldset>
	<?php
	echo ob_get_clean();
}

/**
 * edd_unset_error seems to be not working
 * since it's not unsetting what's really inside $_SESSION['edd-errors']
 * so this is probably the fix
 *
 * Removes a stored error
 *
 * Modified From:
 * EDD 1.4
 * includes/error-tracking.php
 * Modified Part:
 * unset the error in session variable
 * 
 * @param       $error_id string - the ID of the error being set
 * @return      void
*/

function edd_unset_error_fix( $error_id )
{
	// edd_unset_error fix for version less than 1.4
	if(version_compare(EDD_VERSION, '1.5', '<'))
	{
		$errors = edd_get_errors();
		if( $errors ) {
			if( isset( $_SESSION['edd-errors'][$error_id] ) )
			{
				unset( $_SESSION['edd-errors'][$error_id] );
			}
		}
	}
	else
	{
		edd_unset_error($error_id);
	}
}

/**
 * EOF
 */
