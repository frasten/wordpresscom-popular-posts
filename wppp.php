<?php
/*
Plugin Name: WordPress.com Popular Posts
Plugin URI: http://polpoinodroidi.netsons.org/wordpress-plugins/wordpresscom-popular-posts/
Description: Shows the most popular posts, using data collected by <a href='http://wordpress.org/extend/plugins/stats/'>WordPress.com stats</a> plugin.
Version: 0.4.0
Author: Frasten
Author URI: http://polpoinodroidi.netsons.org
*/

/*
Created by Frasten (email : frasten@gmail.com) under GPL licence.
* 
*/

/*
$locale = get_locale();
if ( !empty( $locale ) ) {
	$mofile = dirname(__FILE__).'/lang/wppp-'.$locale.'.mo';
	load_textdomain('wppp', $mofile);
}
*/

$WPPP_defaults = array('title' => __('Popular Posts')
	                     ,'number' => '5'
	                     ,'days' => '0'
	);

class WPPP {
	
	function generate_widget() {
		global $WPPP_defaults;
		if (false && !function_exists('stats_get_options') || !function_exists('stats_get_csv'))
			return;
		
		$opzioni = WPPP::get_impostazioni();
		
		$args = func_get_args();
		if (isset($args[0])) {
			$args = $args[0];
			// Called with arguments
			if (!is_array($args))
				$args = wp_parse_args( $args);
			
			foreach ($args as $key => $value) {
				$opzioni[$key] = $value;
			}
		}
			
		// Tags before and after the title (as called by WordPress)
		if ($opzioni['before_title'] || $opzioni['after_title']) {
			$opzioni['title'] = $opzioni['before_title'].$opzioni['title'].$opzioni['after_title'];
		}
		
		
		// Check against malformed values
		$opzioni['days'] = intval($opzioni['days']);
		$opzioni['number'] = intval($opzioni['number']);
		
		if ($opzioni['days'] <= 0)
			$opzioni['days'] = '-1';
		
		$top_posts = stats_get_csv('postviews',"days={$opzioni['days']}&limit={$opzioni['number']}");
		echo $opzioni['title']."\n";
		echo "<ul>\n";
		foreach ($top_posts as $post) {
			echo "<li><a href='{$post['post_permalink']}' title='".htmlentities($post['post_title'],ENT_QUOTES)."'>{$post['post_title']}</a></li>\n";
		}
		echo "</ul>\n";
	}
	
	function init() {
		if (!function_exists('register_sidebar_widget') || !function_exists('register_widget_control'))
			return;
		
		function print_widget($args) {
			extract($args);
			echo $before_widget;
			echo WPPP::generate_widget("before_title=$before_title&after_title=$after_title");
			echo $after_widget;
		}
		register_sidebar_widget(array(__('Popular Posts'), 'widgets'), 'print_widget');
		register_widget_control(array(__('Popular Posts'), 'widgets'), array('WPPP','impostazioni_widget'), 350, 20);
	}
	
	function get_impostazioni() {
		global $WPPP_defaults;
		$opzioni = get_option('widget_wppp');

		$opzioni['title'] = $opzioni['title'] !== NULL ? $opzioni['title'] : $WPPP_defaults['title'];
		$opzioni['number'] = $opzioni['number'] !== NULL ? $opzioni['number'] : $WPPP_defaults['number'];
		$opzioni['days'] = $opzioni['days'] !== NULL ? $opzioni['days'] : $WPPP_defaults['days'];
		return $opzioni;
	}
	
	function impostazioni_widget() {

		$opzioni = WPPP::get_impostazioni();
		
		
		if (isset($_POST['wppp-titolo'])) {
			$opzioni['title'] = strip_tags(stripslashes($_POST['wppp-titolo']));
		}
		if (isset($_POST['wppp-numero-posts'])) {
			$opzioni['number'] = intval($_POST['wppp-numero-posts']);
		}
		if (isset($_POST['wppp-days'])) {
			$opzioni['days'] = intval($_POST['wppp-days']);
		}
		update_option('widget_wppp', $opzioni);
		
		
		$opzioni['title'] = utf8_decode($opzioni['title']);
		
		echo '<p style="text-align:right;"><label for="wppp-titolo">';
		echo __('Title');
		echo ': <input style="width: 180px;" id="wppp-titolo" name="wppp-titolo" type="text" value="'.htmlentities($opzioni['title'],ENT_QUOTES).'" /></label></p>';
		
		echo '<p style="text-align:right;"><label for="wppp-numero-posts">';
		echo __('Number of links shown');
		echo ': <input style="width: 180px;" id="wppp-numero-posts" name="wppp-numero-posts" type="text" value="'.$opzioni['number'].'" /></label></p>';
		
		echo '<p style="text-align:right;"><label for="wppp-days">';
		echo __('The length (in days) of the desired time frame.<br />0 means unlimited.');
		echo ': <input style="width: 180px;" id="wppp-days" name="wppp-days" type="text" value="'.$opzioni['days'].'" /></label></p>';
	}
	
	
}

/* You can call this function if you want to integrate the plugin in a theme
 * that doesn't support widgets.
 * 
 * Just insert this code: 
 * <?php if (function_exists('WPPP_show_popular_posts')) WPPP_show_popular_posts();?>
 * 
 * Optionally you can add these parameters to the function:
 * WPPP_show_popular_posts(title,number,days);
 * 
 * title: Title of the widget
 * number: number of links shown
 * days: length of the time frame of the stats.
 * */
function WPPP_show_popular_posts_old($title = NULL,$number = NULL, $days = NULL) {
	global $WPPP_defaults;
	
	if (!isset($title)) $title = $WPPP_defaults['title'];
	if (!isset($number)) $number = $WPPP_defaults['number'];
	if (!isset($days)) $days = $WPPP_defaults['days'];
	
	WPPP::generate_widget($title,$number,$days);
}

function WPPP_show_popular_posts($user_args = '') {
	global $WPPP_defaults;
	$args = wp_parse_args( $user_args, $WPPP_defaults);
	
	WPPP::generate_widget($args);
}

add_action('widgets_init', array('WPPP', 'init'));

?>
