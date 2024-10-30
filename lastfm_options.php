<?php

/**
 * Settings class
 *
 */ 
class lastfm_options {

	private $sections;
	private $checkboxes;
	private $settings;
	private $lastfm_tabs;

	/**
	 * Construct
	 */
	public function __construct($lastfm_tabs){
		$this->lastfm_tabs = $lastfm_tabs;
		$this->checkboxes = array();
		$this->settings = array();
		$this->get_settings();
		load_plugin_textdomain('lastfm_tabs', '',LASTFM_DIRNAME . '/languages');
		$this->sections['general']	= __('General settings','lastfm_tabs');
		$this->sections['tabs']		= __('Widget tabs','lastfm_tabs');
		$this->sections['reset']	= __('Reset to defaults','lastfm_tabs');
		$this->sections['about']	= __('About/Help','lastfm_tabs');
		add_action('admin_menu', array($this, 'add_pages'));
		add_action('admin_init', array($this, 'register_settings'));
		if (! get_option('lastfm_tabs_options'))
			$this->initialize_settings();
	}

	/**
	 * Add options page
	 */
	public function add_pages(){
		$admin_page = add_options_page(__('Last.fm options','lastfm_tabs'), __('Last.fm options','lastfm_tabs'), 'manage_options', 'lastfm-options', array($this, 'display_page'));
		add_action('admin_print_scripts-' . $admin_page, array($this, 'scripts'));
		add_action('admin_print_styles-' . $admin_page, array($this, 'styles'));
	}

	/**
	 * Create settings field
	 */
	public function create_setting($args = array()){
		$defaults = array(
			'id'		=> 'default_field',
			'title'		=> __('Default Field','lastfm_tabs'),
			'desc'		=> __('This is a default description.','lastfm_tabs'),
			'std'		=> '',
			'type'		=> 'text',
			'section'	=> 'general',
			'choices'	=> array(),
			'size'		=> '',
			'class'	=> ''
		);
		extract(wp_parse_args($args, $defaults ));
		$field_args = array(
			'type'		=> $type,
			'id'		=> $id,
			'desc'		=> $desc,
			'std'		=> $std,
			'choices'	=> $choices,
			'label_for'	=> $id,
			'size'		=> $size,
			'class'		=> $class
		);
		if ($type == 'checkbox')
			$this->checkboxes[] = $id;
		add_settings_field($id, $title, array($this, 'display_setting'), 'lastfm-options', $section, $field_args );
	}

