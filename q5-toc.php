<?php
/**
 * Table of Contents Plugin
 * ========================
 *
 * Plugin Name:		Q5 TOC
 * Plugin URI:  	https://quintic.co.uk/wordpress/plugins/q5-toc/
 * Description: 	Inserts a Table of Contents (normally into a side bar). Additionally, links to peer pages and associated topics can be included after the TOC. Both TOC and Topic links remain fixed when page scrolls.
 * Version:     	1.1.0
 * Author:      	Quintic
 * Author URI:  	https://www.quintic.co.uk/
 * Requires at least:5.2
 * Requires PHP: 	7.2
 * License:     	GPLv2 or later
 * License URI:	 	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: 	page-content
 * Domain Path: 	./languages
 *
 * Q5 TOC is free software: you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation, either version 2 of the 
 * License, or any later version.
 *
 * Q5 TOC is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if (! class_exists('q5_toc_entry')){
class q5_toc_entry
{
	private $heading;
	private $anchor;
	private $level;

	public function __construct ($heading, $anchor, $level)
	{
		$this->heading = $heading;
		$this->anchor = $anchor;
		$this->level  = $level;
	}

	public function get_heading()
	{
		return $this->heading;
	}

	public function get_anchor()
	{
		return $this->anchor;
	}

	public function get_level()
	{
		return $this->level;
	}
}
}

if (! class_exists('q5_toc')){
class q5_toc
{
	private $anchor_id;
	private $toc_entries;
	
	public function __construct ()
	{
		$this->anchor_id = 0;
		$this->toc_entries = array();
	}

	public function get_toc_entry()
	{
		foreach($this->toc_entries as $toc_entry)
		{
			yield $toc_entry;
		}
	}
	
	public function get_toc_entry_count()
	{
		return count($this->toc_entries);
	}
	
	public function build_toc ( $content )
	{	
		set_error_handler('q5_toc_dom_error_handler');
		$dom = new DOMDocument();
		if ($dom->loadHTML( $content ) === false)
					{
			echo $content;
			echo ('<p>');
			_e('Q5_TOC: Error attempting to load document');
			echo ('</p>');
		}
		else
		{
			foreach ($dom->childNodes as $child)
			{
				$this->find_toc_elements ($this, $dom, $child);
			}
		}
		restore_error_handler();
	}
	
	public function render_toc ( $args = '' )
	{
		$defaults = array(
			'toc_class'             => 'q5_toc',
			'toc_title_class'       => 'q5_toc_title',
			'toc_title'             => 'Table Of Contents',
			'toc_entry_class'       => '',
			'toc_heirarchy_class'   => '',
			'toc_hidden'			=> false,
		);

		$r = wp_parse_args( $args, $defaults );
		
		$current_level = -1;
		$toc_entry_element = $r['toc_entry_class'] == '' ? '<li>' : '<li class="' . $r['toc_entry_class'] . '">';
		$toc_heirarchy_element = $r['toc_heirarchy_class'] == '' ? '<ul>' : '<ul class="' . $r['toc_heirarchy_class'] . '">';

		echo '<div class="' . $r['toc_class'] . '">';
		echo '<p class="' . $r['toc_title_class'] .'">';
		_e($r['toc_title']);
		echo '</p>';
		
		foreach ($this->get_toc_entry() as $toc_entry)
		{ 
		
			$indent = $toc_entry->get_level() - $current_level;
			
			if ($indent > 0)
			{
				$this->step_in($toc_heirarchy_element, $indent);
			}
			
			else if ($indent < 0)
			{
				$this->step_out(-$indent);
			}
			
			$current_level = $toc_entry->get_level();

			if ($r['toc_hidden'])
			{
				echo $toc_entry_element . '<a>' . $toc_entry->get_anchor() . '</a></li>';
			}
			else
			{
				echo $toc_entry_element . '<a href="#' . $toc_entry->get_anchor() . '">' . $toc_entry->get_heading() . '</a></li>';
			}
			
		} 
		$this->step_out($current_level+1);
		echo "</div>";
	}
	
	private function step_in ($ulelement, $indent)
	{
		q5_toc_output_multiple ($ulelement, $indent);
	}
	
	private function step_out ($indent)
	{
		q5_toc_output_multiple ('</ul>', $indent);
	}
	
	private function find_toc_elements ($toc, $dom, $node)
	{
		$q5_toc_definition = q5_toc_definition::get_instance();
		if ($q5_toc_definition->is_toc_element($node))
		{
			$level = $q5_toc_definition->toc_element_level ($node);
			if ($level <= $q5_toc_definition->get_depth())
			{		
				if ($node != null && $node->firstChild != null)
				{
					$heading = $node->firstChild->nodeValue;
					$anchor = $node->getAttribute('id');

					if ($anchor == null)
					{
						$anchor = q5_toc_definition::construct_anchor ($this->anchor_id++);
					}
					$this->add_toc_entry(new q5_toc_entry($heading, $anchor, $level));
				}
			}
		}

		if ($node->childNodes != null)
		{
			foreach($node->childNodes as $childNode)
			{
				$this->find_toc_elements ($toc, $dom, $childNode);
			}
		}
	}

	private function add_toc_entry(q5_toc_entry $toc_entry)
	{
		$this->toc_entries[] = $toc_entry;
	}
}
}

if (!function_exists('q5_toc_list_child_pages')){
/**
 * function q5_toc_list_child_pages
 * ================================
 * Return a list of child pages as HTML.
 *
 * @since 1.0.0
 *
 * @see get_pages()
 *
 * @global WP_Query $wp_query
 *
 * @param post $post 	Current Page.
 * @param array|string $args {
 *     Optional. Array or string of arguments to generate a list of pages. See `get_pages()` for additional arguments.
 *
 *     @type string       $date_format  PHP date format to use for the listed pages. Relies on the 'show_date' parameter.
 *                                      Default is the value of 'date_format' option.
 *     @type int          $depth        Number of levels in the hierarchy of pages to include in the generated list. Accepts:
 *                                      0 (Any depth upto 6 ), [Default]
 *                                      1 (top-level pages only), 
 *										n (pages to the given depth n. Max depth is 6).
 *     @type string		  $title_class	CSS Class of the title_class
 *	   @type string		  $entry_class  CSS Class of each entry
 *	   @type string		  $date_class   CSS class of Date field, if requested.
 *     @type string       $exclude      Comma-separated list of page IDs to exclude. Default empty.
 *     @type array        $include      Comma-separated list of page IDs to include. Default empty.
 *     @type string       $link_after   Text or HTML to follow the page link label. Default null.
 *     @type string       $link_before  Text or HTML to precede the page link label. Default null.
 *     @type string       $post_type    Post type to query for. Default 'page'.
 *     @type string|array $post_status  Comma-separated list or array of post statuses to include. Default 'publish'.
 *     @type string       $show_date    Whether to display the page publish or modified date for each page. Accepts
 *                                      'modified' or any other value. An empty value hides the date. Default empty.
 *     @type string       $sort_column  Comma-separated list of column names to sort the pages by. Accepts 'post_author',
 *                                      'post_date', 'post_title', 'post_name', 'post_modified', 'post_modified_gmt',
 *                                      'menu_order', 'post_parent', 'ID', 'rand', or 'comment_count'. Default 'post_title'.
 *     @type string       $title        List heading. Default 'Pages'.
 *     @type string       $item_spacing Whether to preserve whitespace within the menu's HTML. Accepts 'preserve' or 'discard'.
 *                                      Default 'preserve'.
 *     @type Walker       $walker       Walker instance to use for listing pages. Default empty (Walker_Page).
 *
 *	   @return string - HTML to insert into widget/page.
 * }
 * 
 */
