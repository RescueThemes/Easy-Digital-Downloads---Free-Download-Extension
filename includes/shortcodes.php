<?php

/**
 * Output free download URL
 */
function vp_edd_fd_download_shortcode( $atts, $content = null ) {
	global $post, $edd_options;

	$button_text  = (isset($edd_options['vp_edd_fd_button_text']) and $edd_options['vp_edd_fd_button_text'] !== '') ? $edd_options['vp_edd_fd_button_text'] : __('Download', 'vp_edd_fd');
	$button_class = (isset($edd_options['vp_edd_fd_button_class']) and $edd_options['vp_edd_fd_button_class'] !== '') ? $edd_options['vp_edd_fd_button_class'] : __('edd-fd-button', 'vp_edd_fd');

	extract( shortcode_atts( array(
			'id' 	=> $post->ID,
			'text'  => $button_text,
			'class' => $button_class,
		),
		$atts )
	);

	$download = edd_get_download( $id );
	$text     = str_replace('%name%', $download->post_title, $text);

	if ( $download )
	{
		return '<a href="'.vp_edd_fd_build_download_list_url($id).'" class="' . $class .'">' . $text . '</a>';
	}
}
add_shortcode( 'download_link', 'vp_edd_fd_download_shortcode' );

/**
 * Output filelist in ul li list format
 */
function vp_edd_fd_filelist($atts, $content = null)
{
	extract(shortcode_atts( array(), $atts ));

	$did   = isset($_GET['did']) ? $_GET['did'] : '';
	$files = array();
	if($did !== '')
	{
		$files = edd_get_download_files($did);
	}

	// begin output
	?>
	<ul>
		<?php foreach ($files as $file_key => $file): ?>
		<li><a href="<?php echo vp_edd_fd_build_download_gateway_url($did, $file_key); ?>"><?php echo $file['name']; ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php
	// end of output
}
add_shortcode( 'vp_edd_filelist', 'vp_edd_fd_filelist' );

/**
 * Output direct EDD download file url
 */
function vp_edd_fd_download_url($atts, $content = null)
{
	extract(shortcode_atts( array('label' => 'link'), $atts ));

	$did  = isset($_GET['did'])  ? $_GET['did'] : '';
	$file = isset($_GET['file']) ? $_GET['file'] : '';
	$url  = '';
	if($did !== '' and $file !== '')
	{
		$url = vp_edd_fd_build_download_url($did, $file);
	}

	return '<a href="'.$url.'">' . $atts['label'] . '</a>';

}
add_shortcode( 'vp_edd_download_url', 'vp_edd_fd_download_url' );

/**
 * Output registration form
 */
function vp_edd_membership_form($atts, $content = null)
{
	extract(shortcode_atts( array(), $atts ));

	// get the previous url from GET parameters
	$redirect = '';
	if(isset($_GET['redirect']))
	{
		$redirect = urldecode($_GET['redirect']);
	}

	// begin output
	?>
		<?php do_action('vp_edd_fd_before_member'); ?>

		<!-- login form -->
		<?php echo edd_login_form($redirect); ?>
		<!-- /login form -->

		<!-- registration form -->
		<?php if(!is_user_logged_in()): ?>
			<form method="POST" action="">
				<?php vp_edd_fd_get_register_fields(); ?>
				<input type="submit" value="<?php _e('Register', 'vp_edd_fd'); ?>" />
			</form>
		<?php endif; ?>
		<!-- /registration form -->

		<?php do_action('vp_edd_fd_after_member'); ?>
	<?php
	// end of output
}
add_shortcode( 'vp_edd_membership_form', 'vp_edd_membership_form' );

/**
 * Creepy function to strip 'Login URL in registration field, gonna need this.'
 * @param  String $form Registration form
 * @return String       Strippted registration form
 */
function vp_edd_fd_strip_register($form)
{
	$form = preg_replace('%<p[^>]*>(.*?)</p>%si', '', $form, 1);
	return $form;
}