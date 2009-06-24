<?php
/*
Plugin Name: WordPress.com Popular Posts
Plugin URI: http://polpoinodroidi.com/wordpress-plugins/wordpresscom-popular-posts/
Description: Shows the most popular posts, using data collected by <a href='http://wordpress.org/extend/plugins/stats/'>WordPress.com stats</a> plugin.
Version: 2.0.0alpha1
Author: Frasten
Author URI: http://polpoinodroidi.com
*/

/* Created by Frasten (email : frasten@gmail.com) under a GPL licence. */


if ( ! class_exists( 'WP_Widget' ) ) {
	echo "Wordpress.com Popular Post 2.0.0 is only compatible with WordPress >= 2.8.<br />";
	echo "Please either update your Wordpress installation, downgrade this plugin to v1.3.5 or uninstall this plugin.";
	/* TODO: Don't exit, show this in a info box, and return in every function (doing nothing) */
	exit;
}

if ( ! class_exists( 'WPPP' ) ) :
class WPPP extends WP_Widget {
	var $defaults;

	function WPPP() {
		$this->defaults = array('title'   => __( 'Popular Posts', 'wordpresscom-popular-posts' )
	                        ,'number' => '5'
	                        ,'days'   => '0'
	                        ,'show'   => 'both'
	                        ,'format' => "<a href='%post_permalink%' title='%post_title_attribute%'>%post_title%</a>"
	                        ,'excerpt_length' => '100'
	                        ,'title_length' => '0'
													,'cutoff' => '0'
													,'list_tag' => 'ul'
		);
		
		
		$widget_ops = array( 'classname' => 'widget_hello_world',
		                     'description' => __( "A list of your most popular posts", 'wordpresscom-popular-posts' )
												);
    $control_ops = array('width' => 350, 'height' => 300);
    $this->WP_Widget('wppp', __('Popular Posts', 'wordpresscom-popular-posts' ), $widget_ops, $control_ops);
	}
 