function q5_toc_list_child_pages( $post, $args = '' ) 
{
	$defaults = array(
		'depth'        => 0,
		'title'        => __( 'Pages' ),
		'sort_column'  => 'menu_order, post_title',
		'item_spacing' => 'preserve',
		'post_status'  => 'publish',
		'exclude'      => '',
		'heirarchical' => ''
	);
	$r = wp_parse_args( $args, $defaults );

	if ( ! in_array( $r['item_spacing'], array( 'preserve', 'discard' ), true ) ) 
	{
		// invalid value, fall back to default.
		$r['item_spacing'] = $defaults['item_spacing'];
	}

	// sanitize, mostly to keep spaces out
	$r['exclude'] = preg_replace( '/[^0-9,]/', '', $r['exclude'] );

	// Allow plugins to filter an array of excluded pages (but don't put a nullstring into the array)
	$exclude_array = ( $r['exclude'] ) ? explode( ',', $r['exclude'] ) : array();

	/**
	 * Filters the array of pages to exclude from the pages list.
	 * @param array $exclude_array An array of page IDs to exclude.
	 */
	$r['exclude'] = implode( ',', apply_filters( 'wp_list_pages_excludes', $exclude_array ) );

	// Query pages.
	$output = '';
	$r['hierarchical'] = 0;
	$pages             = get_pages( $r );
	$section_start_function = 'q5_toc_add_section_start';
	$section_end_function = 'q5_toc_null_function';

	if ( ! empty( $pages ) ) 
	{
		foreach ( (array) $pages as $child_page ) 
		{

			if ($child_page->post_parent !=  0 && $child_page->post_parent == $post->ID)
			{
				$output .= $section_start_function($r);
				$section_start_function = 'q5_toc_null_function';
				$section_end_function = 'q5_toc_add_section_end';
				$link = get_permalink($child_page->ID);
				if ($link == null)
				{
					$link = get_page_link($child_page->ID);
				}
				$output .= '<li><a  class="'. $r['entry_class'] . '" href="' . $link .'">' . $child_page->post_title . '</a></li>';
			}
		}
		$output .= $section_end_function();
		return $output;
	}
}
}

