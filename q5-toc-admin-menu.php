<?php
/*
 * q5_toc_admin_menu
 * =================
 * Defines the admin menus for the q5_toc plugin
 * The menus are displayed as a sub-entry to the settings menu.
 *
 * Version: 1.0.0
 *
 * Since:   5.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

class q5_toc_admin_menu
{
	
	private $option_group = 'q5_toc';
	
	// Register Settings
	// =================
	public function q5_toc_register_setting()
	{
		// Register settings/fields:
		//	Depth
		//	Array of HTML Elements
		//  Contents titles.
		
		register_setting (
			$this->option_group, 
			q5_toc_registration::$depth_field_id, 
			array ('type' => 'integer', 
			'description' => 'Q5 TOC Depth of Table of Contents. Maximum value is 6')); 

				
		register_setting (
			$this->option_group,
			q5_toc_registration::$toc_elements_field_id,
			array('type'        => 'string',
			'description'       => 'Q5 TOC Elements',
			'sanitize_callback' =>array($this, 'q5_toc_sanitize_html_elements')));
			
		register_setting (
			$this->option_group, 
			q5_toc_registration::$title_field_id, 
			array ('type' => 'string', 
			'description' => 'Q5 TOC Title',
			'sanitize_callback' =>array($this, 'q5_toc_sanitize_text_field'))); 

				
		register_setting (
			$this->option_group,
			q5_toc_registration::$child_title_field_id,
			array('type'        => 'string',
			'description'       => 'Q5 TOC Title Child Pages section',
			'sanitize_callback' =>array($this, 'q5_toc_sanitize_text_field')));
			
		register_setting (
			$this->option_group, 
			q5_toc_registration::$parent_title_field_id, 
			array ('type' => 'string', 
			'description' => 'Q5 TOC Title Parent Page section',
			'sanitize_callback' =>array($this, 'q5_toc_sanitize_text_field'))); 

				
		register_setting (
			$this->option_group,
			q5_toc_registration::$peer_blog_title_field_id,
			array('type'        => 'string',
			'description'       => 'Q5 TOC Title Blogs/Posts',
			'sanitize_callback' =>array($this, 'q5_toc_sanitize_text_field')));
			
		register_setting (
			$this->option_group,
			q5_toc_registration::$peer_blog_exclude_categories_field_id,
			array('type'        => 'string',
			'description'       => 'Q5 TOC Peer Categories exclusion list',
			'sanitize_callback' =>array($this, 'q5_toc_sanitize_categories')));
			
	}
		
	public function q5_toc_sanitize_html_elements($input)
	{	/*
		* q5_toc_sanitize_html_elements
		* =============================
		* HTML elements are returned as a comma separated string.
		* We need to:
		*  1. Convert to an Indexed array
		*  2. Check each entry is constructed as a valid HTML element
		* 				(Starts with a letter and only contains alphanumerics)
		*  3. Each element must be unique.
		*/
		$elements = explode(',', $input);
		$ct = 0;
		$actual_elements = array();
		foreach ($elements as $item)
		{
			if (!$this->validHTMLelementName ($item))
			{
				// Invalid HTML Element name
				$input = q5_toc::get_headers();
				return $input;
			}
			if ($ct > 0)
			{
				for ($i = 0; $i<$ct; $i++)
				{
					if ($item == $actual_elements[$i])
					{
						// Element name not unique;
						$input = q5_toc::get_headers();
						return $input;
					}
				}
			}
			$actual_elements[$ct++] = $item;
		}

		$input = $actual_elements;
		return $input;
	}
	
	private function validHTMLelementName ($name)
	{
		$trim = trim($name);
		return isset($trim) && 
		     (1 == preg_match("/^[a-z | A-Z][a-z | A-Z | 0-9]*$/", $trim));
	}
	
	public function q5_toc_sanitize_text_field($input)
	{
		return sanitize_text_field($input);
	}
	
	public function q5_toc_sanitize_categories($input)
	{
		$elements = explode(',', sanitize_text_field($input));
		$index_categories = array();
		foreach ($elements as $item)
		{
			if (!empty($item) && !array_key_exists($item, $index_categories))
			{
				$index_categories[$item] = $item;
			}
		}
		$input = $index_categories;
		return $input;
	}
	
	// Define and populate the Fields:
	// ===============================
	public function q5_toc_add_menu_fields()
	{
		$toc_defintion = q5_toc_definition::get_instance();
		
		//Depth field
		add_settings_field(
				q5_toc_registration::$depth_field_id,// id.
				'TOC Depth',                         // title
				array($this, 'q5_toc_depth_html'),   // callback to display HTML
				$this->option_group,                 // Page / Sub-menu
				'q5_toc_section',                    // Section
				array (
					'name'        => q5_toc_registration::$depth_field_id,
					'value'       => $toc_defintion->get_depth(),
					'option_name' => q5_toc_registration::$depth_field_id));
					
		//HTML Element fields
		$elements = get_option(q5_toc_registration::$toc_elements_field_id);
		if (!is_array($elements))
		{
			$elements = q5_toc::headers;
		}
		
		add_settings_field( 
				q5_toc_registration::$toc_elements_field_id, // id.
				'TOC Elements',                              // title
				array($this, 'q5_toc_elements_html'),        // callback to display HTML
				$this->option_group,                         // Page / Sub-menu
				'q5_toc_section',                            // Section
				array (
					'name'             => q5_toc_registration::$toc_elements_field_id,
					'value'            => $toc_defintion->get_headers(),
					'option_name'      => q5_toc_registration::$toc_elements_field_id));
					
		//Title field
		add_settings_field(
				q5_toc_registration::$title_field_id,// id.
				'TOC Title',                         // title
				array($this, 'q5_toc_title_html'),   // callback to display HTML
				$this->option_group,                 // Page / Sub-menu
				'q5_toc_section',                    // Section
				array (
					'name'        => q5_toc_registration::$title_field_id,
					'value'       => $toc_defintion->get_title(),
					'option_name' => q5_toc_registration::$title_field_id));
					
		//Title Child page section
		add_settings_field( 
				q5_toc_registration::$child_title_field_id,  // id.
				'TOC Title Child Page',                      // title
				array($this, 'q5_toc_child_title_html'),     // callback to display HTML
				$this->option_group,                         // Page / Sub-menu
				'q5_toc_section',                            // Section
				array (
					'name'             => q5_toc_registration::$child_title_field_id,
					'value'            => $toc_defintion->get_child_title(),
					'option_name'      => q5_toc_registration::$child_title_field_id));
					
		//Title Parent section
		add_settings_field(
				q5_toc_registration::$parent_title_field_id,// id.
				'TOC Title Parent Page',                    // title
				array($this, 'q5_toc_parent_title_html'),   // callback to display HTML
				$this->option_group,                        // Page / Sub-menu
				'q5_toc_section',                           // Section
				array (
					'name'        => q5_toc_registration::$parent_title_field_id,
					'value'       => $toc_defintion->get_parent_title(),
					'option_name' => q5_toc_registration::$parent_title_field_id));
					
		//Peer Blog pages
		add_settings_field( 
				q5_toc_registration::$peer_blog_title_field_id, // id.
				'TOC Title Peer Blogs',                         // title
				array($this, 'q5_toc_peer_blog_title_html'),    // callback to display HTML
				$this->option_group,                            // Page / Sub-menu
				'q5_toc_section',                               // Section
				array (
					'name'             => q5_toc_registration::$peer_blog_title_field_id,
					'value'            => $toc_defintion->get_peer_blog_title(),
					'option_name'      => q5_toc_registration::$peer_blog_title_field_id));

		//Peer Blog Exclude Categories
		add_settings_field( 
				q5_toc_registration::$peer_blog_exclude_categories_field_id, // id.
				'Peer Categories to Exclude',                                // title
				array($this, 'q5_toc_peer_blog_exclude_categories_html'),    // callback to display HTML
				$this->option_group,                            // Page / Sub-menu
				'q5_toc_section',                               // Section
				array (
					'name'        => q5_toc_registration::$peer_blog_exclude_categories_field_id,
					'value'       => $toc_defintion->get_peer_exclude_categories_list(),
					'option_name' => q5_toc_registration::$peer_blog_exclude_categories_field_id));
	
	}
	public function q5_toc_depth_html ($data)
	{
		echo '<input name="' . $data['name'] . '" id="' . $data['name'] . '"';
		echo ' type="number" value="' . $data['value'] . '" min="1" max="6" size="5pt"';
        echo ' onChange="q5_toc_allVisibilityCheck()"/>';
	}

	public function q5_toc_elements_html ($data)
	{
		// Create a single hidden (actually display:none) field to hold the (max) 6 element names. 
		echo '<input name="' . $data['name'] . '" id="' . $data['name'] . '" type="text" style="display:none" />';

		$i = 1;		
		foreach ($data['value'] as $v)
		{

			echo '<input name="H' . $i .  
				'" id="H' . $i . '" type="text" value="' . $v . '" size="5pt"/>';
			echo '<script type="text/javascript">q5_toc_visibilityCheck("H' . $i . '", ' . $i++ . ') </script>';
		}		
	}
	
	public function q5_toc_title_html ($data)
	{
		echo '<input name="' . $data['name'] . '" id="' . $data['name'] . '"';
		echo ' type="text" value="' . $data['value'] . '"/>';
	}

	public function q5_toc_child_title_html ($data)
	{
		echo '<input name="' . $data['name'] . '" id="' . $data['name'] . '"';
		echo ' type="text" value="' . $data['value'] . '"/>';
	}

	public function q5_toc_parent_title_html ($data)
	{
		echo '<input name="' . $data['name'] . '" id="' . $data['name'] . '"';
		echo ' type="text" value="' . $data['value'] . '"/>';
	}

	public function q5_toc_peer_blog_title_html ($data)
	{
		echo '<input name="' . $data['name'] . '" id="' . $data['name'] . '"';
		echo ' type="text" value="' . $data['value'] . '"/>';
	}

	public function q5_toc_peer_blog_exclude_categories_html ($data)
	{
		$categories = '';
		$separator = '';
		foreach ($data['value'] as $cat_item)
		{
			$categories .= $separator . $cat_item;
			$separator = ',';
		}
		echo '<input name="' . $data['name'] . '" id="' . $data['name'] . '"';
		echo ' type="text" value="' . $categories . '"/>';
	}
	
	// Define Sections
	// ===============
	public function q5_toc_add_menu_section($page)
	{
		add_settings_section ('q5_toc_section',
			'Q5 TOC Configuration',
			array($this, 'q5_toc_menu_section_callback'),
			$this->option_group,
			9);
	}
	
	public function q5_toc_menu_section_callback ()
	{
		echo '<p>';
		_e('Select the Depth to which you would like the table of Contents displayed');
		_e('(Max depth is 6) and the HTML Elements that identifies each heading');
		echo'</p><p>';
		_e('The HTML elements must be unique');
		echo '</p>';
	}
	
	// Add TOC Options Page
	// ====================
	public function q5_toc_options_page()
	{
		add_options_page(
			'Q5 TOC Options',
			'Q5 Toc',
			'manage_options',
			$this->option_group,
			array ($this, 'q5_toc_options_page_html'));
	}
	
	public function q5_toc_options_page_html()
	{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
	
    ?>
    <div class="wrap">
		<script type="text/javascript">
		function q5_toc_visibilityCheck (elementId, level)
		{
			
			if (document.getElementById("q5_toc_depth").value >= level)
			{
				document.getElementById(elementId).style.visibility="visible";
			}
			else
			{
				document.getElementById(elementId).style.visibility="hidden";
			}	
		}
		function q5_toc_allVisibilityCheck()
		{
			q5_toc_visibilityCheck ("H2", 2);
			q5_toc_visibilityCheck ("H3", 3);
			q5_toc_visibilityCheck ("H4", 4);
			q5_toc_visibilityCheck ("H5", 5);
			q5_toc_visibilityCheck ("H6", 6);
		}
		function q5_toc_validateForm()
		{
			var h1 = document.getElementById("H1").value;
			var h2 = document.getElementById("H2").value;
			var h3 = document.getElementById("H3").value;
			var h4 = document.getElementById("H4").value;
			var h5 = document.getElementById("H5").value;
			var h6 = document.getElementById("H6").value;
			document.getElementById("q5_toc_elements_field").value = 
			          h1 + "," + h2 + "," + h3 + "," + h4 + "," + h5 + "," + h6;
			return true;
		}
	</script>
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" onsubmit="return q5_toc_validateForm()" method="post">
            <?php
            // output security fields for the registered setting
            settings_fields($this->option_group);
			
            // output setting sections and their fields
            do_settings_sections($this->option_group);
			
            // output save settings button
            submit_button (_e('Save Settings'));
            ?>
        </form>
    </div>
    <?php
	}
}
?>