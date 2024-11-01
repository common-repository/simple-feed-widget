<?php
/* 
* Plugin Name: Simple Feed Widget
* Description: This pLugin is used for tweeter feed widget, it's automatically croll your twitter account feed and show on the your website, you can put this widget on sidebar and footer section.
* Version:     1.1.0
* Author: thehtmlcoder
* Author URI: thehtmlcoder.net
* License:     GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: simple-feed-widget
*/

define( "STFW_VERSION", '1.1.0' );

// Check if class already exist
if ( !class_exists( 'Simple_Twitter_Widget' ) ) {
	class Simple_Twitter_Widget extends WP_Widget {

		/**
		 * Default instance.
		 */
		protected $default_instance;

		/**
		 * Form Options
		 */
		protected $twitter_timeline_type;
		protected $twitter_widget_theme;

		/**
		 * Sets up a new widget instance.
		 */
		public function __construct() {
			// Initialize Default Instance
			$this->default_instance = array(
				'title'                      => esc_html__( 'Twitter Feed', 'simple-feed-widget' ),
				'twitter_widget_username'    => 'Wordpress',
				'oauth_access_token'         => '',
				'oauth_access_token_secret'  => '',
				'consumer_key'               => '',
				'consumer_secret'            => '',
				'twitter_widget_tweet_limit' => '2',
			);

			// Initialize Form Options
			// Widget Options
			$widget_ops = array(
				'classname'                   => 'twitter-feed-widget',
				'description'                 => esc_html__( 'Display an official Twitter Embedded Timeline widget.', 'simple-feed-widget' ),
				'customize_selective_refresh' => true,
			);

			// Constructor
			parent::__construct(
				'simple-feed-widget', // ID
				apply_filters( 'do_etfw_widget_name', esc_html__( 'Simple Twitter Feeds Widget', 'simple-feed-widget' ) ),
				$widget_ops
			);
			// Scripts
		}
		
		
		public function viwpstw_sanitize_links($tweet) {
			if(isset($tweet->retweeted_status)) {
				$rt_section = current(explode(":", $tweet->text));
				$text = $rt_section.": ";
				$text .= $tweet->retweeted_status->text;
			} else {
				$text = $tweet->text;
			}
			$text = preg_replace('/((http)+(s)?:\/\/[^<>\s]+)/i', '<a href="$0" target="_blank" rel="nofollow">$0</a>', $text );
			$text = preg_replace('/[@]+([A-Za-z0-9-_]+)/', '<a href="https://twitter.com/$1" target="_blank" rel="nofollow">@$1</a>', $text );
			$text = preg_replace('/[#]+([A-Za-z0-9-_]+)/', '<a href="https://twitter.com/search?q=%23$1" target="_blank" rel="nofollow">$0</a>', $text );
			return $text;

		}

		/**
		 * Outputs the content for the current widget instance.
		 *
		 * @param array $args Display arguments including 'before_title', 'after_title',
		 *                        'before_widget', and 'after_widget'.
		 * @param array $instance Settings for the current Custom HTML widget instance.
		 */
		public function widget( $args, $instance ) {
			// Merge the instance arguments with the defaults.
			$instance = wp_parse_args( (array) $instance, $this->default_instance );

			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

			// Open the output of the widget.
			echo $args['before_widget'];

			if ( ! empty( $title ) ) :
				echo $args['before_title'] . $title . $args['after_title'];
			endif;

			$accessToken        = $instance['oauth_access_token'];
			$accessTokenSecret = $instance['oauth_access_token_secret'];
			$consumerKey              = $instance['consumer_key'];
			$consumerSecret           = $instance['consumer_secret'];
			$replies_excl 			= isset( $instance['replies_excl'] ) ? $instance['replies_excl'] : false; 
			$tweets_count = $instance['twitter_widget_tweet_limit'];
			$name = $instance['twitter_widget_username'];

			// we are going to use "user_timeline"		

			/** Close the output of the widget. */
 
			include( plugin_dir_path( __FILE__ ) . 'twitteroauth/twitteroauth.php');
			
			$api_call = new viwptf_TwitterOAuth(
							$consumerKey,   		
							$consumerSecret,   
							$accessToken,   	
							$accessTokenSecret
						);
			$totalToFetch = ($replies_excl) ? max(50, $tweets_count * 3) : $tweets_count;				
			
			$fetchedTweets = $api_call->get(
				'statuses/user_timeline',
				array(
					'screen_name'     => $name,
					'count'           => $totalToFetch,
					'exclude_replies' => $replies_excl
				)
			);
			
			
			$limitToDisplay = min($tweets_count, count($fetchedTweets));
			
				for($i = 0; $i < $limitToDisplay; $i++) :
			 		$tweet = $fetchedTweets[$i];			  
			    	$screen_name = $tweet->user->screen_name;
			    	$permalink = 'https://twitter.com/'. $name .'/status/'. $tweet->id_str;
			    	$tweet_id = $tweet->id_str;
			    	$image = $tweet->user->profile_image_url;
					$text = $this->viwpstw_sanitize_links($tweet);
			    	$time = $tweet->created_at;
			    	$time = date_parse($time);
			    	$uTime = mktime($time['hour'], $time['minute'], $time['second'], $time['month'], $time['day'], $time['year']);
			    	$tweets[] = array(
			    		'text' => $text,
			    		'scr_name'=>$screen_name,
			    		'favourite_count'=>$tweet->favorite_count,
			    		'retweet_count'=>$tweet->retweet_count,
			    		'name' => $name,
			    		'permalink' => $permalink,
			    		'image' => $image,
			    		'time' => $uTime,
			    		'tweet_id' => $tweet_id
			    		);			     
				endfor;
				
				
				
			echo '<div class="twitter-feed-widget">';
			// show tweets
				echo '<ul>';
				if ( ! empty( $screen_name ) ) {
					foreach ( $tweets as $tweet ) {
						echo '<li>';
						// show tweet content
						echo '<i class="easepress-icontwitter"></i>';
						echo '<div class="tweet-content">';
						echo "<a href='https://twitter.com/{$screen_name}'><p class='tweet-title'>@{$screen_name}</p></a>";

						// get tweet text
						$tweet_text = $tweet['text'];					 
						
						// output
						echo wp_kses_post( $tweet_text );
						echo '</div>';
						echo '</li>';
					}
				}
				echo '</ul>';
			echo '</div>';
			
			echo $args['after_widget'];
		}

		/**
		 * Handles updating settings for the current widget instance.
		 *
		 * @param array $new_instance New settings for this instance as input by the user via
		 *                            WP_Widget::form().
		 * @param array $old_instance Old settings for this instance.
		 *
		 * @return array Settings to save or bool false to cancel saving.
		 */
		public function update( $new_instance, $old_instance ) {
			// Instance
			$instance = $old_instance;

			// Sanitization
			$instance['title']                      = sanitize_text_field( $new_instance['title'] );
			$instance['twitter_widget_username']    = sanitize_text_field( $new_instance['twitter_widget_username'] );
			$instance['oauth_access_token']         = sanitize_text_field( $new_instance['oauth_access_token'] );
			$instance['oauth_access_token_secret']  = sanitize_text_field( $new_instance['oauth_access_token_secret'] );
			$instance['consumer_key']               = sanitize_text_field( $new_instance['consumer_key'] );
			$instance['consumer_secret']            = sanitize_text_field( $new_instance['consumer_secret'] );
			$twitter_widget_tweet_limit             = absint( $new_instance['twitter_widget_tweet_limit'] );
			$instance['twitter_widget_tweet_limit'] = ( $twitter_widget_tweet_limit ? $twitter_widget_tweet_limit : null );

			return $instance;
		}

		/**
		 * Outputs the widget settings form.
		 *
		 * @param array $instance Current instance.
		 *
		 * @returns void
		 */
		public function form( $instance ) {
			// Merge the instance arguments with the defaults.
			$instance = wp_parse_args( (array) $instance, $this->default_instance );
			?>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'simple-feed-widget' ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
					   value="<?php echo esc_attr( $instance['title'] ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'twitter_widget_username' ) ); ?>"><?php esc_html_e( 'Twitter Username:', 'simple-feed-widget' ); ?></label>
				<input type="text" class="widefat"
					   id="<?php echo esc_attr( $this->get_field_id( 'twitter_widget_username' ) ); ?>"
					   name="<?php echo esc_attr( $this->get_field_name( 'twitter_widget_username' ) ); ?>"
					   value="<?php echo esc_attr( $instance['twitter_widget_username'] ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'oauth_access_token' ) ); ?>"><?php esc_html_e( 'Oauth Access Token:', 'simple-feed-widget' ); ?></label>
				<input type="text" class="widefat"
					   id="<?php echo esc_attr( $this->get_field_id( 'oauth_access_token' ) ); ?>"
					   name="<?php echo esc_attr( $this->get_field_name( 'oauth_access_token' ) ); ?>"
					   value="<?php echo esc_attr( $instance['oauth_access_token'] ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'oauth_access_token_secret' ) ); ?>"><?php esc_html_e( 'Oauth Access Token Secret:', 'simple-feed-widget' ); ?></label>
				<input type="text" class="widefat"
					   id="<?php echo esc_attr( $this->get_field_id( 'oauth_access_token_secret' ) ); ?>"
					   name="<?php echo esc_attr( $this->get_field_name( 'oauth_access_token_secret' ) ); ?>"
					   value="<?php echo esc_attr( $instance['oauth_access_token_secret'] ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'consumer_key' ) ); ?>"><?php esc_html_e( 'Consumer Key:', 'simple-feed-widget' ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'consumer_key' ) ); ?>"
					   name="<?php echo esc_attr( $this->get_field_name( 'consumer_key' ) ); ?>"
					   value="<?php echo esc_attr( $instance['consumer_key'] ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'consumer_secret' ) ); ?>"><?php esc_html_e( 'Consumer Secret:', 'simple-feed-widget' ); ?></label>
				<input type="text" min="1" max="20" class="widefat"
					   id="<?php echo esc_attr( $this->get_field_id( 'consumer_secret' ) ); ?>"
					   name="<?php echo esc_attr( $this->get_field_name( 'consumer_secret' ) ); ?>"
					   value="<?php echo esc_attr( $instance['consumer_secret'] ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'twitter_widget_tweet_limit' ) ); ?>"><?php esc_html_e( 'Tweet Limit', 'simple-feed-widget' ); ?></label>
				<input type="number" min="1" max="20" class="widefat"
					   id="<?php echo esc_attr( $this->get_field_id( 'twitter_widget_tweet_limit' ) ); ?>"
					   name="<?php echo esc_attr( $this->get_field_name( 'twitter_widget_tweet_limit' ) ); ?>"
					   value="<?php echo esc_attr( $instance['twitter_widget_tweet_limit'] ); ?>"/>
			</p>
			<?php
		}

	}
}

function simple_twitter_feed_register(){

	register_widget( 'Simple_Twitter_Widget' );

}

add_action( 'widgets_init', 'simple_twitter_feed_register' );