<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Tweet shortcode.
 * Params map to key value pairs, and all but tweet are optional:
 * tweet = id or permalink url* (Required)
 * align = none|left|right|center
 * width = number in pixels  example: width="300"
 * lang  =  en|fr|de|ko|etc...  language country code.
 * hide_thread = true | false **
 * hide_media  = true | false **
 *
 * Basic:
 * [tweet https://twitter.com/jack/statuses/20 width="350"]
 *
 * More parameters and another tweet syntax admitted:
 * [tweet tweet="https://twitter.com/jack/statuses/20" align="left" width="350" align="center" lang="es"]
 *
 * @package automattic/jetpack
 */

add_shortcode( 'tweet', array( 'Jetpack_Tweet', 'jetpack_tweet_shortcode' ) );

/**
 * Tweet Shortcode class.
 */
class Jetpack_Tweet {

	/**
	 * Array of arguments about a tweet.
	 *
	 * @var array
	 */
	public static $provider_args;

	/**
	 * Parse shortcode arguments and render its output.
	 *
	 * @since 4.5.0
	 *
	 * @param array $atts Shortcode parameters.
	 *
	 * @return string
	 */
	public static function jetpack_tweet_shortcode( $atts ) {
		global $wp_embed;

		$default_atts = array(
			'tweet'       => '',
			'align'       => 'none',
			'width'       => '',
			'lang'        => 'en',
			'hide_thread' => 'false',
			'hide_media'  => 'false',
		);

		$attr = shortcode_atts( $default_atts, $atts );

		self::$provider_args = $attr;

		/*
		 * figure out the tweet id for the requested tweet
		 * supporting both omitted attributes and tweet="tweet_id"
		 * and supporting both an id and a URL
		 */
		if ( empty( $attr['tweet'] ) && ! empty( $atts[0] ) ) {
			$attr['tweet'] = $atts[0];
		}

		if ( ctype_digit( $attr['tweet'] ) ) {
			$id       = 'https://twitter.com/jetpack/status/' . $attr['tweet'];
			$tweet_id = (int) $attr['tweet'];
		} else {
			preg_match( '/^http(s|):\/\/twitter\.com(\/\#\!\/|\/)([a-zA-Z0-9_]{1,20})\/status(es)*\/(\d+)$/', $attr['tweet'], $urlbits );

			if ( isset( $urlbits[5] ) && (int) $urlbits[5] ) {
				$id       = 'https://twitter.com/' . $urlbits[3] . '/status/' . (int) $urlbits[5];
				$tweet_id = (int) $urlbits[5];
			} else {
				return '<!-- Invalid tweet id -->';
			}
		}

		// Add shortcode arguments to provider URL.
		add_filter( 'oembed_fetch_url', array( 'Jetpack_Tweet', 'jetpack_tweet_url_extra_args' ), 10, 3 );

		/*
			* In Jetpack, we use $wp_embed->shortcode() to return the tweet output.
			* @see https://github.com/Automattic/jetpack/pull/11173
			*/
		$output = $wp_embed->shortcode( $atts, $id );

		// Clean up filter.
		remove_filter( 'oembed_fetch_url', array( 'Jetpack_Tweet', 'jetpack_tweet_url_extra_args' ), 10 );

		/** This action is documented in modules/widgets/social-media-icons.php */
		do_action( 'jetpack_bump_stats_extras', 'embeds', 'tweet' );

		if ( class_exists( 'Jetpack_AMP_Support' ) && Jetpack_AMP_Support::is_amp_request() ) {
			$width  = ! empty( $attr['width'] ) ? $attr['width'] : 600;
			$height = 480;
			$output = sprintf(
				'<amp-twitter data-tweetid="%1$s" layout="responsive" width="%2$d" height="%3$d"></amp-twitter>',
				esc_attr( $tweet_id ),
				absint( $width ),
				absint( $height )
			);
		} else {
			// Add Twitter widgets.js script to the footer.
			add_action( 'wp_footer', array( 'Jetpack_Tweet', 'jetpack_tweet_shortcode_script' ) );
		}

		return $output;
	}

	/**
	 * Adds parameters to URL used to fetch the tweet.
	 *
	 * @since 4.5.0
	 *
	 * @param string $provider URL of provider that supplies the tweet we're requesting.
	 * @param string $url      URL of tweet to embed.
	 * @param array  $args     Parameters supplied to shortcode and passed to wp_oembed_get.
	 *
	 * @return string
	 */
	public static function jetpack_tweet_url_extra_args( $provider, $url, $args = array() ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		foreach ( self::$provider_args as $key => $value ) {
			switch ( $key ) {
				case 'align':
				case 'lang':
				case 'hide_thread':
				case 'hide_media':
					$provider = add_query_arg( $key, $value, $provider );
					break;
			}
		}

		// Disable script since we're enqueing it in our own way in the footer.
		$provider = add_query_arg( 'omit_script', 'true', $provider );

		// Twitter doesn't support maxheight so don't send it.
		$provider = remove_query_arg( 'maxheight', $provider );

		/**
		 * Filter the Twitter Partner ID.
		 *
		 * @module shortcodes
		 *
		 * @since 4.6.0
		 *
		 * @param string $partner_id Twitter partner ID.
		 */
		$partner = apply_filters( 'jetpack_twitter_partner_id', 'jetpack' );

		// Add Twitter partner ID to track embeds from Jetpack.
		if ( ! empty( $partner ) ) {
			$provider = add_query_arg( 'partner', $partner, $provider );
		}

		return $provider;
	}

	/**
	 * Enqueue front end assets.
	 *
	 * @since 4.5.0
	 */
	public static function jetpack_tweet_shortcode_script() {
		if ( ! wp_script_is( 'twitter-widgets', 'registered' ) ) {
			wp_register_script( 'twitter-widgets', 'https://platform.twitter.com/widgets.js', array(), JETPACK__VERSION, true );
			wp_print_scripts( 'twitter-widgets' );
		}
	}

} // class end