	/**
	 * Display options page
	 *
	 */
	public function display_page(){
		echo '<div class="wrap">';
		if (function_exists('screen_icon'))
			screen_icon();
		else
			echo '<div class="icon32" id="icon-options-general"></div>';
		echo '<h2>' . __('Last.fm options','lastfm_tabs') . '</h2>';

		if ((isset($_GET['settings-updated'] ) && $_GET['settings-updated'] == true) || (isset($_POST['wp_delete_cache'])) )
			$this->lastfm_tabs->clear_transients();
		echo '<form action="options.php" method="post">';
		settings_fields('lastfm_tabs_options');
		echo '<div class="ui-tabs">	<ul class="ui-tabs-nav">';
		foreach ($this->sections as $section_slug => $section )
			echo '<li><a href="#' . $section_slug . '">' . $section . '</a></li>';
		echo '</ul>';
		do_settings_sections($_GET['page'] );
		echo '</div>';
		if (function_exists('submit_button'))
			submit_button();
		else
			echo '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . __('Save Changes','lastfm_tabs') . '" /></p>';
		echo '</form>';
		echo '<script type="text/javascript">
		jQuery(document).ready(function($){
			var sections = [];';
		foreach ($this->sections as $section_slug => $section )
			echo "sections['$section'] = '$section_slug';";

		echo 'var wrapped = $(".wrap h3").wrap("<div class=\"ui-tabs-panel\">");
			wrapped.each(function(){
				$(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
			});
			$(".ui-tabs-panel").each(function(index){
				$(this).attr("id", sections[$(this).children("h3").text()]);
				if (index > 0)
					$(this).addClass("ui-tabs-hide");
			});
			$(".ui-tabs").tabs({
				fx: { opacity: "toggle", duration: "fast" }
			});
			$("input[type=text], textarea").each(function(){
				if ($(this).val() == $(this).attr("placeholder") || $(this).val() == "")
					$(this).css("color", "#999");
			});
			$("input[type=text], textarea").focus(function(){
				if ($(this).val() == $(this).attr("placeholder") || $(this).val() == ""){
					$(this).val("");
					$(this).css("color", "#000");
				}
			}).blur(function(){
				if ($(this).val() == "" || $(this).val() == $(this).attr("placeholder")){
					$(this).val($(this).attr("placeholder"));
					$(this).css("color", "#999");
				}
			});
			$(".wrap h3, .wrap table").show();
			// This will make the "warning" checkbox class really stand out when checked.
			// I use it here for the Reset checkbox.
			$(".warning").change(function(){
				if ($(this).is(":checked"))
					$(this).parent().css("background", "#c00").css("color", "#fff").css("fontWeight", "bold");
				else
					$(this).parent().css("background", "none").css("color", "inherit").css("fontWeight", "normal");
			});
			// Browser compatibility
			if ($.browser.mozilla)
				$("form").attr("autocomplete", "off");
			});
		</script>
		</div>';
		echo '<form name="wp_cache_content_delete" method="post">';
		echo '<input type="hidden" name="wp_delete_cache" />';
		if (function_exists('submit_button'))
			submit_button(__('Clear caches','lastfm_tabs'),'delete');
		else
			echo '<p class="submit"><input type="submit" value="Clear caches" class="button-secondary delete" id="submit" name="submit"></p>';
		wp_nonce_field('lfmt');
		echo '</form>';	
	}

	/**
	 * Description for section
	 */
	public function display_section(){
		// code
	}

	/**
	 * Description for About/Help section
	 *
	 */
	public function display_about_section(){
		_e('<h4>Shortcode</h4>','lastfm_tabs');
		_e('<p>You can use the following shortcode:</p>','lastfm_tabs');
		echo '<p><span class="code">[lastfm tab="&lt;function&gt;" count="&lt;count&gt;" title="&lt;true/false&gt;"]</span>';
		_e('<p>The following functions are available: albums, friends, charts, artists, info, recent, loved and shouts</p>','lastfm_tabs');
		_e('<p>You may combine two or more of these functions with commas.</p>','lastfm_tabs');
		_e('<p>The title-parameter enables/disables output of the corresponding title for each function. Title and count are optional. If not provided, the plugin options will be used.</p>','lastfm_tabs');
		_e('<p>An example:</p>','lastfm_tabs');
		echo '<p><span class="code">[lastfm tab="albums, friends, charts, artists, info, recent, loved, shouts" count="3" titles="true"]</span></p>';
		_e('<p>Would display all functions in that order with max. 3 entries each and print the titles.</p>','lastfm_tabs');
		_e('<h4>Template tag</h4>','lastfm_tabs');
		_e('<p>In your templates you can use the function <span class="code">lastfm_tabs_render($functions=array(), $count=false, $echotitle=false, $return=false)</span></p>','lastfm_tabs');
		_e('<p>So the following would output a list of 5 of your friends and the 5 most listened to albums without titles:</p>','lastfm_tabs');
		echo '<p><span class="code">&lt;?php if(function_exists(\'lastfm_tabs_render\')){<br />
				&nbsp;&nbsp;&nbsp;&nbsp;lastfm_tabs_render(array(\'friends\',\'albums\'),5,false);<br />}?&gt;</span></p>';
		_e('<p>If you do not want to echo the function\'s return, add the last parameter and set it to true.</p>','lastfm_tabs');
		_e('<h4>Note</h4>','lastfm_tabs');
		_e('<p>Changes in a template tag will not immediately take effect since all the data is cached. If you change options in a template tag you have to manually clean the plugin cache.</p>','lastfm_tabs');
		_e('<p>If you have any questions or ideas to improve this plugins, write to felix@kreativkonzentrat.de or drop a line at ','lastfm_tabs');
		echo '<a href="http://kreativkonzentrat.de/kontakt" title="Kreativkonzentrat">kreativkonzentrat.de</a></p>';
	}

	/**
	 * HTML output for text field
	 */
	public function display_setting($args = array()){
		extract($args );
		$options = get_option('lastfm_tabs_options');
		if (! isset($options[$id] ) && $type != 'checkbox')
			$options[$id] = $std;
		elseif (! isset($options[$id] ))
			$options[$id] = 0;
		$field_class = '';
		if ($class != '')
			$field_class = ' ' . $class;
		switch ($type ){
			case 'heading':
				echo '</td></tr><tr valign="top"><td colspan="2"><h4>' . $desc . '</h4>';
				break;
			case 'checkbox':
				echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="lastfm_tabs_options[' . $id . ']" value="1" ' . checked($options[$id], 1, false ) . ' /> <span class="description">' . $desc . '</span>';
				break;
			case 'select':
				echo '<select class="select' . $field_class . '" name="lastfm_tabs_options[' . $id . ']">';
				foreach ($choices as $value => $label )
					echo '<option value="' . esc_attr($value ) . '"' . selected($options[$id], $value, false ) . '>' . $label . '</option>';
				echo '</select>';
				if ($desc != '')
					echo '<br /><span class="description">' . $desc . '</span>';
				break;
			case 'radio':
				$i = 0;
				foreach ($choices as $value => $label ){
					echo '<input class="radio' . $field_class . '" type="radio" name="lastfm_tabs_options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr($value ) . '" ' . checked($options[$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
					if ($i < count($options ) - 1 )
						echo '<br />';
					$i++;
				}
				if ($desc != '')
					echo '<br /><span class="description">' . $desc . '</span>';
				break;
			case 'textarea':
				echo '<textarea class="' . $field_class . '" id="' . $id . '" name="lastfm_tabs_options[' . $id . ']" placeholder="' . $std . '" rows="5" cols="30">' . wp_htmledit_pre($options[$id] ) . '</textarea>';
				if ($desc != '')
					echo '<br /><span class="description">' . $desc . '</span>';
				break;
			case 'password':
				echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="lastfm_tabs_options[' . $id . ']" value="' . esc_attr($options[$id] ) . '" />';
				if ($desc != '')
					echo '<br /><span class="description">' . $desc . '</span>';
				break;	
			case 'text':
			default:
				if (isset($size) && $size > 0)
					$size = ' size="'.$size.'" class="text"';
				else
					$size = 'class="regular-text';
				echo '<input '.$size. $field_class . '" type="text" id="' . $id . '" name="lastfm_tabs_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr($options[$id] ) . '" />';
				if ($desc != '')
					echo '<br /><span class="description">' . $desc . '</span>';
				break;
		}
	}

	/**
	 * Settings and defaults
	 */
	public function get_settings(){

		/* General Settings
		 *
		 */
		$this->settings['widget_title'] = array(
			'title'		=> __('Widget title','lastfm_tabs'),
			'desc'		=> __('Default widget title','lastfm_tabs'),
			'std'		=> 'Last.fm',
			'type'		=> 'text',
			'section'	=> 'general'
		);
		$this->settings['username'] = array(
			'title'		=> __('Last.fm username','lastfm_tabs'),
			'desc'		=> __('Your last.fm username','lastfm_tabs'),
			'std'		=> '',
			'type'		=> 'text',
			'section'	=> 'general'
		);
		$this->settings['cover_size'] = array(
			'section'	=> 'general',
			'title'		=> __('Cover size','lastfm_tabs'),
			'desc'		=> __('Size of displayed images','lastfm_tabs'),
			'type'		=> 'select',
			'std'		=> '2',
			'choices'	=> array(
				'0' => __('small','lastfm_tabs'),
				'1' => __('medium','lastfm_tabs'),
				'2' => __('large','lastfm_tabs'),
				'3' => __('extra large','lastfm_tabs')
			)
		);
		$this->settings['expiration'] = array(
			'title'		=> __('Cache expiration','lastfm_tabs'),
			'desc'		=> __('Time in minutes','lastfm_tabs'),
			'std'		=> '300',
			'size'		=> '5',
			'type'		=> 'text',
			'section'	=> 'general'
		);
		$this->settings['nocoverurl'] = array(
			'title'		=> __('Fallback cover','lastfm_tabs'),
			'desc'		=> __('URL of fallback cover when no image is found','lastfm_tabs'),
			'std'		=> LASTFM_URI . '/img/nocover_160.jpg',
			'type'		=> 'text',
			'section'	=> 'general'
		);
		$this->settings['fancyness'] = array(
			'section'	=> 'general',
			'title'		=> __('Fancy mouseover effect','lastfm_tabs'),
			'desc'		=> __('Sliding/fading caption on covers','lastfm_tabs'),
			'type'		=> 'checkbox',
			'std'		=> 1 // Set to 1 to be checked by default, 0 to be unchecked by default.
		);
		$this->settings['effect'] = array(
			'section'	=> 'general',
			'title'		=> __('Effect','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'select',
			'std'		=> 'slide',
			'choices'	=> array(
				'fade' 	=> __('fade','lastfm_tabs'),
				'slide' => __('slide','lastfm_tabs')
			)
		);
		$this->settings['effect_duration'] = array(
			'title'		=> __('Effect duration','lastfm_tabs'),
			'desc'		=> __('in ms','lastfm_tabs'),
			'std'		=> '150',
			'type'		=> 'text',
			'size'		=> '5',
			'section'	=> 'general'
		);
		$this->settings['effect_xy'] = array(
			'section'	=> 'general',
			'title'		=> __('Effect origin','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'select',
			'std'		=> 'bottom',
			'choices'	=> array(
				'bottom'	=> __('bottom','lastfm_tabs'),
				'top'		=> __('top','lastfm_tabs'),
				'left'		=> __('left','lastfm_tabs'),
				'right'		=> __('right','lastfm_tabs')
			)
		);
		$this->settings['use_stylesheet'] = array(
			'title'		=> __('Use stylesheet?','lastfm_tabs'),
			'desc'		=> __('Needed for sliding/fading captions. Fixes image width and height to 126px.<br /> You can place your own lastfm.css in your template folder to overwrite the default stylesheet.','lastfm_tabs'),
			'std'		=> 1,
			'type'		=> 'checkbox',
			'section'	=> 'general'
		);
		$this->settings['enable_tabs'] = array(
			'title'		=> __('Enable tabbed navigation?','lastfm_tabs'),
			'desc'		=> __('jQuery tabs for your Last.fm widget','lastfm_tabs'),
			'std'		=> 1,
			'type'		=> 'checkbox',
			'section'	=> 'general'
		);
		$this->settings['enable_artistimage'] = array(
			'title'		=> __('Show artist image when no album cover is found instead of default cover?','lastfm_tabs'),
			'desc'		=> __('May increase loading time','lastfm_tabs'),
			'std'		=> 1,
			'type'		=> 'checkbox',
			'section'	=> 'general'
		);
		$this->settings['debugmode'] = array(
			'title'		=> __('Enable debugmode?','lastfm_tabs'),
			'desc'		=> __('Shows some caching information','lastfm_tabs'),
			'std'		=> 0,
			'type'		=> 'checkbox',
			'section'	=> 'general'
		);

		/*
		 * Tabs
		 */
		$this->settings['enable_recently'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Enable recently played tracks tab?','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'checkbox',
			'std'		=> 1
		);
		$this->settings['recently_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Recently played'
		);
		$this->settings['trackcount'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Number of tracks to display','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'size'		=> '3',
			'std'		=> '4'
		);
		$this->settings['enable_info'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Enable user info tab?','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'checkbox',
			'std'		=> 0
		);
		$this->settings['info_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Profile'
		);
		$this->settings['enable_friends'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Enable friends tab?','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'checkbox',
			'std'		=> 0
		);
		$this->settings['friends_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Friends'
		);		
		$this->settings['friendscount'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Number of friends to display','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'size'		=> '3',
			'std'		=> '4'
		);
		$this->settings['enable_lovedtracks'] = array(
			'section'	=> 'tabs',
			'title'  	=> __('Enable loved tracks tab?','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'checkbox',
			'std'		=> 1
		);
		$this->settings['loved_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Favorites'
		);
		$this->settings['lovedcount'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Number of loved tracks to display','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'size'		=> '3',
			'std'		=> '4'
		);
		$this->settings['enable_shouts'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Enable shouts tab?','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'checkbox',
			'std'		=> 0
		);
		$this->settings['shouts_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Shouts'
		);
		$this->settings['shoutcount'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Number of shouts to display','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'size'		=> '3',
			'std'		=> '4'
		);
		$this->settings['enable_charts'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Enable weekly album charts tab?','lastfm_tabs'),
			'desc'		=> __('May increase loading time on big lists when not cached yet','lastfm_tabs'),
			'type'		=> 'checkbox',
			'std'		=> 0
		);
		$this->settings['charts_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Charts'
		);
		$this->settings['chartscount'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Number of chart entries to display','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'size'		=> '3',
			'std'		=> '4'
		);
		$this->settings['enable_top_albums'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Enable top albums tab?','lastfm_tabs'),
			'desc'		=> __('May increase loading time on big lists when not cached yet','lastfm_tabs'),
			'type'		=> 'checkbox',
			'std'		=> 0
		);
		$this->settings['top_albums_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Top albums'
		);
		$this->settings['top_albums_count'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Number of top album entries to display','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'size'		=> '3',
			'std'		=> '4'
		);
		$this->settings['top_albums_period'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Period','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'select',
			'std'		=> 'bottom',
			'choices'	=> array(
				'0' => 'overall',
				'1' => '7 days',
				'2' => '3 months',
				'3' => '6 months',
				'4' => '12 months'
			)
		);
		$this->settings['enable_top_artists'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Enable top artists tab?','lastfm_tabs'),
			'desc'		=> __('May increase loading time on big lists when not cached yet','lastfm_tabs'),
			'type'		=> 'checkbox',
			'std'		=> 0
		);
		$this->settings['top_artists_title'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Title:','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'std'		=> 'Top artists'
		);
		$this->settings['top_artists_count'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Number of top album entries to display','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'text',
			'size'		=> '3',
			'std'		=> '4'
		);
		$this->settings['top_artists_period'] = array(
			'section'	=> 'tabs',
			'title'		=> __('Period','lastfm_tabs'),
			'desc'		=> '',
			'type'		=> 'select',
			'std'		=> 'bottom',
			'choices'	=> array(
				'0' => 'overall',
				'1' => '7 days',
				'2' => '3 months',
				'3' => '6 months',
				'4' => '12 months'
			)
		);
		$this->settings['reset_theme'] = array(
			'section'	=> 'reset',
			'title'		=> __('Reset options','lastfm_tabs'),
			'type'		=> 'checkbox',
			'std'		=> 0,
			'class'		=> 'warning', // Custom class for CSS
			'desc'		=> __('Check this box and click "Save Changes" below to reset all plugin options to their defaults.','lastfm_tabs')
		);
	}

	/**
	 * Initialize default settings values
	 */
	public function initialize_settings(){
		$default_settings = array();
		foreach ($this->settings as $id => $setting ){
			if ($setting['type'] != 'heading')
				$default_settings[$id] = $setting['std'];
		}
		update_option('lastfm_tabs_options', $default_settings );
	}

	/**
	 * Register settings
	 */
	public function register_settings(){
		register_setting('lastfm_tabs_options', 'lastfm_tabs_options', array(&$this, 'validate_settings'));
		foreach ($this->sections as $slug => $title ){
			if ($slug == 'about')
				add_settings_section($slug, $title, array($this, 'display_about_section'), 'lastfm-options');
			else
				add_settings_section($slug, $title, array($this, 'display_section'), 'lastfm-options');
		}
		$this->get_settings();
		foreach ($this->settings as $id => $setting ){
			$setting['id'] = $id;
			$this->create_setting($setting );
		}
	}

	/**
	 * jQuery Tabs
	 */
	public function scripts(){
		wp_print_scripts('jquery-ui-tabs');
	}

	/**
	 * Styling
	 */
	public function styles(){
		wp_register_style('lastfm_tabs_admin', LASTFM_URI . '/css/lastfm_tabs_admin.css');
		wp_enqueue_style('lastfm_tabs_admin');
	}

	/**
	 * Validation
	 *
	 */
	public function validate_settings($input ){
		$this->lastfm_tabs->clear_transients(false);
		if (! isset($input['reset_theme'] )){
			$options = get_option('lastfm_tabs_options');
			foreach ($this->checkboxes as $id ){
				if (isset($options[$id] ) && ! isset($input[$id] ))
					unset($options[$id] );
			}
			return $input;
		}
		return false;
	}
}
?>