<?php
/*
Plugin Name: WordPress.com Popular Posts
Plugin URI: http://polpoinodroidi.netsons.org/wordpress-plugins/wordpresscom-popular-posts/
Description: Shows the most popular posts, using data collected by <a href='http://wordpress.org/extend/plugins/stats/'>WordPress.com stats</a> plugin.
Version: 0.2.1
Author: Frasten
Author URI: http://polpoinodroidi.netsons.org
*/

/*
Created by Frasten (email : frasten@gmail.com) under GPL licence.
*/

/*
$locale = get_locale();
if ( !empty( $locale ) ) {
	$mofile = dirname(__FILE__).'/lang/wppp-'.$locale.'.mo';
	load_textdomain('wppp', $mofile);
}
*/

$WPPP_defaults = array('title' => __('Popular Posts')
	                     ,'numero_posts' => '5'
	);

class WPPP {
	
	function genera_widget() {
		if (!function_exists('stats_get_options') || !function_exists('stats_get_csv'))
			return;
		$opzioni = WPPP::get_impostazioni();
		
		$top_posts = stats_get_csv('postviews',"days=false&limit={$opzioni['numero_posts']}");
		echo "<h4>{$opzioni['title']}</h4>\n";
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
			?>
			<?php echo $before_widget; ?>
			<?php echo WPPP::genera_widget(); ?>
			<?php echo $after_widget; ?>
	<?php
		}
		register_sidebar_widget(array(__('Popular Posts'), 'widgets'), 'print_widget');
		register_widget_control(array(__('Popular Posts'), 'widgets'), array('WPPP','impostazioni_widget'), 350, 20);
	}
	
	function get_impostazioni() {
		global $WPPP_defaults;
		$opzioni = get_option('widget_wppp');

		$opzioni['title'] = $opzioni['title'] !== NULL ? $opzioni['title'] : $WPPP_defaults['title'];
		$opzioni['numero_posts'] = $opzioni['numero_posts'] !== NULL ? $opzioni['numero_posts'] : $WPPP_defaults['numero_posts'];
		return $opzioni;
	}
	
	function impostazioni_widget() {

		$opzioni = WPPP::get_impostazioni();
		
		if (isset($_POST['wppp-titolo'])) {
			$opzioni['title'] = strip_tags(stripslashes($_POST['wppp-titolo']));
			update_option('widget_wppp', $opzioni);
		}
		if (isset($_POST['wppp-numero-posts'])) {
			$opzioni['numero_posts'] = strip_tags(stripslashes($_POST['wppp-numero-posts']));
			update_option('widget_wppp', $opzioni);
		}
				
		echo '<p style="text-align:right;"><label for="wppp-titolo">';
		echo __('Title');
		echo ': <input style="width: 180px;" id="wppp-titolo" name="wppp-titolo" type="text" value="'.htmlentities($opzioni['title'],ENT_QUOTES).'" /></label></p>';
		echo '<p style="text-align:right;"><label for="wppp-numero-posts">';
		echo __('Number of posts');
		echo ': <input style="width: 180px;" id="wppp-numero-posts" name="wppp-numero-posts" type="text" value="'.$opzioni['numero_posts'].'" /></label></p>';
	}
	
	
}

add_action('widgets_init', array('WPPP', 'init'));


?>
