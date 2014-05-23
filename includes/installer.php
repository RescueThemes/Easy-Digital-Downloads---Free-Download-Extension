<?php

function vp_edd_fd_install()
{
	global $wpdb, $edd_options;

	if(is_null($edd_options))
	{
		$key         = 'edd_settings';
		$edd_options = get_option($key);
	}

	$download       = isset( $edd_options['vp_edd_fd_download_page'] ) ? $edd_options['vp_edd_fd_download_page']  : '';
	$filelist       = isset( $edd_options['vp_edd_fd_filelist_page'] ) ? $edd_options['vp_edd_fd_filelist_page']  : '';
	$member         = isset( $edd_options['vp_edd_fd_member_page'] )   ? $edd_options['vp_edd_fd_member_page']    : '';
	$must_login     = isset( $edd_options['vp_edd_fd_must_logged_in'] )? $edd_options['vp_edd_fd_must_logged_in'] : true;
	$button_class   = isset( $edd_options['vp_edd_fd_button_class'] )  ? $edd_options['vp_edd_fd_button_class']   : '';
	$button_text    = isset( $edd_options['vp_edd_fd_button_text'] )   ? $edd_options['vp_edd_fd_button_text']    : '';
	$countdown      = isset( $edd_options['vp_edd_fd_countdown'] )     ? $edd_options['vp_edd_fd_countdown']      : 2000;

	if( empty($download) )
	{
		// download gateway page
		$download = wp_insert_post(
			array(
				'post_title'     => __('Download', 'vp_edd_fd'),
				'post_content'   => 'Your download will start shortly, if it\'s not, then you can try clicking on this [vp_edd_download_url label="direct link"].',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
	}

	if( empty($filelist) )
	{
		// filelist page
		$filelist = wp_insert_post(
			array(
				'post_title'     => __('Files', 'vp_edd_fd'),
				'post_content'   => '[vp_edd_filelist]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
	}

	if( empty($member) )
	{
		// user login and registration page
		$member = wp_insert_post(
			array(
				'post_title'     => __( 'Member', 'vp_edd_fd' ),
				'post_content'   => '[vp_edd_membership_form]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
	}

	// Store our page IDs
	$options = array(
		'vp_edd_fd_download_page'  => $download,
		'vp_edd_fd_filelist_page'  => $filelist,
		'vp_edd_fd_member_page'    => $member,
		'vp_edd_fd_must_logged_in' => $must_login,
		'vp_edd_fd_button_class'   => $button_class,
		'vp_edd_fd_button_text'    => $button_text,
		'vp_edd_fd_countdown'      => $countdown,
	);

	// merge and saving settings
	$misc_settings = get_option('edd_settings');

	if(!is_array($misc_settings))
	{
		$misc_settings = array();
	}

	$misc_settings = array_merge($misc_settings, $options);
	update_option( 'edd_settings', $misc_settings );
}

register_activation_hook(VP_EDD_FD_PLUGIN_FILE, 'vp_edd_fd_install');

/**
 * EOF
 */