	function widget($args, $instance = null) {
		global $wpdb;
		if ( false && !function_exists( 'stats_get_options' ) || !function_exists( 'stats_get_csv' ) )
			return;
		
		extract( $args );
		echo $before_widget;
		
		if ( !$instance ) {
			// Called from static non-widget function. (Or maybe some error? :-P)
			$instance = $this->defaults;
			// Overwrite any user-defined value
			foreach ( $args as $key => $value ) {
				$instance[$key] = $value;
			}
		}
			
		// Tags before and after the title (as called by WordPress)
		if ( $before_title || $after_title ) {
			$instance['title'] = $before_title . $instance['title'] . $after_title;
		}
		
		
		// Check against malformed values
		$instance['days'] = intval( $instance['days'] );
		$instance['number'] = intval( $instance['number'] );
		
		if ( $instance['days'] <= 0 )
			$instance['days'] = '-1';
		
		// A little hackish, but "could" work!
		$howmany = $instance['number'];
		if ( $instance['show'] == 'posts' )
			$howmany *= 2;
		else if ( $instance['show'] == 'pages' )
			$howmany *= 4; // pages are usually less, let's try more!
		
		// If I set some posts to be excluded, I must ask for more data
		$excluded_ids = explode( ',', $instance['exclude'] );
		if ( sizeof ( $excluded_ids ) ) {
			$howmany += sizeof ( $excluded_ids );
		}
		
		
		/* TEMPORARY FIX FOR WP_STATS PLUGIN */
		$reset_cache = false;
		$stats_cache = get_option( 'stats_cache' );
		
		if ( !$stats_cache || !is_array( $stats_cache ) ) {
			$reset_cache = true;
		}
		else {
			foreach ( $stats_cache as $key => $val ) {
				if ( !is_array($val) || !sizeof($val) ) {
					$reset_cache = true;
					break;
				}
				foreach ( $val as $key => $val2 ) {
					if ( !is_array($val2) || !sizeof($val2) ) {
						$reset_cache = true;
						break;
					}
					break;
				}
				break;
			}
		}
		
		if ($reset_cache) {
			update_option( 'stats_cache', "");
		}
		/* END FIX */

		$top_posts = stats_get_csv( 'postviews', "days={$instance['days']}&limit=$howmany" );
		echo $instance['title'] . "\n";
		
		// Check against malicious data
		if ( !in_array( $instance['list_tag'], array( 'ul', 'ol') ) )
			$instance['list_tag'] = $this->defaults['list_tag'];
		echo "<{$instance['list_tag']} class='wppp_list'>\n";
		
		// Cleaning and filtering
		if ( sizeof( $excluded_ids ) ) {
			$temp_list = array();
			foreach ( $top_posts as $p ) {
				// If I have set some posts to be excluded:
				if ( in_array( $p['post_id'], $excluded_ids ) ) continue;
				/* I don't know why, but on some blogs there are "fake" entries,
				   without data. */
				if ( !$p['post_id'] ) continue;
				// Posts with views <= 0 must be excluded
				if ( $p['views'] <= 0 ) continue;
				// If I have set to have a cutoff, exclude the posts with views below that threshold
				if ( $instance['cutoff'] > 0 && $p['views'] < $instance['cutoff'] ) continue;
				
				$temp_list[] = $p;
			}
			$top_posts = $temp_list;
		}
		
		if ( $instance['show'] != 'both') {
			// I want to show only posts or only pages
			$id_list = array();
			foreach ( $top_posts as $p ) {
				$id_list[] = $p['post_id'];
			}

			// If no top-posts, just do nothing gracefully
			if ( sizeof( $id_list ) ) {
				$results = $wpdb->get_results("
				SELECT id FROM {$wpdb->posts} WHERE id IN (" . implode(',', $id_list) . ") AND post_type = '" .
				( $instance['show'] == 'pages' ? 'page' : 'post' ) . "'
				");
				$valid_list = array();
				foreach ( $results as $valid ) {
					$valid_list[] = $valid->id;
				}
				
				$temp_list = array();
				foreach ( $top_posts as $p ) {
					if ( in_array( $p['post_id'], $valid_list ) )
						$temp_list[] = $p;
				}
				$top_posts = $temp_list;
				unset($temp_list);
			} // end if (I have posts)
		} // end if (I chose to show only posts or only pages)
		
		// Limit the number of posts shown following user settings.
		$temp_list = array();
		foreach ( $top_posts as $p ) {
			$temp_list[] = $p;
			if ( sizeof( $temp_list ) >= $instance['number'] )
				break;
		}
		$top_posts = $temp_list;
		
		/* The data from WP-Stats aren't updated, so we must fetch them from the DB */
		// TODO: implement a cache for this data
		if ( sizeof( $top_posts ) ) {
			$id_list = array();
			foreach ( $top_posts as $p ) {
				$id_list[] = $p['post_id'];
			}
			
			// Have to unescape the CSV data, to avoid issues with truncate functions
			for ( $i = 0; $i < sizeof( $top_posts ); $i++ ) {
				$top_posts[$i]['post_title'] = stripslashes( htmlspecialchars_decode( $top_posts[$i]['post_title'] ) );
			}
			
			// Could it be slow?
			// I fetch the updated data from the DB, and overwrite the old values
			$results = $wpdb->get_results("
			SELECT id, post_title FROM {$wpdb->posts} WHERE id IN (" . implode(',', $id_list) . ")
			");
			foreach ( $results as $updated_p ) {
				// I don't use foreach ($var as &$var), it doesn't work in php < 5
				for ( $i = 0; $i < sizeof( $top_posts ); $i++ ) {
					$p = $top_posts[$i];
					if ( $p['post_id'] == $updated_p->id ) {
						$p['post_title'] = $updated_p->post_title;
						$top_posts[$i] = $p;
						break;
					}
				}
			}
		} // end if I have top-posts
		
		foreach ( $top_posts as $post ) {
			echo "\t<li>";

			// Replace format with data
			$replace = array(
				'%post_permalink%'       => get_permalink( $post['post_id'] ),
				'%post_title%'           => esc_html( $this->truncateText( $post['post_title'], $instance['title_length'] ) ),
				'%post_title_attribute%' => esc_attr( $post['post_title'], ENT_QUOTES ),
				'%post_views%'           => number_format_i18n( $post['views'] )
			);
			
			// %post_excerpt% stuff
			if ( strpos( $instance['format'], '%post_excerpt%' ) ) {
				// I get the excerpt for the post only if necessary, to save CPU time.
				$temppost = &get_post( $post['post_id'] );
				
				if ( !empty( $temppost->post_excerpt ) ) {
					/* Excerpt already saved by the user */
					$replace['%post_excerpt%'] = $this->truncateText( $temppost->post_excerpt, $instance['excerpt_length'] );
				}
				else {
					// let's calculate the excerpt:
					$excerpt = strip_tags( $temppost->post_content );
					$excerpt = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $excerpt );
					$excerpt = $this->truncateText( $excerpt, $instance['excerpt_length'] );
					$replace['%post_excerpt%'] = $excerpt;
				}
				unset( $temppost );
			}
			
			echo strtr( $instance['format'], $replace );
			
			echo "</li>\n";
		}
		echo "</{$instance['list_tag']}>\n";
		echo $after_widget;
	}
 
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = intval( $new_instance['number'] );
		$instance['days'] = intval( $new_instance['days'] );
		$instance['format'] = $new_instance['format'];
		$instance['show'] = in_array( $new_instance['show'], array( 'both', 'posts', 'pages' ) ) ?
			$new_instance['show'] :
			$this->defaults['show'];
		$instance['excerpt_length'] = intval( $new_instance['excerpt_length'] );
		$instance['title_length'] = intval( $new_instance['title_length'] );
		// I want only digits or commas for this:
		$instance['exclude'] = preg_replace( '/[^0-9,]/', '', $new_instance['exclude'] );
		$instance['cutoff'] = abs( intval( $new_instance['cutoff'] ) );
		$instance['list_tag'] = in_array( $new_instance['list_tag'], array( 'ul', 'ol') ) ?
			$new_instance['list_tag'] :
			$this->defaults['list_tag'];
		
 		$instance['initted'] = 1;
		
		return $instance;
	}
 
