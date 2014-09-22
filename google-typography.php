<?php
/*
Plugin Name: Google Typography
Plugin URI: http://projects.ericalli.com/google-typography/
Description: A simple plugin that lets you use and customize (in real-time!) any fonts from Google Fonts on your existing site, all without writing a single line of code.
Version: 1.1
Author: Eric Alli
Author URI: http://ericalli.com
*/

/**
 * GoogleTypography class
 *
 * @class GoogleTypography	The class that holds the entire Google Typography plugin
 */
class GoogleTypography {
		
	/**
	 * @var $api_url	The google web font API URL
	 */
	protected $api_url = "https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyCjae0lAeI-4JLvCgxJExjurC4whgoOigA";
	
	/**
	 * @var $fonts_url	The google web font URL
	 */
	protected $fonts_url = "//fonts.googleapis.com/css?family=";
	
	/**
	 * Constructor for the GoogleTypography class
	 *
	 * Sets up all the appropriate hooks and actions
	 * within the plugin.
	 *
	 * @uses register_uninstall_hook()
	 * @uses is_admin()
	 * @uses add_action()
	 *
	 */	
	function __construct() {
		register_activation_hook(__FILE__, array(&$this, "get_fonts"));
		
		add_action("init", array(&$this,"localization_setup"));
		
		if ( is_admin() ){
			add_action("admin_menu", array(&$this, "admin_menu"));
			add_action("admin_enqueue_scripts", array(&$this, "admin_scripts"));
			add_action("wp_ajax_get_user_fonts", array(&$this,"ajax_get_user_fonts"));
			add_action("wp_ajax_save_user_fonts", array(&$this,"ajax_save_user_fonts"));
			add_action("wp_ajax_reset_user_fonts", array(&$this,"ajax_reset_user_fonts"));
			add_action("wp_ajax_get_google_fonts", array(&$this,"ajax_get_google_fonts"));
			add_action("wp_ajax_get_google_font_variants", array(&$this,"ajax_get_google_font_variants"));
		} else{
			add_action("wp_head", array(&$this,"build_frontend"));
		}

	}
	
	function &init() {
		static $instance = false;

		if ( !$instance ) {
			$instance = new GoogleTypography();
		}

		return $instance;
	}
	

	/**
	 * Initialize plugin for localization
	 *
	 * @uses load_plugin_textdomain()
	 *
	 */
	function localization_setup() {
		load_plugin_textdomain("google-typography", false, dirname(plugin_basename( __FILE__ )) . "/languages/");
	}
	
	/**
	 * Initialize admin menu
	 *
	 * @uses add_submenu_page()
	 * @uses add_filter()
	 * @uses add_action()
	 *
	 */
	function admin_menu() {
		global $plugin_screen;
		
		$plugin_screen = add_submenu_page("themes.php", "Typography", "Typography", "manage_options", "typography", array(&$this, "options_ui"));
		
		add_filter("plugin_action_links", array(&$this, "plugin_link"), 10, 2);
		add_action("load-".$plugin_screen, array(&$this, "help_tab"));
	}
	
