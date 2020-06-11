<?php
/*
 *
 */
 
define ('Q5_ANCHOR_PREFIX', 'q5_anchor');

class q5_toc_definition
{	
	private static $q5_toc_definition;
	
	public static function get_instance()
	{
		if (q5_toc_definition::$q5_toc_definition == NULL)
		{
			q5_toc_definition::$q5_toc_definition = new q5_toc_definition();
		}
		return q5_toc_definition::$q5_toc_definition;
	}
	/*
	public static function toc_elements()
	{
		return $toc_headers::array_values();
	}
	*/
	public static function construct_anchor ($id)
	{
		return Q5_ANCHOR_PREFIX . $id;
	}
	// End Static Functions
	// ====================
		
	private $depth = 3;
	
	// Start on h2 as that is the default first heading in CoBlocks
	private $headers = array(1 => 'h2',
					2 => 'h3',
					3 => 'h4',
					4 => 'h5',
					5 => 'h6',
					6 => 'h7');
					
	private $title = 'Table of Contents';

	private $child_title = 'Topics';
	
	private $parent_title = 'Main Topic';
	
	private $peer_blog_title = 'Related Blogs';
	
	private $toc_elements_key; // TOC elements keyed on element name.
	
	private function __construct()
	{
		$depth_option = get_option(q5_toc_registration::$depth_field_id);
		if ($depth_option != null)
		{
			$this->depth = $depth_option;
		}
		
		$headers_option = get_option(q5_toc_registration::$toc_elements_field_id);
		if ($headers_option != null)
		{
			$this->headers = $headers_option;
		}
		$this->toc_elements_key = array_flip($this->headers);
		
		$title_option = get_option(q5_toc_registration::$title_field_id);
		if ($title_option != null)
		{
			$this->title = $title_option;
		}

		$child_title_option = get_option(q5_toc_registration::$child_title_field_id);
		if ($child_title_option != null)
		{
			$this->child_title = $child_title_option;
		}


		$parent_title_option = get_option(q5_toc_registration::$parent_title_field_id);
		if ($parent_title_option != null)
		{
			$this->parent_title = $parent_title_option;
		}


		$peer_blog_title_option = get_option(q5_toc_registration::$peer_blog_title_field_id);
		if ($peer_blog_title_option != null)
		{
			$this->peer_blog_title = $peer_blog_title_option;
		}
	}
		
	public function get_depth()
	{
		return $this->depth;
	}		

	public function get_headers()
	{
		return $this->headers;
	}
		
	public function get_title()
	{
		return $this->title;
	}		

	public function get_child_title()
	{
		return $this->child_title;
	}
		
	public function get_parent_title()
	{
		return $this->parent_title;
	}		

	public function get_peer_blog_title()
	{
		return $this->peer_blog_title;
	}

	public function is_toc_element($element)
	{		
		if(array_key_exists($element->nodeName, $this->toc_elements_key) )
		return array_key_exists($element->nodeName, $this->toc_elements_key);
	}
	
	public function toc_element_level($element)
	{
		return  $this->is_toc_element($element) ? 
			$this->toc_elements_key[$element->nodeName] :
			-1;
	}
}
?>
