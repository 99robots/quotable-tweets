<?php
/**
 * Plugin Name: Quotable Tweets
 * Plugin URI: https://draftpress.com/products
 * Description: The Quotable Tweets plugin gives you an easy way to add a
 * beautiful actionable tweet link to your sidebar.
 * Version: 1.1.8
 * Author: DraftPress
 * Author URI: https://draftpress.com
 * License: GPL2
 * php version: 7.0
 *
 * @category Plugin
 * @package  QuotableTweets
 * @author   DraftPress <support@draftpress.com>
 * @license  GNU General Public License 2
 * (https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
 * @link     https://draftpress.com/products
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

/**
 * Global Definitions
 */

// Plugin Name.
if ( ! defined( 'NNROBOTS_QUOTABLE_TWEETS_PLUGIN_NAME' ) ) {
	define(
		'NNROBOTS_QUOTABLE_TWEETS_PLUGIN_NAME',
		trim( dirname( plugin_basename( __FILE__ ) ), '/' )
	);
}

// Plugin directory.
if ( ! defined( 'NNROBOTS_QUOTABLE_TWEETS_PLUGIN_DIR' ) ) {
	define(
		'NNROBOTS_QUOTABLE_TWEETS_PLUGIN_DIR',
		WP_PLUGIN_DIR . '/' . NNROBOTS_QUOTABLE_TWEETS_PLUGIN_NAME
	);
}

// Hooks / Filters.
add_action( 'init', array( 'NNRobots_Quotable_Tweets', 'loadTextDomain' ) );
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	array(
		'NNRobots_Quotable_Tweets',
		'settingsLink',
	)
);

// Register the Widget.
add_action(
	'widgets_init',
	function () {
		register_widget( 'NNRobots_Quotable_Tweets' );
	}
);

/**
 * NNRobots_Quotable_Tweets main class
 *
 * @category Class
 * @package  QuotableTweets
 * @author   DraftPress <support@draftpress.com>
 * @license  GNU General Public License 2
 * (https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
 * @link     https://draftpress.com/products
 * @since    1.0.0
 * @using    WordPress 3.8
 */
class NNRobots_Quotable_Tweets extends WP_Widget {





	/**
	 * Prefix
	 *
	 * (default value: 'nnrobots_quotable_tweets_')
	 *
	 * @var    string
	 * @access public
	 * @static
	 */

	public static $prefix = 'nnrobots_quotable_tweets_';

	/**
	 * Base ID.
	 *
	 * (default value: 'nnrobots_quotable_tweets')
	 *
	 * @var    string
	 * @access public
	 * @static
	 */
	public static $base_id = 'nnrobots_quotable_tweets';

	/**
	 * Prefix Dash
	 *
	 * (default value: 'nnrobots_related_posts_')
	 *
	 * @var    string
	 * @access private
	 * @static
	 */
	private static $prefix_dash = 'nnr-qt-';

