<?php

/**
 * Push value to array after specific key position
 * @return array
 * @param  array $src
 * @param  array $in
 * @param  int|string $pos
*/
function array_push_after($src,$in,$pos){
	if(is_int($pos)) $R=array_merge(array_slice($src,0,$pos+1), $in, array_slice($src,$pos+1));
	else{
		foreach($src as $k=>$v){
			$R[$k]=$v;
			if($k==$pos)$R=array_merge($R,$in);
		}
	}return $R;
}

function vp_get_plugin_basename_from_slug( $slug )
{
	if(!function_exists('get_plugins'))
	{
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$keys = array_keys( get_plugins() );
	foreach ( $keys as $key ) {
		if ( preg_match( '|^' . $slug .'|', $key ) )
			return $key;
	}
	return $slug;
}

function vp_is_plugin_active_from_slug($slug)
{
	if(!function_exists('is_plugin_active'))
	{
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	$path = vp_get_plugin_basename_from_slug($slug);

	if($slug === $path or !is_plugin_active($path))
	{
		return false;
	}
	return true;
}

/**
 * EOF
 */