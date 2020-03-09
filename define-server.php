<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Plugin Name: DEFINE SERVER
 * Description: DEFINE SERVER can help us to define the 'downloadserver' and the 'apiserver',it's help China's user solve '429 Too Many Requests' and more.
 * Version: 1.0
 * Author: 坏男孩
 * Text Domain: define-server
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Author URI: http://33.al/
 */

class DEFINE_SERVER {

	private $wsOptions;

	public function __construct() {
		$this->setup_vars();
		$this->hooks();
	}

	public function setup_vars(){
		$this->wsOptions = get_option( 'define_server_options' );
    }

	public function hooks() {
		register_activation_hook( __FILE__ , array( $this,'define_server_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'define_server_deactivate' ) );
		add_filter( 'plugin_action_links', array( $this, 'define_server_settings_link' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'define_server_admin' ) );
        add_filter( 'pre_http_request', array(__CLASS__, 'pre_http_request'), 10, 3);
	}
	
	/*激活插件设置默认服务器*/
	function define_server_activate(){
		$wsOptions = array();
		$wsOptions["apiserver"] = "api.wordpress.org";
		$wsOptions["downserver"] = "downloads.wordpress.org";
		add_option( 'define_server_options', $wsOptions );
	}
	/*停用插件，删除掉数据*/
	function define_server_deactivate() {
	
		delete_option( 'define_server_options' );
	}

	
	function define_server_settings_link($action_links,$plugin_file) {
		if( $plugin_file == plugin_basename( __FILE__ ) ) {
			$ws_settings_link = '<a href="options-general.php?page=' . dirname( plugin_basename(__FILE__) ) . '/define-server.php">' . __("Settings") . '</a>';
			array_unshift($action_links,$ws_settings_link);
		}

		return $action_links;
	}
	function define_server_admin(){
		add_options_page('DEFINE SERVER Options', 'DEFINE SERVER','manage_options', __FILE__, array( $this, 'define_server_page') );
	}
	function define_server_page(){
		require_once __DIR__ . '/define_server_admin.php';
	}
	public function pre_http_request($preempt, $r, $url) {
        if ( ! stristr($url, 'api.wordpress.org') && ! stristr($url, 'downloads.wordpress.org')) {
            return false;
        }
        $wsOptions=get_option( 'define_server_options' );
        $url = str_ireplace( 'api.wordpress.org', $wsOptions["apiserver"], $url );
        $url = str_ireplace( 'downloads.wordpress.org', $wsOptions["downserver"], $url );

        if (function_exists('wp_kses_bad_protocol')) {
            if ($r['reject_unsafe_urls']) {
                $url = wp_http_validate_url($url);
            }
            if ($url) {
                $url = wp_kses_bad_protocol($url, array(
                    'http',
                    'https',
                    'ssl'
                ));
            }
        }
        $arrURL = @parse_url($url);
        if (empty($url) || empty($arrURL['scheme'])) {
            return new WP_Error('http_request_failed', __('A valid URL was not provided.'));
        }

        // If we are streaming to a file but no filename was given drop it in the WP temp dir
        // and pick its name using the basename of the $url
        if ($r['stream']) {
            if (empty($r['filename'])) {
                $r['filename'] = get_temp_dir() . basename($url);
            }

            // Force some settings if we are streaming to a file and check for existence and perms of destination directory
            $r['blocking'] = true;
            if ( ! wp_is_writable(dirname($r['filename']))) {
                return new WP_Error('http_request_failed', __('Destination directory for file streaming does not exist or is not writable.'));
            }
        }

        if (is_null($r['headers'])) {
            $r['headers'] = array();
        }

        // WP allows passing in headers as a string, weirdly.
        if ( ! is_array($r['headers'])) {
            $processedHeaders = WP_Http::processHeaders($r['headers']);
            $r['headers']     = $processedHeaders['headers'];
        }

        // Setup arguments
        $headers = $r['headers'];
        $data    = $r['body'];
        $type    = $r['method'];
        $options = array(
            'timeout'   => $r['timeout'],
            'useragent' => $r['user-agent'],
            'blocking'  => $r['blocking'],
            'hooks'     => new WP_HTTP_Requests_Hooks($url, $r),
        );

        if ($r['stream']) {
            $options['filename'] = $r['filename'];
        }
        if (empty($r['redirection'])) {
            $options['follow_redirects'] = false;
        } else {
            $options['redirects'] = $r['redirection'];
        }

        // Use byte limit, if we can
        if (isset($r['limit_response_size'])) {
            $options['max_bytes'] = $r['limit_response_size'];
        }

        // If we've got cookies, use and convert them to Requests_Cookie.
        if ( ! empty($r['cookies'])) {
            $options['cookies'] = WP_Http::normalize_cookies($r['cookies']);
        }

        // SSL certificate handling
        if ( ! $r['sslverify']) {
            $options['verify']     = false;
            $options['verifyname'] = false;
        } else {
            $options['verify'] = $r['sslcertificates'];
        }

        // All non-GET/HEAD requests should put the arguments in the form body.
        if ('HEAD' !== $type && 'GET' !== $type) {
            $options['data_format'] = 'body';
        }

        /**
         * Filters whether SSL should be verified for non-local requests.
         *
         * @param bool $ssl_verify Whether to verify the SSL connection. Default true.
         * @param string $url The request URL.
         *
         * @since 2.8.0
         * @since 5.1.0 The `$url` parameter was added.
         *
         */
        $options['verify'] = apply_filters('https_ssl_verify', $options['verify'], $url);

        // Check for proxies.
        $proxy = new WP_HTTP_Proxy();
        if ($proxy->is_enabled() && $proxy->send_through_proxy($url)) {
            $options['proxy'] = new Requests_Proxy_HTTP($proxy->host() . ':' . $proxy->port());

            if ($proxy->use_authentication()) {
                $options['proxy']->use_authentication = true;
                $options['proxy']->user               = $proxy->username();
                $options['proxy']->pass               = $proxy->password();
            }
        }

        // Avoid issues where mbstring.func_overload is enabled
        mbstring_binary_safe_encoding();

        try {
            $requests_response = Requests::request($url, $headers, $data, $type, $options);

            // Convert the response into an array
            $http_response = new WP_HTTP_Requests_Response($requests_response, $r['filename']);
            $response      = $http_response->to_array();

            // Add the original object to the array.
            $response['http_response'] = $http_response;
        } catch (Requests_Exception $e) {
            $response = new WP_Error('http_request_failed', $e->getMessage());
        }

        reset_mbstring_encoding();

        /**
         * Fires after an HTTP API response is received and before the response is returned.
         *
         * @param array|WP_Error $response HTTP response or WP_Error object.
         * @param string $context Context under which the hook is fired.
         * @param string $class HTTP transport used.
         * @param array $r HTTP request arguments.
         * @param string $url The request URL.
         *
         * @since 2.8.0
         *
         */
        do_action('http_api_debug', $response, 'response', 'Requests', $r, $url);
        if (is_wp_error($response)) {
            return $response;
        }

        if ( ! $r['blocking']) {
            return array(
                'headers'       => array(),
                'body'          => '',
                'response'      => array(
                    'code'    => false,
                    'message' => false,
                ),
                'cookies'       => array(),
                'http_response' => null,
            );
        }

        /**
         * Filters the HTTP API response immediately before the response is returned.
         *
         * @param array $response HTTP response.
         * @param array $r HTTP request arguments.
         * @param string $url The request URL.
         *
         * @since 2.9.0
         *
         */
        return apply_filters('http_response', $response, $r, $url);
    }
}

new DEFINE_SERVER();
?>