if( ! function_exists( 'q5_toc_add_section_start' ) ) {
	function q5_toc_add_section_start($args)
	{
		$output = '<div class="' . $args['section_class'] . '">';
		if ( $args['title'] ) 
		{
			$output .= '<p class="' . $args['title_class'] . '">' . $args['title'] . '</p>';
		}
		$output .= '<ul>';
		return $output;
	}
}

if( ! function_exists( 'q5_toc_add_section_end' ) ) {
	function q5_toc_add_section_end()
	{
		return '</ul></div>';
	}
}


if( ! function_exists( 'q5_toc_null_function' ) ) {
	function q5_toc_null_function ($args = '')
	{
	}
}

if( ! function_exists( 'q5_toc_output_multiple' ) ) {	
	function q5_toc_output_multiple ($text, $occurances)
	{
		for ($i = 0; $i < $occurances; $i++)
		{
			print ($text);
		}		
	}
}

if (! function_exists('q5_toc_list_parent')){
/**
 * function q5_toc_list_parent
 * ===========================
 * Return a link to parent page as HTML.
 *
 * @since 1.0.0
 *
 * @param post $post 	Current Page.
 * @param array|string $args {
 *     Optional. Array or string of arguments to generate a list of pages. See `get_pages()` for additional arguments.
 *
 *     @type string		  $section_class CSS Class of the section (<div>
 *     @type string		  $title_class	 CSS Class of the title 
 *	   @type string		  $entry_class   CSS Class of each entry
 
 *     @type string       $title         List heading. Default 'Parent'.
 *     @type string       $item_spacing Whether to preserve whitespace within the menu's HTML. Accepts 'preserve' or 'discard'.
 *                                      Default 'preserve'.
 *
 *	   @return string - HTML to insert into widget/page.
 * 
 */
function q5_toc_list_parent( $post, $args = '' ) 
{
	$defaults = array(
		'title'        => __( 'Parent' ),
		'section_class'=> '',
		'title_class'  => '',
		'entry_class'  => '',
		'item_spacing' => 'preserve',
	);
	$output = '';
	
	$r = wp_parse_args( $args, $defaults );

	if ( ! in_array( $r['item_spacing'], array( 'preserve', 'discard' ), true ) ) 
	{
		// invalid value, fall back to default.
		$r['item_spacing'] = $defaults['item_spacing'];
	}

	if ( $post->post_parent != 0)
	{

		$parent_page = get_post($post->post_parent);
		$link = get_permalink($post->post_parent);
		if ($link == null)
		{
			$link = get_page_link($post->post_parent);
		}
		$output = '<div class="' . $r['section_class'] . '">';
		$output .= '<p class="' . $r['title_class'] . '">' . $r['title'] . '</p>';
		$output .= '<a class="'. $r['entry_class'] . '" href="' . $link .'">' . $parent_page->post_title . '</a>';
		$output .= '</div>';
	}
	
	return $output;
}
}
if (! function_exists('q5_toc_list_peer')){
	/**
	* function q5_toc_list_peer
	* ===========================
	* Return a link to blog peer pages as HTML.
	*
	* @since 1.1.0
	*
	* @param post $post 	Current Post.
	* @param array|string $args {
	*     Optional. Array or string of arguments to generate a list of pages. See 		`get_pages()` for additional arguments.
	*
	*     @type string		  $section_class CSS Class of the section (<div>
	*     @type string		  $title_class	 CSS Class of the title 
	*	  @type string		  $entry_class   CSS Class of each entry
 
	*     @type string       $title        List heading. Default 'Related Blogs'.
	*     @type string       $item_spacing Whether to preserve whitespace within the menu's HTML. Accepts 'preserve' or 'discard'.
	*                                      Default 'preserve'.
	*
	*	   @return string - HTML to insert into widget/page.
	* 
	*/
	function q5_toc_list_peer ( $post, $args = '' ) 
	{
		$defaults = array(
			'title'        => __( 'Related Blogs' ),
			'section_class'=> '',
			'title_class'  => '',
			'entry_class'  => '',
			'item_spacing' => 'preserve',
		);
		$output = '';
		$r = wp_parse_args( $args, $defaults );

		if ( ! in_array( $r['item_spacing'], array( 'preserve', 'discard' ), true ) ) 
		{
			// invalid value, fall back to default.
			$r['item_spacing'] = $defaults['item_spacing'];
		}

		$categories = get_the_category($post->id);
		if ( ! empty( $categories ) ) 
		{
			$section_start_function = 'q5_toc_add_section_start';
			$section_end_function = 'q5_toc_null_function';
			$linked = array();
			$categories_list = '';
			$separator = '';
			foreach ( (array) $categories as $category ) 
			{
				$peers = get_posts(array('category_name' => $category->slug,
										 'numberposts' => -1,
										 'exclude'  => $post->ID));
				if (!empty($peers))
				{
					foreach ((array) $peers as $peer)
					{
						$link = get_permalink($peer);
						if ($link == null)
						{
							$link = get_page_link($peer);
						}
						if(!array_key_exists($link, $linked))
						{
							$linked[$link] = $link;

							$output .= $section_start_function($r);
							$section_start_function = 'q5_toc_null_function';
							$section_end_function = 'q5_toc_add_section_end';
							$output .= '<li><a  class="'. $r['entry_class'] . '" href="' . $link .'">' . $peer->post_title . '</a></li>';
						}
					}
				}
			}
			
			$output .= $section_end_function();
		}
		return $output;
	}
}