	function form( $instance ) {
		// Set the settings that are still undefined
		$old_instance = $instance;
		$instance = $this->defaults;
		foreach ( $old_instance as $key => $value ) {
			$instance[$key] = $value;
		}
		
		
		if ( !$instance['initted'] ) {
			// Import eventual old settings (from WPPP < 2.0.0)
			$settings = get_option( 'widget_wppp' );
			foreach ( $settings as $wdgt ) {
				if ( is_array( $wdgt ) && ! $item['initted'] ) {
					// These are the old WPPP settings
					foreach ( $wdgt as $key => $value ) {
						$instance[$key] = $value;
					}
					break;
				}
			}
			unset( $settings );
		}
		
		
		$field_id = $this->get_field_id( 'title' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Title', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 180px;' id='$field_id' name='" .
			$this->get_field_name( 'title' ) . "' type='text' value='" .
			esc_attr( $instance['title'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id( 'number' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Number of links shown', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 30px;' id='$field_id' name='" .
			$this->get_field_name( 'number' ) . "' type='text' value='" .
			intval( $instance['number'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id( 'days' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'The length (in days) of the desired time frame.<br />(0 means unlimited)', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 40px;' id='$field_id' name='" .
			$this->get_field_name( 'days' ) . "' type='text' value='" .
			intval( $instance['days'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id( 'show' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Show: ', 'wordpresscom-popular-posts' );
		$opt = array(
			'both'  => __( 'posts and pages', 'wordpresscom-popular-posts' ),
			'posts' => __( 'only posts', 'wordpresscom-popular-posts' ),
			'pages' => __( 'only pages', 'wordpresscom-popular-posts' )
		);
		if ( !$instance['show'] )
			$instance['show'] = $this->defaults['show'];
		echo "<select name='" . $this->get_field_name( 'show' ) . "' id='$field_id'>\n";
		foreach ( $opt as $key => $value ) {
			$sel = ( $instance['show'] == $key ) ? ' selected="selected"' : '';
			echo "<option value='$key'$sel>$value</option>\n";
		}
		echo '</select></label></p>';
		
		$field_id = $this->get_field_id( 'format' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Format of the links. See <a href="http://polpoinodroidi.com/wordpress-plugins/wordpresscom-popular-posts/">docs</a> for help', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 300px;' id='$field_id' name='" .
			$this->get_field_name( 'format' ) . "' type='text' value='" .
			esc_attr( $instance['format'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id( 'excerpt_length' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Length of the excerpt (if %post_excerpt% is used in the format above)', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 40px;' id='$field_id' name='" .
			$this->get_field_name( 'excerpt_length' ) . "' type='text' value='" .
			intval( $instance['excerpt_length'] ) . "' />" . __(' characters') . "</label></p>";
		
		$field_id = $this->get_field_id( 'title_length' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Max length of the title links.<br />(0 means unlimited)', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 30px;' id='$field_id' name='" .
			$this->get_field_name( 'title_length' ) . "' type='text' value='" .
			intval( $instance['title_length'] ) . "' />" . __(' characters') . "</label></p>";
		
		$field_id = $this->get_field_id( 'exclude' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Exclude these posts: (separate the IDs by commas. e.g. 1,42,52)', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 180px;' id='$field_id' name='" .
			$this->get_field_name( 'exclude' ) . "' type='text' value='" .
			esc_attr( $instance['exclude'] ) . "' /></label></p>";
			
		$field_id = $this->get_field_id( 'cutoff' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Don\'t show posts/pages with a view count under', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 50px;' id='$field_id' name='" .
			$this->get_field_name( 'cutoff' ) . "' type='text' value='" .
			intval( $instance['cutoff'] ) . "' /></label>" . __('(0 means unlimited)', 'wordpresscom-popular-posts' ) . '</p>';
		
		$field_id = $this->get_field_id( 'list_tag' );
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Kind of list', 'wordpresscom-popular-posts' );
		$opt = array(
			'ul'  => __( 'Unordered list (&lt;ul&gt;)', 'wordpresscom-popular-posts' ),
			'ol'  => __( 'Ordered list (&lt;ol&gt;)', 'wordpresscom-popular-posts' )
		);
		if ( !$instance['show'] )
			$instance['show'] = $this->defaults['list_tag'];
		echo ": <select name='" . $this->get_field_name( 'list_tag' ) . "' id='$field_id'>\n";
		foreach ( $opt as $key => $value ) {
			$sel = ( $instance['list_tag'] == $key ) ? ' selected="selected"' : '';
			echo "<option value='$key'$sel>$value</option>\n";
		}
		echo '</select></label></p>';
	}
	
	function truncateText( $text, $chars = 50 ) {
		if ( strlen($text) <= $chars || $chars <= 0 )
			return $text;
		$new = wordwrap( $text, $chars, "|" );
		$newtext = explode( "|", $new );
		return $newtext[0] . "...";
	}
}
endif;

/* You can call this function if you want to integrate the plugin in a theme
 * that doesn't support widgets.
 * 
 * Just insert this code: 
 * <?php if ( function_exists( 'WPPP_show_popular_posts' ) ) WPPP_show_popular_posts();?>
 * 
 * Optionally you can add some parameters to the function, in this format:
 * name=value&name=value etc.
 * 
 * Possible names are:
 * - title (title of the widget, you can add tags (e.g. <h3>Popular Posts</h3>) default: Popular Posts)
 * - number (number of links shown, default: 5)
 * - days (length of the time frame of the stats, default 0, i.e. infinite)
 * - show (both, posts, pages, default both)
 * - format (the format of the links shown, default: <a href='%post_permalink%' title='%post_title%'>%post_title%</a>)
 * - excerpt_length (the length of the excerpt, if %post_excerpt% is used in the format)
 * - title_length (the length of the title links, default 0, i.e. unlimited)
 * - exclude (the list of post/page IDs to exclude, separated by commas)
 * - cutoff (don't show posts/pages with a view count under this number)
 * 
 * Example: if you want to show the widget without any title, the 3 most viewed
 * articles, in the last week, and in this format: My Article (123 views)
 * you will use this:
 * WPPP_show_popular_posts( "title=&number=3&days=7&format=<a href='%post_permalink%' title='%post_title_attribute%'>%post_title% (%post_views% views)</a>" );
 * 
 * You don't have to fill every field, you can insert only the values you
 * want to change from default values.
 * 
 * You can use these special markers in the `format` value:
 * %post_permalink% the link to the post
 * %post_title% the title the post
 * %post_title_attribute% the title of the post; use this in attributes, e.g. <a title='%post_title_attribute%'
 * %post_views% number of views
 * %post_excerpt% the first n characters of the content. Set n with excerpt_length.
 * 
 * */
function WPPP_show_popular_posts( $user_args = '' ) {
	$wppp = new WPPP();
	
	$args = wp_parse_args( $user_args, $wppp->defaults );
	// remove slashes in format. TODO: still necessary?
	if ( isset( $args['format'] ) ) {
		$args['format'] = stripslashes( $args['format'] );
	}	
	
	$wppp->widget( $args );
}




// Language loading
load_textdomain( 'wordpresscom-popular-posts', dirname(__FILE__) . "/language/wordpresscom-popular-posts-" . get_locale() . ".mo" );


add_action('widgets_init', create_function('', 'return register_widget("WPPP");'));

// TODO: function for non widget-ready themes
?>
