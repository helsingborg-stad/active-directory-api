# Active Directory APi
This repository contains an api compatible with our ad-api-integration plugin. The api simplifies integration of active directory syncronization and authentication. It also allows administrators to authenticate with active directory on sites that normally not are allow to utilizate active directory as a authentication method. 

The active directory api is simple to configure. Here are an commented configuration file. 

```php
$configuration = array (
        'base_dn' => 'OU=Adminview,DC=xyz,DC=xyz,DC=se',
        'account_suffix' => '@xyz.xyz.se',
        'host' => '127.0.0.0',
        'port' => 389,
        'use_tls' => true,
        'network_timeout' => 5, 
        'invalid_username_patterns' => array(
            '/^s([a-z]{4})([0-9]{4})/i',
            '/^([a-z]{3})([0-9]{4})/i',
            '/^([a-z]{6})([0-9]{4})/i',
            '/^([0-9]{6})([a-z]{2})/i',
            '/adintegration/i', // You want to disable integration etc from bulk import
        ), // Remove these user patterns. Use php regular expressions. 
        'custom_filter' => '(objectClass=user)(!(extensionattribute12=FunctionalAccount))(!(extensionattribute12=SpecialAccount))(mail=*)(!(lastlogon=0))', // Filter all querys with this filter. Use a normal ldap syntax here. 
        'number_of_extension_attributes' => 12 // The number of extension attributes that sould be displayed 
    );
```

Thats it! You can query the api without plugin, or with the examples below. 

# Get a index of all usernames avabile (requires a master account)

```php
    $curl = new Curl(); // This class is avabile below

    $data = array(
        'username' => 'administrator',
        'password' => '*****************'
    );

    echo $curl->request('POST', rtrim('https://ad-api.dev/ad-api/', "/") . '/user/index/', $data, 'json', array('Content-Type: application/json'));
```

# Get the current authenticated user

```php
    $curl = new Curl(); // This class is avabile below
    
    $data = array(
        'username' => 'user',
        'password' => '*****************'
    );

    echo $curl->request('POST', rtrim('https://ad-api.dev/ad-api/', "/") . '/user/current/', $data, 'json', array('Content-Type: application/json'));
```

# Get specified username profiles (requires a master account)

```php
    $curl = new Curl(); // This class is avabile below

    $data = array(
        'username' => 'administrator',
        'password' => '*****************'
    );

    $accounts = array("testaccount1", "testaccount2", "testaccount3"); 

    echo $curl->request('POST', rtrim('https://ad-api.dev/ad-api/', "/") . '/user/get/' .implode("/", $accounts). '/', $data, 'json', array('Content-Type: application/json'));
```

# CURL class example (include this to be able to run examples)

```php
    class Curl {
        /**
         * Curl request
         * @param  string $type        Request type
         * @param  string $url         Request url
         * @param  array $data         Request data
         * @param  string $contentType Content type
         * @param  array $headers      Request headers
         * @return string              The request response
         */

        public function request($type, $url, $data = null, $contentType = 'json', $headers = null)
        {
            //Arguments are stored here
            $arguments = null;

            switch (strtoupper($type)) {
                /**
                 * Method: GET
                 */
                case 'GET':
                    // Append $data as querystring to $url
                    if (is_array($data)) {
                        $url .= '?' . http_build_query($data);
                    }

                    // Set curl options for GET
                    $arguments = array(
                        CURLOPT_RETURNTRANSFER      => true,
                        CURLOPT_HEADER              => false,
                        CURLOPT_FOLLOWLOCATION      => true,
                        CURLOPT_SSL_VERIFYPEER      => false,
                        CURLOPT_SSL_VERIFYHOST      => false,
                        CURLOPT_URL                 => $url,
                        CURLOPT_CONNECTTIMEOUT_MS  => 1500
                    );

                    break;

                /**
                 * Method: POST
                 */
                case 'POST':
                    // Set curl options for POST
                    $arguments = array(
                        CURLOPT_RETURNTRANSFER      => 1,
                        CURLOPT_URL                 => $url,
                        CURLOPT_POST                => 1,
                        CURLOPT_HEADER              => false,
                        CURLOPT_CONNECTTIMEOUT_MS   => 3000
                    );

                    if (in_array($contentType, array("json", "jsonp"))) {
                        $arguments[CURLOPT_POSTFIELDS] = json_encode($data);
                    } else {
                        $arguments[CURLOPT_POSTFIELDS] = http_build_query($data) ;
                    }

                    break;
            }

            /**
             * Set up headers if given
             */
            if ($headers) {
                $arguments[CURLOPT_HTTPHEADER] = $headers;
            }

            /**
             * Do the actual curl
             */
            $ch = curl_init();
            curl_setopt_array($ch, $arguments);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response = curl_exec($ch);
            curl_close($ch);

            /**
             * Return the response
             */
            return $response;
        }
    }
```