	function help_tab() {
		global $plugin_screen;
		
		$screen = get_current_screen();

		if ($screen->id != $plugin_screen)
		    return;
		
		$adding_title        = __("Adding A Collection", "google-typography");
		$adding_content      = "<p>" . __("To add a new font for use on your site. Click the \"Add New\" button on the top left of the page near the \"Google Typography\" title.", "google-typography") . "</p>";
		$adding_content      .= "<p>" . __("Once added, a new font row will appear on the page below. Next you can continue to customize your font (more info in the \"Customizing\" help tab).", "google-typography") . "</p>";
		$adding_content      .= "<p><a href=\"https://vimeo.com/67957799\" target=\"_blank\">" . __("Watch The Video Tutorial &rarr;", "google-typography") . "</a></p>";
		$customizing_title   = __("Customizing", "google-typography");
		$customizing_content = "<p>" . __("Customizing fonts is easy; after adding a new font row you can then customize the following font attributes:", "google-typography") . "</p>";
		$customizing_content .= "<ul><li><b>" . __("Preview Text", "google-typography") . "</b> - " . __("Used for live previewing your changes. This text does not appear anywhere on your website.", "google-typography") . "</li><li><b>Preview Background Color</b> - Allows you to swap between light and dark backgrounds when previewing this font.</li><li><b>" . __("Font Family", "google-typography") . "</b> - " . __("The font family to use for this font. Choose from a real-time list of all available Google Fonts.", "google-typography") . "</li><li><b>" . __("Font Variant", "google-typography") . "</b> - " . __("The variant to use for this font. Note: Each font has it\"s own variant options.", "google-typography") . "</li><li><b>" . __("Font Size", "google-typography") . "</b> - " . __("The size you would like this font to be.", "google-typography") . "</li><li><b>" . __("Font Color", "google-typography") . "</b> - " . __("The color you\"d like to use for this font.", "google-typography") . "</li><li><b>" . __("CSS Selectors", "google-typography") . "</b> - " . __("The HTML tags or CSS selectors you\"d like this font to apply to (more info in the \"CSS Selectors\" help tab). You can specify multiple selectors separated by comma\"s. Ex: h1, #some_id, .some_class", "google-typography") . "</li></ul>";
		$selectors_title     = __("CSS Selectors", "google-typography");
		$selectors_content   = "<p>" . __("CSS Selectors are used to hook your font rows into your actual website. Once you\"ve added, customized, and defined CSS selectors for your fonts, Google Typography will automatically insert all the necessary CSS into your website.", "google-typography") . "</p>";
		$selectors_content   = "<p>" . __("Here are some examples of the selectors you can use:", "google-typography") . "</p>";
		$selectors_content   .= "<ul><li><b>" . __("IDs", "google-typography") . ":</b> " . __("#selector", "google-typography") . "</li><li><b>" . __("Classes:", "google-typography") . ":</b> " . __(".selector", "google-typography") . "</li><li><b>" . __("HTML Tags", "google-typography") . ":</b> " . __("span", "google-typography") . "</ul>";      
		$selectors_content   .= "<p><b>" . __("Example", "google-typography") . ":</b> " . __("#selector span.date", "google-typography") . "</p>";
		
		$screen->add_help_tab(array(
		    "id"	=> "adding",
		    "title"	=> $adding_title,
		    "content"	=> $adding_content
		));
		
		$screen->add_help_tab(array(
		    "id"	=> "customizing",
		    "title"	=> $customizing_title,
		    "content"	=> $customizing_content
		));
		
		$screen->add_help_tab(array(
		    "id"	=> "selectors",
		    "title"	=> $selectors_title,
		    "content"	=> $selectors_content
		));

	}
	
	/**
	 * Initialize plugin options link
	 *
	 */
	function plugin_link($links, $file) {
		if ($file == "google-typography/google-typography.php") {
			$links["settings"] = sprintf("<a href=\"%s\"> %s </a>", admin_url("themes.php?page=typography"), __("Settings", "google-typography"));
		}
		return $links;
	}
	
	/**
	 * Build the frontend CSS to apply to wp_head()
	 *
	 * @uses get_option()
	 * @uses GoogleTypography::stringify_fonts()
	 *
	 */
	function build_frontend() {
		
		$collections = get_option("google_typography_collections");
		
		$import_fonts = array();
		$font_styles = "";

		if($collections) {
		
			foreach($collections as $collection) {

				array_push($import_fonts, array("font_family" => $collection["font_family"], "font_variant" => $collection["font_variant"]));

				if(isset($collection["css_selectors"]) && $collection["css_selectors"] != "") {

					$font_styles .= $collection["css_selectors"] . '{ ';
					$font_styles .= 'font-family: "' . $collection['font_family'] . '";';
					$font_styles .= 'font-weight: ' . $collection['font_variant'] . ';';
					if($collection['font_size']) {
						$font_styles .= 'font-size: ' . $collection['font_size'] . ';';
					}
					if($collection['font_color']) {
						$font_styles .= 'color: ' . $collection['font_color'] . ';';
					}
					$font_styles .= " }\n";
			
				}
			}
			
			if(!empty($import_fonts)) {

				$import_url = '@import url(' . $this->fonts_url . $this->stringify_fonts($import_fonts) .');';
			
				$frontend = "\n<style type=\"text/css\">\n";
				$frontend .= $import_url."\n";
				$frontend .= $font_styles;
				$frontend .= "</style>\n";
			
				echo $frontend;

			}
		
		}
		
	}
	
