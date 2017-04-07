<?php

namespace AdApi;

class Server
{
    private $host;
    private $port;
    private $useTls;
    private $networkTimeout;
    private $username;
    private $password;


    public function __construct($host, $port, $useTls, $networkTimeout, $username, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->useTls = $useTls;
        $this->networkTimeout = $networkTimeout;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Connect to and authenticate with the LDAP server
     * @param  array $args Connection arguments
     * @return boolean
     */
    public function connect()
    {
        \AdApi\App::debugLog('Trying to connect');

        // Connect to the ldap server
        try {
            \AdApi\App::$ad = ldap_connect($this->host, $this->port);
        } catch (Exception $e) {
            \AdApi\Helper\Json::error('Could not connect to the ldap server. Please make sure that you are using correct server details.');
        }

        // Set ldap options
        ldap_set_option(\AdApi\App::$ad, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option(\AdApi\App::$ad, LDAP_OPT_REFERRALS, 0);
        ldap_set_option(\AdApi\App::$ad, LDAP_OPT_NETWORK_TIMEOUT, $this->networkTimeout);

        // Start tls if wanted
        if ($this->useTls === true) {
            ldap_start_tls(\AdApi\App::$ad);
        }

        \AdApi\App::debugLog('Trying to authenticate');

        // Authenticate user/password with the ldap server
        $auth = $this->auth();

        if (!$auth) {
            \AdApi\App::debugLog('Username or password is incorrect', true);
            \AdApi\Helper\Json::error('Could not authenticate with the ldap server. Incorrect authentication credentials.');
        }

        \AdApi\App::debugLog('We are connected and authenticated');

        return true;
    }

    /**
     * Disconnect from the ldap server
     * @return boolean
     */
    public static function disconnect()
    {
        if (\AdApi\App::$ad) {
            ldap_unbind(\AdApi\App::$ad);
        }

        return true;
    }

    /**
     * Authenticate with supplied username and password
     * @return boolean
     */
    public function auth()
    {
        $auth = @ldap_bind(\AdApi\App::$ad, $this->username, $this->password);

        if (!$auth) {
            return false;
        }

        \AdApi\App::$currentUser = $this->username;

        return true;
    }
}
