<?php
/**
 * wp-cli minify command
 *
 * @author Paolo Tresso <plugins@swergroup.com>
 */

if ( true === class_exists( 'WP_CLI_Command' ) ){
	/**
	 * Install and run mitmproxy (https://mitmproxy.org)
	 *
	 *  -b ADDR               Address to bind proxy to (defaults to all interfaces)
	 *  --anticache           Strip out request headers that might cause the server
	 *                        to return 304-not-modified.
	 *  --confdir CONFDIR     Configuration directory. (~/.mitmproxy)
	 *  -e                    Show event log.
	 *  -n                    Don't start a proxy server.
	 *  -p PORT               Proxy service port.
	 *  -P REVERSE_PROXY      Reverse proxy to upstream server:
	 *                        http[s]://host[:port]
	 *  -q                    Quiet.
	 *  -r RFILE              Read flows from file.
	 *  -s SCRIPT             Run a script.
	 *  -t FILTER             Set sticky cookie filter. Matched against requests.
	 *  -T                    Set transparent proxy mode.
	 *  -u FILTER             Set sticky auth filter. Matched against requests.
	 *  -v                    Increase verbosity. Can be passed multiple times.
	 *  -w WFILE              Write flows to file.
	 *  -z                    Try to convince servers to send us un-compressed data.
	 *  -Z SIZE               Byte size limit of HTTP request and response bodies.
	 *                        Understands k/m/g suffixes, i.e. 3m for 3 megabytes.
	 *  --host                Use the Host header to construct URLs for display.
	 *  --no-upstream-cert    Don't connect to upstream server to look up
	 *                        certificate details.
	 *  --debug
	 *  --palette PALETTE     Select color palette: dark, light, solarized_dark,
	 *                        solarized_light
	 *
	 * Web App:
	 *  -a                    Enable the mitmproxy web app.
	 *  --appdomain domain    Domain to serve the app from.
	 *  --appip ip            IP to serve the app from. Useful for transparent mode,
	 *                        when a DNS entry for the app domain is not present.
	 */
	class WP_CLI_Proxy_Command extends WP_CLI_Command{
		
		private function _patch_wp_config( $content ){
			$wp_config_path = WP_CLI\Utils\locate_wp_config();
			$token = "/* That's all, stop editing!";
			list( $before, $after ) = explode( $token, file_get_contents( $wp_config_path ) );
			file_put_contents( $wp_config_path, $before . $content . $token . $after );
			WP_CLI::success( 'Added proxy constants to wp-config.php.' );
		}
		
		public function config( $args = null, $assoc_args = null ){
			$proxy_config = <<<WPCONFIG
define( 'WP_PROXY', true );
if ( WP_PROXY ){
	define( 'WP_PROXY_HOST', 'localhost' );
	define( 'WP_PROXY_PORT', '8080' );
	define( 'WP_PROXY_USERNAME', '' );
	define( 'WP_PROXY_PASSWORD', '' );
	define( 'WP_PROXY_BYPASS_HOSTS', 'localhost' );
}
WPCONFIG;
			self::_patch_wp_config( $proxy_config );
		}

		/**
		 * @alias upgrade
		 */
		public function install( $args = null, $assoc_args = null ){
			WP_CLI::log( 'Installing mitmproxy..' );
			passthru( 'pip install mitmproxy --upgrade', $res );
			if ( 0 === $res ){
				WP_CLI::success( 'mitmproxy successfully installed.' );
			} else {
				WP_CLI::error( 'Sorry, I could not install mitmproxy.' );
			}
		}
		
		/**
		 * @subcommand is-installed
		 */
		public function is_installed( $args = null, $assoc_args = null ){
			passthru( 'mitmproxy --version', $res );
			if ( 0 === $res ){
				WP_CLI::success( 'mitmproxy is installed.' );
			} else {
				WP_CLI::error( 'mitmproxy is NOT installed.' );
				self::install();
			}
		}

		public function version( $args = null, $assoc_args = null ){
			WP_CLI::launch( 'mitmproxy --version' );
		}

	}

	WP_CLI::add_command( 'proxy', 'WP_CLI_Proxy_Command' );
}