	/**
	 * Load the text domain
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function loadTextDomain() {
		$locale = apply_filters(
			'plugin_locale',
			get_locale(),
			'quotable-tweets'
		);

		load_textdomain(
			'quotable-tweets',
			WP_LANG_DIR . '/quotable-tweets/quotable-tweets-' . $locale . '.mo'
		);

		load_plugin_textdomain(
			'quotable-tweets',
			false,
			NNROBOTS_QUOTABLE_TWEETS_PLUGIN_DIR . '/languages/'
		);
	}

	/**
	 * Hooks to 'plugin_action_links_' filter.
	 *
	 * @param array $links An array of plugin action links.
	 *
	 * @since 1.0.0
	 *
	 * @return array The modified array of plugin action links.
	 */
	public static function settingsLink( $links ) {
		$widget_link = '<a href="widgets.php">Widget</a>';
		array_unshift( $links, $widget_link );

		return $links;
	}

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'quotable_tweets',
			esc_html__( 'Quotable Tweets', 'quotable-tweets' ),
			array(
				'description' => esc_html__(
					'Beautiful way to display an actionable tweet.',
					'quotable-tweets'
				),
			)
		);
	}

	/**
	 * Front-end display of the widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from the database.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @return true
	 */
	public function widget( $args, $instance ) {
		// Do not display if not a single post.
		if ( ! is_singular() ) {
			return false;
		}

		global $post;

		echo esc_attr( $args['before_widget'] );

		// Title.
		if ( empty( $instance['title'] ) ) {
			$instance['title'] = esc_html__(
				'Share this article!',
				'quotable-tweets'
			);
		}

		// Bitly access token.
		$post_link = wp_get_shortlink( $post->ID );
		if ( ! isset( $post_link ) || empty( $post_link ) || ! $post_link ) {
			$post_link = get_permalink( $post->ID );
		}

		if ( ! empty( $instance['bitly_access_token'] ) ) {
			$instance['title'] = esc_html__(
				'Share this article!',
				'quotable-tweets'
			);

			// Construct the URL with parameters and use rawurlencode.
			$bitly_url = 'https://api-ssl.bitly.com/v3/shorten?'
			. http_build_query(
				array(
					'access_token' => $instance['bitly_access_token'],
					'longUrl'      => rawurlencode( $post_link ),
					'format'       => 'json',
				)
			);

			// Use wp_remote_get() to make an HTTP request.
			$response = wp_remote_get( $bitly_url );

			if ( ! is_wp_error( $response ) ) {
				// Parse the JSON response.
				$body   = wp_remote_retrieve_body( $response );
				$result = json_decode( $body );

				if ( isset( $result->data->url ) ) {
					$post_link = $result->data->url;
				}
			}
		}

		// Set default button text if empty.
		if ( empty( esc_attr( $instance['button_text'] ) ) ) {
			$instance['button_text'] = esc_html__( 'Tweet', 'quotable-tweets' );
		}
		?>
		<div class="<?php echo esc_attr( self::$prefix_dash ); ?>>container">
			<div class="<?php echo esc_attr( self::$prefix_dash ); ?>title-container">
				<span aria-hidden="true" 
				class="<?php echo esc_attr( self::$prefix_dash ); ?>
					icon-twitter">
				</span>
				<span class="<?php echo esc_attr( self::$prefix_dash ); ?>title">
					<?php echo esc_attr( $instance['title'] ); ?>
				</span>

			</div>
			<div class="<?php echo esc_attr( self::$prefix_dash ); ?>text-container">
				<p class="<?php echo esc_attr( self::$prefix_dash ); ?>post-title">  
					<?php
					echo esc_attr( $post->post_title );
					?>
				</p>
				<?php
				$class_name
					= esc_attr( self::$prefix_dash ) . 'quote-container';
				?>
				<p class="<?php echo esc_attr( $class_name ); ?>quote-container">
					<span class="<?php echo esc_attr( self::$prefix_dash ); ?>
					quote dashicons dashicons-format-quote"></span>
				</p>
			</div>
			<?php
			$a_href
				= 'https://twitter.com/intent/tweet?text='
			. $post->post_title . ' ' . $post_link;
			?>
			<a class="<?php echo esc_attr( self::$prefix_dash ); ?>button"
			href="<?php echo esc_url( $a_href ); ?>">
			<?php echo esc_attr( $instance['button_text'] ); ?>
			</a>
		</div>
		<?php
		wp_enqueue_style(
			self::$prefix . 'css',
			plugins_url( 'quotable-tweets.css', __FILE__ ),
			array(),
			'1.0.0'
		);
		wp_enqueue_style( 'dashicons' );

		echo esc_attr( $args['after_widget'] );
	}

	/**
	 * Back-end widget form.
	 *
	 * @param array $instance Previously saved values from the database.
	 *
	 * @see WP_Widget::form()
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$title              = ! empty( $instance['title'] )
			? $instance['title']
			: esc_html__( 'Share this article!', 'quotable-tweets' );
		$bitly_access_token = ! empty( $instance['bitly_access_token'] )
			? $instance['bitly_access_token']
			: '';
		$button_text        = ! empty( $instance['button_text'] )
			? $instance['button_text']
			: esc_html__( 'Tweet', 'quotable-tweets' );
		?>
		<p>
			<label for="
			<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
			<?php esc_html_e( 'Title:', 'quotable-tweets' ); ?> </label>
			<input class="widefat" id="
			<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>
			name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			type="text"
			value="<?php echo esc_attr( $title ); ?>">
			<br />
			<em>
				<?php
				esc_html_e(
					'The title of the widget',
					'quotable-tweets'
				);
				?>
			</em>


		</p>
		<p>
			<label 
			for="
			<?php
			echo esc_attr(
				$this->get_field_id( 'bitly_access_token' )
			);
			?>
			"> 
			<?php
			esc_html_e(
				'Bitly Access Token:',
				'quotable-tweets'
			);
			?>
			</label>
			<input class="widefat" 
			id="<?php echo esc_attr( $this->get_field_id( 'bitly_access_token' ) ); ?>" 
			name="
			<?php echo esc_attr( $this->get_field_name( 'bitly_access_token' ) ); ?>"
			type="text" 
			value="<?php echo esc_attr( $bitly_access_token ); ?>">

			<br />
			<em> 
				<?php esc_html_e( 'Insert your Bitly', 'quotable-tweets' ); ?> 
				<a href="https://bitly.com/a/oauth_apps" target="_blank"> 
					<?php esc_html_e( 'access token', 'quotable-tweets' ); ?> 
				</a> 
				<?php
				esc_html_e(
					'Optional: Shortens post links in the tweet.',
					'quotable-tweets'
				);
				?>
			</em>
		</p>
		<p>
			<label 
			for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>"> 
			<?php esc_html_e( 'Button Text:', 'quotable-tweets' ); ?> </label>
			<input class="widefat" 
			id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>" 
			name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>" 
			type="text" 
			value="<?php echo esc_attr( $button_text ); ?>">
			<br />
			<em> 
				<?php
				esc_html_e(
					'The text of the Tweet button.',
					'quotable-tweets'
				);
				?>
				 
			</em>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from the database.
	 *
	 * @see WP_Widget::update()
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                       = array();
		$instance['title']              = ! empty( $new_instance['title'] )
			? wp_strip_all_tags( $new_instance['title'] )
			: '';
		$instance['bitly_access_token'] = ! empty(
			$new_instance['bitly_access_token']
		)
			? wp_strip_all_tags( $new_instance['bitly_access_token'] )
			: '';

		$instance['button_text'] = ! empty( $new_instance['button_text'] )
			? wp_strip_all_tags( $new_instance['button_text'] )
			: '';

		return $instance;
	}
}

?>