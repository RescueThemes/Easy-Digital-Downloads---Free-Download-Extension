<?php
/*
Plugin Name: Easy Digital Downloads - Free Download
Plugin URI: http://vafpress.com/
Description: Bypass Checkout Mechanism For Free Download
Version: 0.2.1
Author: Vafpress
Author URI: http://vafpress.com/
License: GPLv3
*/

// setup contants
if( !defined( 'VP_EDD_FD_PLUGIN_FILE' ) )
	define( 'VP_EDD_FD_PLUGIN_FILE', __FILE__ );

require_once 'includes/notices.php';
require_once 'includes/helper.php';

/**
 * We need the functions to check for EDD existance, so if it's not loaded, load em.
 */
$edd_slug = 'easy-digital-downloads';

if(!vp_is_plugin_active_from_slug($edd_slug))
{
	if(is_admin())
		// give admin notice
		add_action('admin_notices', 'vp_edd_fd_no_edd_notice');
	else
		// halt plugin in front end
		return;	
}
else
{
	add_filter('query_vars'                , 'vp_edd_fd_query_vars');
	add_action('init'                      , 'vp_edd_add_column');
	add_action('wp'                        , 'vp_edd_fd_download_requested', 10);
	add_action('wp'                        , 'vp_edd_fd_check_registration');
	add_action('wp_head'                   , 'vp_edd_download_gateway_script');
	add_filter('edd_purchase_download_form', 'vp_edd_fd_download_button', 10, 2);
	add_action('get_pages'                 , 'vp_edd_fd_hide_pages');
}

require_once 'includes/installer.php';
// using settings.php as a filename caused an error
require_once 'includes/edd-fd-settings.php';
require_once 'includes/edd-functions.php';
require_once 'includes/shortcodes.php';

/**
 * Add custom queries
 */
function vp_edd_fd_query_vars($qvars) {
	$custom_queries = array('did', 'vp_edd_act', 'file', 'expire', 'redirect');
	$qvars          = array_merge($qvars, $custom_queries);
	return $qvars;
}

/**
 * Overrides purchase button with free download button if the download price is ZERO.
 * 
 * @param  String $purchase_form Purchase from string
 * @param  Array  $args          Download data
 * @return String                Purchase or download button
 */
function vp_edd_fd_download_button($purchase_form, $args)
{
	$did   = $args['download_id'];
	$price = vp_edd_fd_get_calculated_price($did);
	if($price <= 0)
	{
		return do_shortcode( "[download_link id=$did]" );
	}
	return $purchase_form;
}

function vp_edd_fd_download_requested()
{
	global $edd_options;

	$download_page = isset($edd_options['vp_edd_fd_download_page']) ? $edd_options['vp_edd_fd_download_page']: 0;
	$filelist_page = isset($edd_options['vp_edd_fd_filelist_page']) ? $edd_options['vp_edd_fd_filelist_page'] : 0;

	// check if we are executing free download url
	$download_id = isset($_GET['did']) ? $_GET['did'] : '';
	$action      = isset($_GET['vp_edd_act']) ? $_GET['vp_edd_act'] : '';
	$file        = isset($_GET['file']) ? $_GET['file'] : '';
	if( $download_id !== '' and $action !== '' )
	{

		// check if it's a premium then die.
		$price = vp_edd_fd_get_calculated_price($download_id);
		if($price > 0)
		{
			$error_message = __('You do not have permission to download this file', 'vp_edd_fd');
			wp_die( apply_filters( 'edd_deny_download_message', $error_message, __('Purchase Verification Failed', 'vp_edd_fd') ) );
		}

		switch ($action) {
			case 'show_download':

				$files = edd_get_download_files($download_id);
				if(count($files) > 1)
				{
					$lp_url    = get_permalink( $filelist_page );
					$list      = add_query_arg( array('did' => $download_id), $lp_url );
					$direct_to = $list;
				}
				else
				{
					reset($files);
					$file      = key($files);
					$gp_url    = get_permalink( $download_page );
					$gateway   = add_query_arg(array('did' => $download_id, 'file' => $file), $gp_url);
					$direct_to = $gateway;
				}
				
				// check auth
				vp_edd_fd_check_auth($direct_to);

				wp_redirect($direct_to);
				die();

				break;
			case 'download_gateway':
				$gp_url  = get_permalink( $download_page );
				$gateway = add_query_arg(array('did' => $download_id, 'file' => $file), $gp_url);

				// check auth
				vp_edd_fd_check_auth($gateway);
				
				wp_redirect( $gateway );
				die();

				break;
			case 'download':
				global $wp, $wp_query;
				$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

				// check auth
				vp_edd_fd_check_auth($current_url);

				vp_edd_fd_process_download();

				die();
				break;
			default:
				break;
		}
	}
}

