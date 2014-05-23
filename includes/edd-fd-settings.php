<?php

// adds the settings to the Misc section
function vp_edd_fd_add_settings($settings) {

	// Setup some default option sets
	$pages = get_pages();
	$pages_options = array( '' => '' ); // Blank option
	if( $pages ) {
		foreach( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}
	$eddmc_settings = array(
		array(
			'id' => 'vp_edd_fd_settings',
			'name' => '<strong>' . __('Free Download Settings', 'vp_edd_fd') . '</strong>',
			'desc' => __('Configure Free Download Settings', 'vp_edd_fd'),
			'type' => 'header'
		),
		array(
			'id'   => 'vp_edd_fd_must_logged_in',
			'name' => __('Login to Download', 'vp_edd_fd'),
			'desc' => __('When checked, users need to login before download, if they don\'t, will be redirected to member page.', 'vp_edd_fd'),
			'type' => 'checkbox',
		),
		array(
			'id'   => 'vp_edd_fd_button_class',
			'name' => __('Download button CSS class', 'vp_edd_fd'),
			'desc' => __('CSS class of the download button for your custom styling, default to "edd-fd-button".', 'vp_edd_fd'),
			'type' => 'text',
		),
		array(
			'id'   => 'vp_edd_fd_button_text',
			'name' => __('Download button text', 'vp_edd_fd'),
			'desc' => __('Text label for your download button, default to "download".', 'vp_edd_fd'),
			'type' => 'text',
		),
		array(
			'id'   => 'vp_edd_fd_countdown',
			'name' => __('Download gateway waiting time', 'vp_edd_fd'),
			'desc' => __('(miliseconds) The waiting time before download started, default to 2000 miliseconds.', 'vp_edd_fd'),
			'type' => 'text',
		),
		array(
			'id' => 'vp_edd_fd_download_page',
			'name' => __('Download Page', 'vp_edd_fd'),
			'desc' => __('Select the page that will show anything before user download begin.', 'vp_edd_fd'),
			'type' => 'select',
			'size' => 'regular',
			'options' => $pages_options
		),
		array(
			'id'   => 'vp_edd_fd_member_page',
			'name' => __('Member Page', 'vp_edd_fd'),
			'desc' => __('Select the page that will show login and registration form.', 'vp_edd_fd'),
			'type' => 'select',
			'size' => 'regular',
			'options' => $pages_options
		),
		array(
			'id' => 'vp_edd_fd_filelist_page',
			'name' => __('Filelist Page', 'edda'),
			'desc' => __('Select the page that will show file lists if there are more than one files.', 'vp_edd_fd'),
			'type' => 'select',
			'options' => $pages_options
		),
	);
	
	return array_merge($settings, $eddmc_settings);
}

add_filter('edd_settings_misc', 'vp_edd_fd_add_settings');