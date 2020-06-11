<?php
/*
 * q5_toc_registration
 * ===================
 * Defines the plugin Activation / Deactivation and Uninstall functions
 * The functions are declared 'STATIC' because it is a requirement that
 * uninstall class functions are always static. For consistency all three
 * functions have been defined as static.
 *
 * Version: 1.0.0
 *
 * Since:   5.2
 */
 if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}
 
 class q5_toc_registration {
	
	
	public static $depth_field_id = 'q5_toc_depth';
	
	public static $toc_elements_field_id = 'q5_toc_elements_field';
	
	public static $title_field_id = 'q5_toc_title';
	
	public static $child_title_field_id = 'q5_toc_child-title_field';
	
	public static $parent_title_field_id = 'q5_toc_parent_title_field';
	
	public static $peer_blog_title_field_id = 'q5_toc_peer_blog-title_field';
	
	// Plugin Acivation / Deactivation functions.
	public static function q5_toc_activation ()
	{
		$q5_toc_definition = q5_toc_definition::get_instance();
		add_option(q5_toc_registration::$depth_field_id, $q5_toc_definition->get_depth());
		add_option(q5_toc_registration::$toc_elements_field_id, $q5_toc_definition->get_headers());
		add_option(q5_toc_registration::$title_field_id, $q5_toc_definition->get_title());
		add_option(q5_toc_registration::$child_title_field_id, $q5_toc_definition->get_child_title());
		add_option(q5_toc_registration::$parent_title_field_id, $q5_toc_definition->get_parent_title());
		add_option(q5_toc_registration::$peer_blog_title_field_id, $q5_toc_definition->get_peer_blog_title());
	}

	public static function q5_toc_deactivation ()
	{
		remove_filter('the_content', 'q5_toc_add_anchor_points_content_filter' );
		remove_action('widgets_init', 'q5_toc_registration::q5_toc_widget_register');
		q5_toc_registration::q5_toc_widget_unregister();
	}

	public static function q5_toc_uninstall ()
	{
		delete_option(q5_toc_registration::$depth_field_id);
		delete_option(q5_toc_registration::$toc_elements_field_id);	
		delete_option(q5_toc_registration::$title_field_id);
		delete_option(q5_toc_registration::$child_title_field_id);
		delete_option(q5_toc_registration::$parent_title_field_id);
		delete_option(q5_toc_registration::$peer_blog_title_field_id);

	}
	
	public static function register_q5_toc_scripts()
	{
		 wp_enqueue_style( 'q5_toc_style', plugins_url( 'css/style.css' , __FILE__ ) );
	}
	// 
	public static function q5_toc_widget_register()
	{
		register_widget('q5_toc_widget');
	}
	
	public static function q5_toc_widget_unregister()
	{
		unregister_widget('q5_toc_widget');
	}
}
?>