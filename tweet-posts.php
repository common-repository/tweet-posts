<?php
/*
Plugin Name: Tweet Posts
Plugin URI: http://joshbetz.com/2012/08/tweet-posts/
Description: Tweet your posts.
Version: 0.3
Author: Josh Betz
Author URI: http://joshbetz.com
*/

require_once( 'twitteroauth.php' );

class Tweet_Posts {

	const VERSION = '0.3';
	const SLUG    = 'tweet-posts';
	const OPTION  = 'tweet_posts';

	private $options = array();

	function __construct() {
		add_action( 'init', array( $this, 'init' ) );

		add_action( 'wp_head', array( $this, 'wp_head' ) );

		/* Admin actions */
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'publish_post', array( $this, 'publish_post' ), 10, 2 );

		$this->options = get_option( self::OPTION );
	}

	function init() {
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'user_contactmethods', array( $this, 'user_contactmethods') );
	}

	function deactivate() {
		unregister_setting( self::OPTION, self::OPTION, array( $this, 'validate_settings' ) );
	}

	function user_contactmethods( $m ) {
		$m['twitter'] = __( 'Twitter', 'tweet-posts' );
		return $m;
	}

	function wp_head() {
		if ( is_single() ) {
			// We need to see the post so we know what to fill in
			the_post();

			if ( !empty( $this->options['twitter_site'] ) )
				echo '<meta name="twitter:site" content="@' . $this->options['twitter_site'] . '">';

			$twitter = get_the_author_meta( 'twitter' );
			if ( !empty( $twitter ) )
				echo '<meta name="twitter:creator" content="@' . get_the_author_meta( 'twitter'  ) . '">';

			switch ( get_post_format() ) {
				case 'image':
					echo '<meta name="twitter:card" content="photo">';
					break;
				case 'video':
				case 'audio':
					//TODO: implement player cards
					//echo '<meta name="twitter:card" content="player">';
					//echo '<meta name="twitter:player" content="">';
					//echo '<meta name="twitter:player:stream" content="">';
					//echo '<meta name="twitter:player:stream:content_type" content="">';
					//echo '<meta name="twitter:image" content="">';
					break;
				default:
					echo '<meta name="twitter:card" content="summary">';
					break;
			}
			echo '<meta name="twitter:url" content="' . get_permalink() . '">';
			echo '<meta name="twitter:title" content="' . get_the_title() . '">';
			echo '<meta name="twitter:description" content="' . wp_trim_excerpt( get_the_excerpt() ) . '">';

			if ( has_post_thumbnail() ) {
				$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
				echo '<meta name="twitter:image" content="' . $src[0] . '">';
			}

			// Rewind the loop even though it's a single page
			rewind_posts();
		}
	}

	function publish_post( $id, $post ) {
		// If this isn't new, bail
		if ( $post->post_date != $post->post_modified )
			return;

		$title = $post->post_title;
		$shortlink = wp_get_shortlink( $id );
		$type = get_post_format( $id );

		$tweet = self::get_tweet( $title, $shortlink, $type );
		$this->do_tweet( $tweet );
	}

	function do_tweet( $tweet ) {
		$connection = new TwitterOAuth( $this->options['consumer_key'], $this->options['consumer_secret'], $this->options['oauth_token'], $this->options['oauth_token_secret'] );

		$args = array(
			'status' => esc_html( $tweet ),
			'include_entities' => true
		);
		$response = $connection->post( 'statuses/update', $args );

		return $response;
	}

	static function get_tweet( $_title, $shortlink, $type ) {
		switch ( $type ) {
			case 'aside':
			case 'status':
			case 'quote':
			case 'chat':
				$pre = "New $type: ";
				break;

			case 'audio':
			case 'video':
			case 'image':
			case 'link':
				$pre = "New $type post: ";
				break;

			case 'gallery':
				$pre = "New image gallery: ";
				break;

			case 'standard':
			default:
				$pre = "New blog post: ";
		}

		if ( empty( $_title ) )
			return $pre . $shortlink;

		$left = 140 - strlen( $pre . $shortlink );
		if ( strlen( $_title ) < $left )
			$title = $_title . ' ';
		else {
			$_title = substr( $_title, 0, $left - 4 );
			$parts = explode( ' ', $_title );
			array_pop( $parts );
			$title = implode( ' ', $parts ) . '... ';
		}

		return $pre . $title . $shortlink;
	}

	function admin_menu() {
		add_options_page( __( 'Tweet Posts', 'tweet-posts' ), __( 'Tweet Posts', 'tweet-posts' ), 'manage_options', self::SLUG, array( $this, 'settings_page' ) );
	}

	function settings_page() { ?>
		<div class="wrap">
			<h2><?php _e( 'Tweet Posts', 'tweet-posts' ); ?></h2>
			<form action="options.php" method="post">
				<?php
					settings_fields( self::OPTION );
					do_settings_sections( self::SLUG );
					submit_button();
				?>
			</form>
		</div>
<?php }

	function admin_init() {
		register_setting( self::OPTION, self::OPTION, array( $this, 'validate_settings' ) );
		add_settings_section( self::SLUG . 'info', __( 'Twitter Account Info', 'tweet-posts' ), array( $this, 'twitter_section' ), self::SLUG );
		add_settings_field( 'twitter_site', __( 'Site Twitter', 'tweet-posts' ), array( $this, 'input' ), self::SLUG, self::SLUG . 'info', array( 'name' => 'twitter_site' ) );

		add_settings_section( self::SLUG . 'api', __( 'Twitter API', 'tweet-posts' ), array( $this, 'api_section' ), self::SLUG );
		add_settings_field( 'consumer_key', __( 'Consumer Key', 'tweet-posts' ), array( $this, 'input' ), self::SLUG, self::SLUG . 'api', array( 'name' => 'consumer_key' ) );
		add_settings_field( 'consumer_secret', __( 'Consumer Secret', 'tweet-posts' ), array( $this, 'input' ), self::SLUG, self::SLUG . 'api', array( 'name' => 'consumer_secret' ) );
		add_settings_field( 'oauth_token', __( 'OAuth Token', 'tweet-posts' ), array( $this, 'input' ), self::SLUG, self::SLUG . 'api', array( 'name' => 'oauth_token' ) );
		add_settings_field( 'oauth_token_secret', __( 'OAuth Token Secret', 'tweet-posts' ), array( $this, 'input' ), self::SLUG, self::SLUG . 'api', array( 'name' => 'oauth_token_secret' ) );
	}

	function twitter_section() { ?>
		<p class="description">Fill in your site's Twitter account information. You can change your personal Twitter username in <a href="<?php echo admin_url( 'profile.php' ); ?>">your profile</a>.</p>
<?php }

	function api_section() { ?>
		<p class="description">Fill in your Twitter API details.</p>
<?php }

	function input( $post ) {
		$options = $this->options;
		$setting = $post['name'];
		$value = !empty( $options[$setting] ) ? $options[$setting] : ''; ?>
		<input id='<?php echo $setting; ?>' name='<?php echo self::OPTION . '[' . $setting . ']'; ?>' size='40' type='text' value='<?php echo $value; ?>' />
<?php }

	function validate_settings( $input ) {
		// API Data
		$new['consumer_key'] = esc_attr( $input['consumer_key'] );
		$new['consumer_secret'] = esc_attr( $input['consumer_secret'] );
		$new['oauth_token'] = esc_attr( $input['oauth_token'] );
		$new['oauth_token_secret'] = esc_attr( $input['oauth_token_secret'] );

		// Twitter Account Data
		$new['twitter_site'] = esc_attr( $input['twitter_site'] );
		return $new;
	}

}

new Tweet_Posts();