function vp_edd_fd_build_download_list_url($did)
{
	$home_url = get_home_url();
	$list_url = add_query_arg(array('did' => $did, 'vp_edd_act' => 'show_download'), $home_url);
	return $list_url;
}

function vp_edd_fd_build_download_gateway_url($did, $file)
{
	$home_url = get_home_url();
	$dg_url   = add_query_arg(array('did' => $did, 'file' => $file, 'vp_edd_act' => 'download_gateway'), $home_url);
	return $dg_url;
}

function vp_edd_fd_build_download_url($did, $file)
{
	global $edd_options;

	$hours = isset( $edd_options['download_link_expiration'] )
			&& is_numeric( $edd_options['download_link_expiration'] )
			? absint($edd_options['download_link_expiration']) : 24;

	if( ! ( $date = strtotime( '+' . $hours . 'hours' ) ) )
		$date = 2147472000; // Highest possible date, January 19, 2038

	$params = array(
		'file' 			=> $file,
		'did'    		=> $did,
		'vp_edd_act'	=> 'download',
		'expire' 		=> rawurlencode( base64_encode( $date ) )
	);

	$params = apply_filters( 'edd_download_file_url_args', $params );

	$download_url = add_query_arg( $params, home_url() );

	return $download_url;
}

/**
 * Output download gateway script
 */
function vp_edd_download_gateway_script()
{
	global $edd_options;
	$download_page = isset($edd_options['vp_edd_fd_download_page']) ? $edd_options['vp_edd_fd_download_page'] : '';
	$member_page   = isset($edd_options['vp_edd_fd_member_page'])   ? $edd_options['vp_edd_fd_member_page']   : '';
	$countdown     = (isset($edd_options['vp_edd_fd_countdown']) and is_numeric($edd_options['vp_edd_fd_countdown'])) ? $edd_options['vp_edd_fd_countdown'] : 2000;
	if(is_page( $download_page ))
	{
		$did  = isset($_GET['did'])  ? $_GET['did'] : '';
		$file = isset($_GET['file']) ? $_GET['file'] : '';
		if($did !== '' and $file !== '')
		{
			$url = vp_edd_fd_build_download_url($did, $file);
			?>
			<script>
				jQuery(document).ready(function(){
					setTimeout(function(){
						var download_url = "<?php echo $url; ?>";
						window.location.assign(download_url);
					}, <?php echo $countdown; ?>);
				});
			</script>
			<?php
		}
	}
}

/**
 * Check user authentication, redirect to membership page if not authed
 * @param  String $redirect URL of redirection string
 */
function vp_edd_fd_check_auth($redirect)
{
	global $edd_options;

	$must_logged_in = isset($edd_options['vp_edd_fd_must_logged_in']) ? $edd_options['vp_edd_fd_must_logged_in'] : false;

	if($must_logged_in)
	{
		$member_page   = $edd_options['vp_edd_fd_member_page'];
		$member_url    = add_query_arg( array('redirect' => urlencode($redirect)), get_permalink( $member_page ) );
		if(!is_user_logged_in())
		{
			wp_redirect( $member_url );
			die();
		}
	}
}


/**
 * Check for user registration since EDD regisration seems to be tied with the purchase
 * so need to build function to only registering.
 * @return [type] [description]
 */
function vp_edd_fd_check_registration()
{
	global $wp;
	global $edd_options;

	$member_page   = isset($edd_options['vp_edd_fd_member_page']) ? $edd_options['vp_edd_fd_member_page'] : 0;

	// get redirect url
	$redirect = '';
	if(isset($_GET['redirect']))
	{
		$redirect = $_GET['redirect'];
	}

	// get needed variables
	$page_id  = get_the_ID();

	// if in registration page
	if ($page_id == $member_page)
	{
		edd_unset_error_fix( 'no_gateways' );
		$errors = edd_get_errors();

		// add mailchimp checkbox
		if(function_exists('eddmc_mailchimp_fields'))
		{
			add_action('edd_purchase_form_user_info', 'eddmc_mailchimp_fields');
		}

		// add hidden field to indicate registration submission
		add_action( 'edd_register_account_fields_after', 'add_register_action' );
		function add_register_action()
		{
			echo '<input type="hidden" name="edd_action" value="user_register"/>';
		}

		// if logged in, just redirect to download file
		if(is_user_logged_in())
		{
			wp_redirect( $redirect );
		}
	}

	// if in member page and registration POST data sent
	if(isset($_POST['edd_action']))
	{
		$action = $_POST['edd_action'];
		if($action === 'user_register')
		{
			$user = vp_edd_fd_register_user();
			if($user)
			{
				wp_redirect( $redirect );
			}
		}
	}
}


