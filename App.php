<?php

namespace AdApi;

class App
{
    public static $debug = false;

    public static $ad;
    public static $baseDn;
    public static $accountSuffix;

    public static $currentUser;

    private $requiredArgs = array(
        'base_dn',
        'account_suffix',
        'host',
        'port',
        'use_tls',
        'network_timeout',
    );

    public function __construct($args = array())
    {
        $this->autoload();

        if (!$this->hasRequiredArgs($args)) {
            \AdApi\Helper\Json::error('Missing connection args: ' . implode(', ', $this->getMissingConnectionArgs($args)));
        }


        if (!isset($args['username']) || !isset($args['password'])) {
            $entityBody = json_decode(stripslashes(file_get_contents('php://input')));

            if (!isset($entityBody->username) || !isset($entityBody->password)) {
                \AdApi\Helper\Json::error('Invalid credentials');
            }

            $args['username'] = $entityBody->username;
            $args['password'] = $entityBody->password;
        }

        self::$baseDn = $args['base_dn'];
        self::$accountSuffix = $args['account_suffix'];

        // Add suffix to username
        if (substr($args['username'], -strlen($args['account_suffix'])) !== $args['account_suffix']) {
            $args['username'] .= $args['account_suffix'];
        }

        // Prepare to connect to server
        $server = new \AdApi\Server(
            $args['host'],
            $args['port'],
            $args['use_tls'],
            $args['network_timeout'],
            $args['username'],
            $args['password']
        );

        // Connect
        $server->connect();

        $this->callEndpoint();
    }

    /**
     * Autoload all required components
     * @return void
     */
    public function autoload()
    {
        require_once 'source/Vendor/Psr4ClassLoader.php';

        $loader = new \AdApi\Vendor\Psr4ClassLoader();
        $loader->addPrefix('AdApi', 'source');
        $loader->register();
    }

    /**
     * Calls the requested endpoint
     * @return void
     */
    public function callEndpoint()
    {
        if (!isset($_GET['e']) || empty($_GET['e'])) {
            \AdApi\Helper\Json::error('No endpoint or method called');
        }

        $e = explode('/', $_GET['e']);
        $endpoint = ucfirst($e[0]);
        $method = $e[1];
        $params = array_slice($e, 2);


        \AdApi\App::debugLog('Calling endpoint "' . $endpoint . '" method "' . $method . '" with params "' . implode(',', $params) . '"');

        $endpoint = '\AdApi\Endpoint\\' . $endpoint;
        $endpoint = new $endpoint();
        $data = call_user_func_array(array($endpoint, $method), $params);

        \AdApi\Helper\Json::send($data, true);
    }

    /**
     * Check if $args include all required connection args
     * @param  array  $args  The connection args
     * @return boolean
     */
    public function hasRequiredArgs($args)
    {
        $matchingKeys = array_intersect_key(array_flip($this->requiredArgs), $args);
        return count($matchingKeys) === count($this->requiredArgs);
    }

    /**
     * Get the key names for any missing connection arg
     * @param  array $args  The connection args
     * @return array        Missing keys
     */
    public function getMissingArgs($args)
    {
        return array_keys(array_diff_key(array_flip($this->requiredArgs), $args));
    }

    /**
     * Echo debug string
     * @param  string  $text Text to echo
     * @param  boolean $exit Exit after or not
     * @return void
     */
    public static function debugLog($text, $exit = false)
    {
        if (!self::$debug) {
            return;
        }

        echo 'LDAP Debug: ', $text, '<br>';

        if ($exit) {
            exit;
        }
    }
}