if (! class_exists('q5_toc_widget')){
	/*
	* Q5 TOC Wigdet for inclusion in sidebars.
	*/
	class q5_toc_widget extends WP_Widget
	{
		public function __construct()
		{
			parent::__construct ('q5_toc_widget', 'Q5 TOC Widget');
		}
	
		public function widget ($args, $instance)
		{	
			global $wp_query;
			$toc_definition = q5_toc_definition::get_instance();
			$post = get_post( $wp_query->post->ID );
		
			$toc = new q5_toc();
			$toc->build_toc(wptexturize($post->post_content));
			// TOC is rendered twice - see reason above.
			$toc_args = array (
				'toc_title' => $toc_definition->get_title()
			);
			$toc->render_toc($toc_args);
			$hiddenToc = array (
				'toc_title' 			=> $toc_definition->get_title(),
				'toc_class'             => 'q5_toc_hidden',
				'toc_title_class'       => 'q5_toc_title_hidden',
				'toc_hidden'			=> true,
			);
			$toc->render_toc($hiddenToc);
					
			if (is_page())
			{		
				// ChildPages
				$child_args = array (
					'title'     	=> $toc_definition->get_child_title(),
					'section_class'	=> 'q5_toc_child',
					'title_class'	=> 'q5_toc_child_title',
					'entry_class'	=> 'q5_toc_child_entry',
				);
				echo q5_toc_list_child_pages($post, $child_args); 
					
				$child_args = array (
					'title'     	=> $toc_definition->get_child_title(),
					'section_class'	=> 'q5_toc_child_hidden',
					'title_class'	=> 'q5_toc_child_title_hidden',
					'entry_class'	=> 'q5_toc_child_entry_hidden',
					'toc_hidden' => true,
				);

				echo q5_toc_list_child_pages($post, $child_args);  
					
				// Parent Page - uses same CSS classes as child section.
				$parent_args = array(
					'title'      	=> $toc_definition->get_parent_title(),
					'section_class'	=> 'q5_toc_child',
					'title_class'	=> 'q5_toc_child_title',
					'entry_class'	=> 'q5_toc_child_entry',
				);
				echo q5_toc_list_parent($post, $parent_args); 
					
				$parent_args = array(
					'title'     	=> $toc_definition->get_parent_title(),
					'section_class'	=> 'q5_toc_child_hidden',
					'title_class'	=> 'q5_toc_child_title_hidden',
					'entry_class'	=> 'q5_toc_child_entry_hidden',
					'toc_hidden'    => true,
				);
				echo q5_toc_list_parent($post, $parent_args);
			}
		
			// Peer Blog Pages - uses same CSS classes as child section.
			if (is_single())
			{
				$peer_args = array(
					'title'     	=> $toc_definition->get_peer_blog_title(),
					'section_class'	=> 'q5_toc_child',
					'title_class'	=> 'q5_toc_child_title',
					'entry_class'	=> 'q5_toc_child_entry',
				);
				echo q5_toc_list_peer($post, $peer_args); 
					
				$peer_args = array(
					'title'     	=> $toc_definition->get_peer_blog_title(),
					'section_class'	=> 'q5_toc_child_hidden',
					'title_class'	=> 'q5_toc_child_title_hidden',
					'entry_class'	=> 'q5_toc_child_entry_hidden',
					'toc_hidden'    => true,
				);
				echo q5_toc_list_peer($post, $peer_args);
			}		
		}
	}
}

