<?php
/**
 * wp-cli proxy: wrapper around mitmproxy
 *
 * @version 0.1.2
 * @author Paolo Tresso <plugins@swergroup.com>
 */

if ( true === class_exists( 'WP_CLI_Command' ) ){
	/**
	 * Install, configure and run local debug proxy
	 *
	 * This command install, configure and run a local debug proxy ( http://mitmproxy.org ).
	 * Setup requires Python >= 2.7 and the pip installer.
	 *
	 */
	class WP_CLI_Proxy_Command extends WP_CLI_Command{
		
		private $version = '0.1.2';
		
		/**
		 * Start mitmproxy
		 * 
		 * ## OPTIONS
		 *
		 * [port]
		 * : Proxy service port. (Default: 9090)
		 *
		 * [--flags=<flags>]
		 * : mitmproxy command flags, i.e.: "-b 127.0.1.1"
		 *
		 * ## EXAMPLES
		 *
		 * wp proxy start
		 * wp proxy start 12345
		 * wp proxy start "-b 127.0.1.1 --palette=solarized_dark"
		 * wp proxy start 8080 "-b 127.0.1.1"
		 *
		 * @param $args array 				Arguments array
		 * @param $assoc_args array 	Associative arguments array
		 * @since 0.1.2
		 * @when before_wp_load
		 * @synopsis [<port>] [--flags=<flags>]
		 */
		public function start( $args = null, $assoc_args = null ){
			$flags = ( isset( $assoc_args['flags'] ) ) ? $assoc_args['flags'] : '';
			if ( isset( $args[0] ) ):
				list( $port ) = $args;
			else :
				$port = '9090';
			endif;
			
			WP_CLI::launch( 'mitmproxy -p ' . $port . ' ' . $flags );
		}
		
		/** 
		 * Patch wp-config.php with our proxy configuration
		 *
		 * Function borrowed from wp-cli itself.
		 * Couldn't find a way to use the original one :|
		 *
		 * @param mixed $content 		wp-config configuration snippet
		 * @uses WP_CLI\Utils\locate_wp_config
		 * @uses file_get_contents
		 * @uses file_put_contents
		 * @since 0.1.1
		 */
		private function _patch_wp_config( $content ){
			$wp_config_path = WP_CLI\Utils\locate_wp_config();
			$token = "/* That's all, stop editing!";
			list( $before, $after ) = explode( $token, file_get_contents( $wp_config_path ) );
			file_put_contents( $wp_config_path, $before . $content . $token . $after );
			WP_CLI::success( 'Added proxy constants to wp-config.php.' );
		}
		
		/**
		 * Add proxy configuration constants to wp-config.php (or dump them to console).
		 *
		 * ## OPTIONS
		 *
		 * [--dump]
		 * : Return values to console insted of patching wp-config.php
		 *
		 * ## EXAMPLES
		 *
		 * wp proxy config
		 * wp proxy config --dump
		 *
		 * ## AVAILABLE CONSTANTS 
		 *
		 *   WP_PROXY
		 *      Run WordPress requests through the proxy. (bool) 
		 *
		 *   WP_PROXY_HOST
		 *      Proxy IP/hostname, default: 127.0.0.1 (string)
		 *
		 *   WP_PROXY_PORT
		 *      Proxy port, default: 9090 (string)
		 *
		 *   WP_PROXY_BYPASS_HOSTS	(string)
		 *      Comma-separated list of ip/hostnames to bypass (string)
		 *
		 *   WP_PROXY_USERNAME
		 *      Optional proxy username (string)
		 *
		 *   WP_PROXY_PASSWORD
		 *      Optional proxy password (string)
		 *
		 * @param $args array 				Arguments array
		 * @param $assoc_args array 	Associative arguments array
		 * @uses _patch_wp_config
		 * @since 0.1.1
		 * @when before_wp_load
		 * @synopsis [--dump]
		 */
		public function config( $args = null, $assoc_args = null ){
			
			$proxy_config = <<<WPCONFIG

define( 'WP_PROXY', true );
if ( WP_PROXY ){
	define( 'WP_PROXY_HOST', '127.0.0.1' );
	define( 'WP_PROXY_PORT', '9090' );
	define( 'WP_PROXY_BYPASS_HOSTS', '127.0.0.1' );
	define( 'WP_PROXY_USERNAME', '' );
	define( 'WP_PROXY_PASSWORD', '' );
}

WPCONFIG;

		if ( isset( $assoc_args['dump'] ) ):
			WP_CLI::line( $proxy_config );
		else :
			self::_patch_wp_config( $proxy_config );
		endif;
		}

		/**
		 * Install mitmproxy via the pip installer
		 *
		 * @uses passthru
		 * @since 0.1.2
		 */
		private function _mitmproxy_install( $sudo ){
			WP_CLI::log( 'Installing mitmproxy..' );
			passthru( $sudo . 'pip install mitmproxy --upgrade', $res );
			if ( 0 === $res ){
				WP_CLI::success( 'mitmproxy successfully installed.' );
			} else {
				WP_CLI::error( 'Sorry, something went wrong.' );
			}
		}

		/**
		 * Check pip package and call mitmproxy install procedure
		 *
		 * @uses _mitmproxy_install
		 * @uses passthru
		 * @since 0.1.2
		 */
		private function _do_install( $sudo ){
			// check pip installer
			passthru( $sudo . 'pip -V', $check );
			if ( 0 === $check ){
				// real install
				self::_mitmproxy_install( $sudo );
			} else {
				// python or pip or something else missing
				WP_CLI::error( 'Python >= 2.7 + pip installer required. See http://www.pip-installer.org .' );
			}
		}

		/**
		 * Install mitmproxy
		 *
		 * Check if mitmproxy is already installed, otherwise installs it. 
		 * Requires Python >= 2.7 and the pip installer.
		 * See http://www.pip-installer.org for details.
		 *
		 * ## EXAMPLES
		 *
		 * wp proxy install
		 * wp proxy install --sudo
		 *
		 * @uses _do_install
		 * @uses passthru
		 * @since 0.1.1
		 * @when before_wp_load
		 * @synopsis [--sudo]
		 */
		public function install( $args = null, $assoc_args = null ){
			if ( isset( $assoc_args['sudo'] ) && 1 === $assoc_args['sudo'] ):
				$sudo = 'sudo ';
			else :
				$sudo = '';
			endif;

			passthru( 'mitmproxy --version', $mitmcheck );

			if ( 0 === $mitmcheck ){
				// already installed 
				WP_CLI::success( 'mitmproxy already installed.' );
			} else {
				self::_do_install( $sudo );
			}

		}
		
		/**
		 * Return wp-cli + mitmproxy versions
		 *
		 * ## EXAMPLES
		 *
		 * wp proxy version
		 *
		 * @since 0.1.1
		 * @when before_wp_load
		 * @synopsis [--extra]
		 */
		public function version(){
			WP_CLI::line( 'wp-cli proxy command ' . $this->version );
			if ( isset( $assoc_args['extra'] ) && 1 === $assoc_args['extra'] ):
				WP_CLI::launch( 'mitmproxy --version' );
				WP_CLI::launch( 'wp --info' );
			endif;
		}

	}

	WP_CLI::add_command( 'proxy', 'WP_CLI_Proxy_Command' );
}