/**
 * Register new user
 * @return Array User array
 */
function vp_edd_fd_register_user()
{
	// Validate the form $_POST data
	$valid_data['need_new_user'] = true;
	$valid_data['new_user_data'] = edd_purchase_form_validate_new_user();

	// Allow themes and plugins to hook to errors
	do_action( 'edd_checkout_error_checks', $valid_data, $_POST );

	if ( edd_get_errors() )
	{
		// print error before member registration content
		add_action('vp_edd_before_member', 'edd_print_errors');
		$user = false;
	}
	else
	{
		$user = edd_get_purchase_form_user( $valid_data );
		// Setup user information
		$user_info = array(
			'id'         => $user['user_id'],
			'email'      => $user['user_email'],
			'first_name' => $user['user_first'],
			'last_name'  => $user['user_last'],
		);
		if(function_exists('eddmc_check_for_email_signup'))
		{
			eddmc_check_for_email_signup($_POST, $user_info);
		}
	}

	return $user;
}


/**
 * This block of 3 functions add download counts column in downloads table so that we can track the statistic easier
 * since our free download won't have sales counted.
 */
function vp_edd_add_column()
{
	add_filter( 'manage_edit-download_columns', 'vp_edd_download_columns' );
	add_action( 'manage_download_posts_custom_column'  , 'vp_edd_render_download_columns', 10, 2 );
}

function vp_edd_download_columns( $download_columns ) {
	$downloads        = array('downloads' => __( 'Downloads', 'vp_edd_fd' ));
	$download_columns = array_push_after($download_columns, $downloads, 'sales');

	return $download_columns;
}

function vp_edd_render_download_columns( $column_name, $post_id )
{
	// if ( get_post_type( $post_id ) == 'download' )
	// {
		global $edd_options;
		global $edd_logs;
		$count = $edd_logs->get_log_count($post_id, 'file_download');
		switch ( $column_name) {
			case 'downloads':
				echo $count;
				break;
		}
	// }
}

/**
 * Hides our secret pages (download gateway, member, filelist)
 * function taken from Exclude Pages plugin by Simon Wheatley
 * @param  Array $pages Array of page ids to be hidden
 */
function vp_edd_fd_hide_pages($pages)
{
	global $edd_options;
	$download   = isset( $edd_options['vp_edd_fd_download_page'] ) ? $edd_options['vp_edd_fd_download_page']  : '';
	$filelist   = isset( $edd_options['vp_edd_fd_filelist_page'] ) ? $edd_options['vp_edd_fd_filelist_page']  : '';
	$member     = isset( $edd_options['vp_edd_fd_member_page'] )   ? $edd_options['vp_edd_fd_member_page']    : '';

	$bail_out = ( ( defined( 'WP_ADMIN' ) && WP_ADMIN == true ) || ( strpos( $_SERVER[ 'PHP_SELF' ], 'wp-admin' ) !== false ) );
	if ( $bail_out ) return $pages;

	$excluded_ids = array($download, $filelist, $member);
	$length       = count($pages);

	// Ensure the array only has unique values
	$delete_ids = array_unique( $excluded_ids );
	
	// Loop though the $pages array and actually unset/delete stuff
	for ( $i=0; $i<$length; $i++ ) {
		$page = & $pages[$i];
		// If one of the ancestor pages is excluded, add it to our exclude array
		if ( in_array( $page->ID, $delete_ids ) ) {
			// Finally, delete something(s)
			unset( $pages[$i] );
		}
	}

	// Reindex the array, for neatness
	// SWFIXME: Is reindexing the array going to create a memory optimisation problem for large arrays of WP post/page objects?
	if ( ! is_array( $pages ) ) $pages = (array) $pages;
	$pages = array_values( $pages );

	return $pages;
}

/**
 * EOF
 */
