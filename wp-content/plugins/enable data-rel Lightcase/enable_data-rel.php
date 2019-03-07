<?php
/*
Plugin Name: enable Data-Rel for Gutenburg
Plugin URI: http://spirographics.com/playground
Description: Updated rel= to data-rel=
Author: Jess Ericskon
Version: 1.0.0
Author URI: http://spirographics.com
License: GPL2
*/


add_filter('wp_get_attachment_link', 'add_data_rel');
function add_data_rel($link) {
	global $post;
	return str_replace(' rel="lightcase"', ' data-rel="lightcase"', $link);
}
