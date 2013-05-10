<?php
/*
Plugin Name: GitHub Gists Sidebar Widget
Plugin URI: http://andrewnorcross.com/plugins
Description: A sidebar widget to display your public gists from GitHub.
Version: 1.1
Author: norcross
Author URI: http://andrewnorcross.com
*/
/*  Copyright 2012 Andrew Norcross

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License (GPL v2) only.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Load plugin's translated strings
 */
function rkv_load_textdomain() {
	load_plugin_textdomain( 'gist-sidebar-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

add_action( 'plugins_loaded', 'rkv_load_textdomain' );

// add admin CSS for my error message
function rkv_gist_widget_css() {
	echo "
	<style type='text/css'>
	span.gist_error_message {color:#CD0000;padding-top:5px;display:block;text-align:right;font-weight:bold;}
	</style>
	";
}

add_action('admin_head', 'rkv_gist_widget_css');

/**
 * construct widget
 */
class rkv_ListGistsWidget extends WP_Widget {
    /** constructor */
	function rkv_ListGistsWidget() {
		$widget_ops = array( 'classname' => 'list_gists', 'description' => __( 'Displays a list of gists hosted on GitHub', 'gist-sidebar-widget' ) );
		$this->WP_Widget( 'list_gists', __( 'Public GitHub Gists', 'gist-sidebar-widget' ), $widget_ops );
	}

	
    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args, EXTR_SKIP );

	// first check for a username. can't do much without it
	$user	= $instance['github_user'];
	if (empty ($user) ) {
		echo '<p>' . __( 'Please enter a username in the widget settings', 'gist-sidebar-widget' ) . '</p>';
	} else {

		// check for stored transient. if none present, create one
		if( false == get_transient( 'public_github_gists_'.$user.'' ) ) {	
	
			// grab username and total gists to grab
			$user	= $instance['github_user'];
			$number	= $instance['gists_num'];

			// set number of items to return
			if (!empty ($number) ) { $max = $number; } else { $max = 100; } // 100 is the max return in the GitHub API
	
			$request	= new WP_Http;
			$url		= 'https://api.github.com/users/'.urlencode($user).'/gists?&per_page='.$max.'';
			$response	= wp_remote_get ( $url );
	
			// Save a transient to the database
			set_transient('public_github_gists_'.$user.'', $response, 60*60*12 );
	
		} // end transient check


		// set all variable options for plugin call
	
		$user	= $instance['github_user'];
		$number	= $instance['gists_num'];
		$date	= $instance['show_date'];
		$link	= $instance['show_link'];
		$text	= $instance['link_text'];		
	
		// check for transient cache'd result
			$response = get_transient( 'public_github_gists_'.$user.'' );

			// check for bad response from GitHub
			if( is_wp_error( $response ) ) {
				echo '<p>' . __( 'Sorry, there was an error with your request.', 'gist-sidebar-widget' ) . '</p>';
			} else {
				$gist_list	= json_decode( $response['body'] );

		// start output of actual widget
		echo $before_widget;
		
		$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
		echo '<ul>';

		// list individual items
		foreach ( $gist_list as $gist ) {
	
			// get gist values for display
			$desc	= $gist->description;
			$gistid	= $gist->id;
			$url	= $gist->html_url;
	
			// grab date and convert it to a readable format
			$create	= $gist->created_at;
			$create	= strtotime($create);
			$create	= date_i18n(get_option('date_format'), $create);
	
			// check for missing values and replace them if necessary
			( $desc == null) ? $title = __( 'Gist ID:', 'gist-sidebar-widget' ) . ' ' . $gistid : $title = $desc;
			( empty ($text) ) ? $text = __( 'GitHub Profile', 'gist-sidebar-widget' ) : $text = $text;
			
			// display list of gists
				echo '<li class="gist_item">';
				echo '<a class="gist_title" href="'.$url.'" title="'.$title.'" target="_blank">'.$title.'</a>';
				
				// include optional date
				if ($date == 1) :
					echo '<br /><span class="gist_date">';
					printf( __( 'Created: %s', 'gist-sidebar-widget' ), $create );
					echo '</span>';
				endif;

				echo '</li>';
			} // end foreach
		
		echo '</ul>';
		
		// display optional github profile link
		if ($link == 1) : echo '<p class="github_link"><a href="https://github.com/'.$user.'" title="'.$text.'" target="_blank">'.$text.'</a></p>'; endif;

		} // end error check
	
	echo $after_widget;
	} // end username check
	?>

        <?php }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['title']			= strip_tags($new_instance['title']);
		$instance['github_user']	= strip_tags($new_instance['github_user']);
		$instance['gists_num']		= strip_tags($new_instance['gists_num']);
		$instance['link_text']		= strip_tags($new_instance['link_text']);
		$instance['show_date']		= !empty($new_instance['show_date']) ? 1 : 0;	
		$instance['show_link']		= !empty($new_instance['show_link']) ? 1 : 0;	

		// Remove our saved transient (in case we changed something) 
		delete_transient('public_github_gists_'.$user.'');

			return $instance;
		}

    /** @see WP_Widget::form */
    function form($instance) {				
        $instance = wp_parse_args( (array) $instance, array( 
			'title'			=> '',
			'github_user'	=> '',
			'gists_num'		=> '',
			'link_text'		=> __( 'See my GitHub profile', 'gist-sidebar-widget' ),
			'show_date'		=> 0,
			'show_link'		=> 0,
			));
		foreach ( $instance as $field => $val ) {
			if ( isset($new_instance[$field]) )
				$instance[$field] = 1;
		}
		$title			= strip_tags($instance['title']);
		$github_user	= strip_tags($instance['github_user']);
		$gists_num		= strip_tags($instance['gists_num']);
		$link_text		= strip_tags($instance['link_text']);
        ?>
		<p>
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'gist-sidebar-widget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
		<p>
        <label for="<?php echo $this->get_field_id('github_user'); ?>"><?php _e('GitHub username:', 'gist-sidebar-widget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('github_user'); ?>" name="<?php echo $this->get_field_name('github_user'); ?>" type="text" value="<?php echo esc_attr($github_user); ?>" />
        <?php if (empty ($github_user) ) :	echo '<span class="gist_error_message">' . __( 'Username is required!', 'gist-sidebar-widget' ) . '</span>'; endif; ?>
        </p>
        
		<p>
        <label for="<?php echo $this->get_field_id('gists_num'); ?>"><?php _e('Gists to display:', 'gist-sidebar-widget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('gists_num'); ?>" name="<?php echo $this->get_field_name('gists_num'); ?>" type="text" value="<?php echo esc_attr($gists_num); ?>" />
        </p>
        <br />
		<p><strong><?php _e( 'Optional Values', 'gist-sidebar-widget' ); ?></strong></p>
        <p>
        <input class="checkbox" type="checkbox" <?php checked($instance['show_date'], true) ?> id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>" />
		<label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Display creation date', 'gist-sidebar-widget'); ?></label>
        </p>
		<p>
        <input class="checkbox" type="checkbox" <?php checked($instance['show_link'], true) ?> id="<?php echo $this->get_field_id('show_link'); ?>" name="<?php echo $this->get_field_name('show_link'); ?>" />
		<label for="<?php echo $this->get_field_id('show_link'); ?>"><?php _e('Include link to GitHub profile', 'gist-sidebar-widget'); ?></label>
        </p>
		<p>
        <label for="<?php echo $this->get_field_id('link_text'); ?>"><?php _e('Profile link text:', 'gist-sidebar-widget'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('link_text'); ?>" name="<?php echo $this->get_field_name('link_text'); ?>" type="text" value="<?php echo esc_attr($link_text); ?>" />
        </p>
        
		<?php }

} // class 

// register widget
add_action( 'widgets_init', create_function( '', "register_widget('rkv_ListGistsWidget');" ) );