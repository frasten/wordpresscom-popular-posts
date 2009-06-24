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
		);
		
		
		$widget_ops = array( 'classname' => 'widget_hello_world',
		                     'description' => __( "A list of your most popular posts", 'wordpresscom-popular-posts' )
												);
    $control_ops = array('width' => 350, 'height' => 300);
    $this->WP_Widget('wppp', __('Popular Posts', 'wordpresscom-popular-posts' ), $widget_ops, $control_ops);
	}
 
	function widget($args, $instance) {
		global $wpdb;
		if ( false && !function_exists( 'stats_get_options' ) || !function_exists( 'stats_get_csv' ) )
			return;
		
		extract( $args );
		echo $before_widget;
		// $instance = WPPP::get_impostazioni();
		
		/*$args = func_get_args();
		if ( isset( $args[0] ) ) {
			$args = $args[0];
			// Called with arguments
			if ( !is_array( $args ) )
				$args = wp_parse_args( $args );
			
			foreach ( $args as $key => $value ) {
				$instance[$key] = $value;
			}
		}*/
			
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
		echo "<ul class='wppp_list'>\n";
		
		if ( $instance['show'] != 'both') {
			// I want to show only posts or only pages
			$id_list = array();
			foreach ( $top_posts as $p ) {
				/* I don't know why, but on some blogs there are "fake" entries,
				   without data. */
				if ($p['post_id'])
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
					if ( sizeof( $temp_list ) >= $instance['number'] )
						break;
				}
				$top_posts = $temp_list;
				unset($temp_list);
			} // end if (I have posts)
		} // end if (I chose to show only posts or only pages)
		
		/* The data from WP-Stats aren't updated, so we must fetch them from the DB */
		// TODO: implement a cache for this data
		if ( sizeof( $top_posts ) ) {
			$id_list = array();
			foreach ( $top_posts as $p ) {
				/* I don't know why, but on some blogs there are "fake" entries,
				   without data.
					 Posts with 0 views must be excluded too. */
				if ( $p['post_id'] && $p['views'] > 0 )
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
				'%post_title%'           => esc_html( WPPP::truncateText( $post['post_title'], $instance['title_length'] ) ),
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
		echo "</ul>\n";
		echo $after_widget;
	}
 
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = intval( $new_instance['number'] );
		$instance['days'] = intval( $new_instance['days'] );
		$instance['format'] = $new_instance['format']; // TODO: sanitize this
		$instance['show'] = in_array( $new_instance['show'], array( 'both', 'posts', 'pages' ) ) ?
			$new_instance['show'] :
			'both';
		$instance['excerpt_length'] = intval( $new_instance['excerpt_length'] );
		$instance['title_length'] = intval( $new_instance['title_length'] );
 		$instance['initted'] = 1;
		
		return $instance;
	}
 
	function form( $instance ) {
		if ( !$instance['initted'] ) {
			// Initial default settings
			foreach ( $this->defaults as $key => $value ) {
				$instance[$key] = $value;
			}
		}
		
		
		$field_id = $this->get_field_id('title');
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Title', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 180px;' id='$field_id' name='" .
			$this->get_field_name('title') . "' type='text' value='" .
			esc_attr( $instance['title'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id('number');
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Number of links shown', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 180px;' id='$field_id' name='" .
			$this->get_field_name('number') . "' type='text' value='" .
			intval( $instance['number'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id('days');
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'The length (in days) of the desired time frame.<br />0 means unlimited', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 180px;' id='$field_id' name='" .
			$this->get_field_name('days') . "' type='text' value='" .
			intval( $instance['days'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id('show');
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Show: ', 'wordpresscom-popular-posts' );
		$opt = array(
			'both'  => __( 'posts and pages', 'wordpresscom-popular-posts' ),
			'posts' => __( 'only posts', 'wordpresscom-popular-posts' ),
			'pages' => __( 'only pages', 'wordpresscom-popular-posts' )
		);
		if ( !$instance['show'] )
			$instance['show'] = $this->defaults['show'];
		echo "<select name='" . $this->get_field_name('show') . "' id='$field_id'>\n";
		foreach ( $opt as $key => $value ) {
			$sel = ( $instance['show'] == $key ) ? ' selected="selected"' : '';
			echo "<option value='$key'$sel>$value</option>\n";
		}
		echo '</select></label></p>';
		
		$field_id = $this->get_field_id('format');
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Format of the links. See <a href="http://polpoinodroidi.com/wordpress-plugins/wordpresscom-popular-posts/">docs</a> for help', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 300px;' id='$field_id' name='" .
			$this->get_field_name('format') . "' type='text' value='" .
			esc_attr( $instance['format'] ) . "' /></label></p>";
		
		$field_id = $this->get_field_id('excerpt_length');
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Length of the excerpt (if %post_excerpt% is used in the format above)', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 100px;' id='$field_id' name='" .
			$this->get_field_name('excerpt_length') . "' type='text' value='" .
			intval( $instance['excerpt_length'] ) . "' />" . __(' characters') . "</label></p>";
		
		$field_id = $this->get_field_id('title_length');
		echo "<p style='text-align:right;'><label for='$field_id'>";
		echo __( 'Max length of the title links.<br />(0 means unlimited)', 'wordpresscom-popular-posts' );
		echo ": <input style='width: 100px;' id='$field_id' name='" .
			$this->get_field_name('title_length') . "' type='text' value='" .
			intval( $instance['title_length'] ) . "' />" . __(' characters') . "</label></p>";
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



add_action('widgets_init', create_function('', 'return register_widget("WPPP");'));

//register_widget('WPPP_multi');
// TODO: function for non widget-ready themes
?>