	/**
	 * Concatenate fonts into a format that Google likes
	 *
	 * @uses array_map()
	 * @uses implode()
	 * @return String of fonts and their associated weights
	 *
	 */
	function stringify_fonts($array) {
		
		$array = array_map('unserialize', array_unique(array_map('serialize', $array)));
		
		$fonts = array();
		
		foreach($array as $font){
			$parts = '';
			
			$parts .= str_replace(" ", "+", $font['font_family']);
			if(isset($font['font_variant'])) {
				$parts .= ':' . $font['font_variant'];
			}
			
			$fonts[] = $parts;
		}
		
		return implode('|', $fonts);
	}
	
	/**
	 * Build the admin settings UI
	 *
	 * @uses GoogleTypography::get_fonts()
	 *
	 */
	function options_ui() {
		
		$title               = __("Google Typography", "google-typography");
		$loading             = __("Loading Your Collections", "google-typography");
		$add_new             = __("Add New", "google-typography");
		$reset               = __("Reset", "google-typography");
		$preview_text        = __("Type in some text to preview...", "google-typography");
		$preview_hint        = __("Preview Background Color", "google-typography");
		$font_family_title   = __("Font family...", "google-typography");
		$font_variant_title  = __("Variant...", "google-typography");
		$font_size_title     = __("Size...", "google-typography");
		$css_selectors_title = __("CSS Selectors (h1, .some_class)", "google-typography");
		$delete_button_text  = __("Delete", "google-typography");
		$save_button_text    = __("Save", "google-typography");
		
		$welcome_title       = __("Welcome to Google Typography", "google-typography");
		$welcome_subtitle    = __("Get started in 3 steps. Not easy enough? ", "google-typography") . "<a href=\"https://vimeo.com/67957799\" target=\"_blank\">" . __("Watch the video tutorial &#x2192;", "google-typography") . "</a>";
		$step_1_title        = __("1. Pick A Font", "google-typography");
		$step_1_desc         = __("Choose from any of the 600+ Google Fonts.", "google-typography");
		$step_2_title        = __("2. Customize It", "google-typography");
		$step_2_desc         = __("Pick a size, variant, color and more.", "google-typography");
		$step_3_title        = __("3. Attach It", "google-typography");
		$step_3_desc         = __("Attach your font to any CSS selector(s).", "google-typography");
		$year                = date("Y");
		
		$fonts = $this->get_fonts();
		$font_families = "";
		foreach ($fonts as $font) {
			$font_family = $font["family"];
			$font_families .= "<option value=\"$font_family\">$font_family</option>";
		}
		
		$numbers = "";
		foreach (range(1, 120) as $number) {
			$numbers .= "<option value=\"{$number}px\">{$number}px</option>";
		}
		
		if(get_option("google_typography_default")) {
			$reset_link = "<a href=\"javascript:;\" class=\"add-new-h2 reset_collections\">" . $reset . "</a>";
		} else { $reset_link = ""; }
		
		echo <<<EOT
			<div id="google_typography" class="wrap">
							
				<div class="icon32" id="icon-themes"><br></div>
				<h2>
					$title
					<a href="javascript:;" class="add-new-h2 new_collection">$add_new</a>
					$reset_link
				</h2>
				
				<div class="loading">
					<span class="spin"></span>
					<h2>$loading</h2>
				</div>
				
				<div class="welcome">
					<div class="help_hint"></div>
					<div class="welcome_title">
						<h1>$welcome_title</h1>
						<p>$welcome_subtitle</p>
					</div>
					<ul class="steps">
						<li class="step_1">
							<h3>$step_1_title</h3>
							<p>$step_1_desc</p>
						</li>
						<li class="step_2">
							<h3>$step_2_title</h3>
							<p>$step_2_desc</p>
						</li>
						<li class="step_3">
							<h3>$step_3_title</h3>
							<p>$step_3_desc</p>
						</li>
					</ul>
				</div>
				
				<div class="template">
				
					<div class="collection">
					
						<div class="font_preview">
							<input type="text" class="preview_text" value="$preview_text" />
							<ul class="preview_color" title="$preview_hint">
								<li><a href="javascript:;" class="light"></a></li>
								<li><a href="javascript:;" class="dark"></a></li>
							</ul>
						</div>
						
						<div class="font_options">
							<div class="left_col">
								<select class="font_family">
									<option value="">$font_family_title</option>
									$font_families
								</select>
								<select class="font_variant">
									<option value="">$font_variant_title</option>
								</select>
								<select class="font_size">
									<option value="">$font_size_title</option>
									$numbers
								</select>
								<input type="text" value="#222222" class="font_color" />
								<input type="text" placeholder="$css_selectors_title" class="css_selectors" />
							</div>
							<div class="right_col">
								<a href="javascript:;" class="delete_collection">$delete_button_text</a>
								<a href="javascript:;" class="button button-primary button-large save_collection">$save_button_text</a>
							</div>
							<div class="clear"></div>
						</div>
					
					</div>
					
				</div>
				
				<div class="collections"></div>
			</div>
EOT;
		
	}
	