if ( ! function_exists( 'q5_toc_insert_anchor_points' ) ) {
/**
 * function q5_toc_insert_anchor_points
 * ======== ===========================
 *
 * Description:
 * ============
 * Add id attributes to the header elements that we wish to use as ToC elements.
 * Note: Anchor_id is passed in by Reference. This allows it to be modified at each recursive call.
 */
	function q5_toc_insert_anchor_points (&$anchor_id, DOMDocument $doc, $node)
	{
		$q5_toc_definition = q5_toc_definition::get_instance();
		if ($q5_toc_definition->is_toc_element($node))
		{
			if ($node->getAttribute('id') == null)
			{
				$node->setAttribute('id', q5_toc_definition::construct_anchor($anchor_id++));
			}
		}

		if ($node->childNodes != null)
		{
			foreach($node->childNodes as $childNode)
			{
				q5_toc_insert_anchor_points ($anchor_id, $doc, $childNode);
			}
		}
	}
}

define ('Q5_TOC_TOP_MARKER', '<b id="q5_toc_top"></b>');
define ('Q5_TOC_TAIL_MARKER', '<b id="q5_toc_tail"></b>');

if ( ! function_exists( 'q5_toc_add_anchor_points_content_filter' ) ) {
/**
 * function q5_toc_add_anchor_points_content_filter
 * ======== =======================================
 
 * Description:
 * ============
 * Add anchor points to the header elements that we wish to use as ToC.
 * This code is executed as a filter over the content.
*/

	function q5_toc_add_anchor_points_content_filter ( $content ) 
	{	
		// Do not filter if editing.
		if (function_exists('get_current_screen')){
			$screen = get_current_screen();
			if ($screen->parent_base != 'edit')
			{
				return $content;
			}
		}
		
		// Use DOMDocument to parse content as HTML to determine anchor points.
		
		// DOMDocument will top and tail content to create valid HTML document.
		// So we add marker points to enable us to return the extract we need.
		set_error_handler('q5_toc_dom_error_handler');
		
		$anchor_id = 0;
		$dom = new DOMDocument();
		$dom->loadHTML(Q5_TOC_TOP_MARKER . $content . Q5_TOC_TAIL_MARKER);
				
		foreach ($dom->childNodes as $child)
		{
			q5_toc_insert_anchor_points ($anchor_id, $dom, $child);
		}

		//Remove tags added by DOMDocument, plus the marker tags that we added.
		$content =  $dom->saveHTML();
		$content = substr ( $content, strpos( $content, Q5_TOC_TOP_MARKER, 0 ) + strlen(Q5_TOC_TOP_MARKER), -1 );
		$content = substr ( $content, 0, strpos( $content, Q5_TOC_TAIL_MARKER, 0 ) );

		restore_error_handler();
		return $content;
	}
}