	/**
	 * Function for retrieving user font collections
	 *
	 *
	 * @uses get_option()
	 * @uses json_encode()
	 * @return JSON object with all user fonts
	 *
	 */
	function ajax_get_user_fonts() {
		
		$collections = get_option("google_typography_collections");
		
		$retrieved = $collections ? true : false;

		$response = json_encode(array("success" => $retrieved, "collections" => $collections));
		
		header("Content-Type: application/json");
		echo $response;
		
		exit;
		
	}
	
	/**
	 * Function for saving user font collections
	 *
	 *
	 * @uses update_option()
	 * @uses json_encode()
	 * @return JSON object with all user fonts
	 *
	 */
	function ajax_save_user_fonts() {
		
		$collections = $_REQUEST["collections"];
		
		$collections = update_option("google_typography_collections", $collections);
		
		$response = json_encode(array("success" => true, "collections" => $collections));
		
		header("Content-Type: application/json");
		echo $response;
		
		exit;
		
	}
	
	/**
	 * Function for resetting user font collections
	 *
	 *
	 * @uses delete_option()
	 * @uses json_encode()
	 * @return JSON object with all user fonts
	 *
	 */
	function ajax_reset_user_fonts() {
		
		delete_option("google_typography_default");
		delete_option("google_typography_collections");
		
		$response = json_encode(array("success" => true));
		
		header("Content-Type: application/json");
		echo $response;
		
		exit;
		
	}
	
	/**
	 * AJAX function for retrieving fonts from Google
	 *
	 *
	 * @uses GoogleTypography::multidimensional_search()
	 * @uses header()
	 * @return JSON object with font data
	 *
	 */
	function ajax_get_google_fonts() {

		$fonts = $this->get_fonts();
		
		header("Content-Type: application/json");
		echo json_encode($fonts);
		
		exit;
	}
	
	/**
	 * AJAX function for retrieving font variants
	 *
	 *
	 * @uses GoogleTypography::multidimensional_search()
	 * @uses header()
	 * @return JSON object with font data
	 *
	 */
	function ajax_get_google_font_variants() { 

		$fonts = $this->get_fonts();
		$font_family = $_GET["font_family"];
		
		$result = $this->multidimensional_search($fonts, array("family" => $font_family));
		
		header("Content-Type: application/json");
		echo json_encode($result["variants"]);
		
		exit;
	}
	