if ( ! function_exists( 'q5_toc_dom_error_handler' ) ) {
	function q5_toc_dom_error_handler($number, $error)
	{
		if (!preg_match('/^DOMDocument::loadHTML/', $error, $m))
		{
			throw new Exception($m[1]);	
		}
		// echo ('<p class="q5_toc_error">Q5_TOC: ' . $error . '</p>');
	}
}

/*
 * Plugin Registration
 * ===================
 */
require_once('q5-toc-definition.php');
require_once('q5-toc-registration.php');
register_activation_hook   ( __FILE__ , 'q5_toc_registration::q5_toc_activation');
register_deactivation_hook ( __FILE__ , 'q5_toc_registration::q5_toc_deactivation');
register_uninstall_hook    ( __FILE__ , 'q5_toc_registration::q5_toc_uninstall');

//Register css styles.
add_action('wp_enqueue_scripts', 'q5_toc_registration::register_q5_toc_scripts');

// Register Admin menus.
require_once ('q5-toc-admin-menu.php');
$q5_admin_menu = new q5_toc_admin_menu();

add_action('admin_menu', array($q5_admin_menu, 'q5_toc_options_page'));
		
if (!empty ($GLOBALS['pagenow']) &&
	('options-general.php' === $GLOBALS['pagenow'] || 'options.php' === $GLOBALS['pagenow']))
{
	add_action('admin_init', array($q5_admin_menu, 'q5_toc_add_menu_section'));
	add_action('admin_init', array($q5_admin_menu, 'q5_toc_add_menu_fields'));
	add_action('admin_init', array($q5_admin_menu, 'q5_toc_register_setting'));
}

add_action('widgets_init', 'q5_toc_registration::q5_toc_widget_register');
// Do not activate TOC if in 'Edit' mode;
$q5_toc_allow = true;
if (function_exists('get_current_screen'))
{
	$screen = get_current_screen();
	$q5_toc_allow = ($screen->parent_base != 'edit');
}
if ($q5_toc_allow)
{
	add_filter('the_content', 'q5_toc_add_anchor_points_content_filter' );
}

?>