	/**
	 * Function for retrieving and saving fonts from Google
	 *
	 *
	 * @uses get_transient()
	 * @uses set_transient()
	 * @uses wp_remote_get()
	 * @uses wp_remote_retrieve_body()
	 * @uses json_decode()
	 * @return JSON object with font data
	 *
	 */
	function get_fonts() {
		$fonts = get_transient("google_typography_fonts");	

		if (false === $fonts)	{

			$request = wp_remote_get($this->api_url);

			if(is_wp_error($request)) {

			   $error_message = $request->get_error_message();
			
			   echo "Something went wrong: $error_message";

			} else {
				
				$json = wp_remote_retrieve_body($request);

				$data = json_decode($json, TRUE);

				$items = $data["items"];
				
				$i = 0;
				
				foreach ($items as $item) {
					
					$i++;
					
					$variants = array();
					foreach ($item['variants'] as $variant) {
						if(!stripos($variant, "italic") && $variant != "italic") {
							if($variant == "regular") {
								$variants[] = "normal";
							} else {
								$variants[] = $variant;
							}
						}
					}

					$fonts[] = array("uid" => $i, "family" => $item["family"], "variants" => $variants);

				}
				
				set_transient("google_typography_fonts", $fonts, 60 * 60 * 24);

			}

		}

		return $fonts;
	}
	
	/**
	 * Function for searching array of fonts
	 *
	 *
	 * @return JSON object with font data
	 *
	 */
	function multidimensional_search($parents, $searched) {
	  if(empty($searched) || empty($parents)) {
	    return false;
	  }

	  foreach($parents as $key => $value) {
	    $exists = true;
	    foreach ($searched as $skey => $svalue) {
	      $exists = ($exists && IsSet($parents[$key][$skey]) && $parents[$key][$skey] == $svalue);
	    }
	    if($exists){ return $parents[$key]; }
	  }

	  return false;
	}
	
	/**
	 * Enqueue admin styles and scripts
	 *
	 *
	 * @uses wp_register_script()
	 * @uses wp_enqueue_script()
	 * @uses wp_register_style()
	 * @uses wp_enqueue_style()
	 *
	 */
	function admin_scripts() {

		// Grab the plugin version
		$plugin_data = get_plugin_data(__FILE__);
		
		//Javascripts
		wp_enqueue_script("google-webfont", "https://ajax.googleapis.com/ajax/libs/webfont/1.4.2/webfont.js", false, $plugin_data["Version"], true);
		wp_enqueue_script("google-typography", plugin_dir_url(__FILE__) . "javascripts/google-typography.js", array("jquery", "jquery-ui-sortable", "wp-color-picker"), $plugin_data["Version"], true);
		wp_enqueue_script("chosen", plugin_dir_url(__FILE__) . "javascripts/jquery.chosen.js", array("jquery"), $plugin_data["Version"], true);
		
		// Stylesheets
		wp_enqueue_style("google-typography", plugin_dir_url(__FILE__) . "stylesheets/google-typography.css", array(), $plugin_data["Version"], "screen");
		wp_enqueue_style("chosen", plugin_dir_url(__FILE__) . "stylesheets/chosen.css", array(), $plugin_data["Version"], "screen");
		wp_enqueue_style("google-font", "//fonts.googleapis.com/css?family=Lato:300,400", array(), $plugin_data["Version"], "screen");
		wp_enqueue_style("wp-color-picker");
	}
}


/**
 * Initiate the plugin
 */
if(class_exists("GoogleTypography")) {
    // instantiate the plugin class
    $google_typography = GoogleTypography::init();
}

/**
 * Function for registering default typography collections 
 *
 *
 * @uses get_option()
 * @uses update_option()
 *
 */
function register_typography($collections) {

	if(!get_option("google_typography_default")) {

		$defaults = array();
		delete_option("google_typography_collections");

		foreach($collections as $key => $collection) {
			array_push($defaults, 
				array_merge(
					array("default" => true), 
					$collection
				)
			);
		}
 
		update_option("google_typography_default", true);
		update_option("google_typography_collections", $defaults);

	} 
}
