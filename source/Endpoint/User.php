<?php

namespace AdApi\Endpoint;

class User extends \AdApi\Endpoint
{

    /**
     * Get usernames for all users
     * @return array The usernames
     */
    public function index($cookie = null, $results = array()) : array
    {
        do {

            //Init pagination
            @ldap_control_paged_result(\AdApi\App::$ad, 500, true, $cookie);

            //Do search / get entries
            $search     = @ldap_search(\AdApi\App::$ad, \AdApi\App::$baseDn, "(&(objectCategory=person)(samaccountname=*)" . \AdApi\App::$customFilter . "(!(userAccountControl:1.2.840.113556.1.4.803:=2)))", array('samaccountname'));
            $entries    = @ldap_get_entries(\AdApi\App::$ad, $search);

            //Merge to full result array
            $results = array_merge($results, $entries);

            //Next page
            @ldap_control_paged_result_response(\AdApi\App::$ad, $search, $cookie);
        } while ($cookie !== null && $cookie != '');

        //Only keep usernames in an array
        $return = array();
        foreach ($results as $result) {
            if (isset($result['samaccountname']) && isset($result['samaccountname'][0]) && !empty($result['samaccountname'][0])) {
                if (\AdApi\Helper\Filter::validUserName($result['samaccountname'][0])) {
                    $return[] = strtolower($result['samaccountname'][0]);
                }
            }
        }

        //Clean array
        $return = array_filter(array_unique($return));

        //Return error if empty
        if (empty($return)) {
            \AdApi\Helper\Json::error('Did not find any matching user(s)');
        }

        return $return;
    }

    /**
     * Get current users userinfo
     * @param  array  $fields   Fields to get (null for default)
     * @return array            The current users userinfo
     */
    public function current()
    {
        return $this->get(\AdApi\App::$currentUser);
    }

    /**
     * Get userinfo from username(s)
     * @param  dynamic  $q         Usernames (or email) as multiple params
     * @return array               The userdata
     */
    public function get($q)
    {
        //Extract
        $queries = func_get_args();

        //Get local cache if is within n seconds
        $cache = new \AdApi\Helper\Cache($q);
        if (!is_null($response = $cache->get())) {
            return $response;
        }

        //Fetch response
        $response = $this->search($queries);

        //Cache response
        $cache->store($response);

        //Return
        return $response;
    }

    /**
     * Search for a user and return userinfo
     * @param  string $username Username to search for
     * @return array            Users userinfo
     */
    public function search($q)
    {
        \AdApi\App::debugLog('Searching for user(s)…');

        $filterQuery = null;

        foreach ((array)$q as $query) {
            $query = str_replace(\AdApi\App::$accountSuffix, '', $query);

            $key = 'samaccountname';
            if (strpos($query, '@') > -1) {
                $key = 'mail';
            }

            $filterQuery .= '(' . $key . '=' . $query . ')';
        }

        if ($filterQuery) {
            $filterQuery = '(|' . $filterQuery . ')';
        }

        $filter = '(&(objectCategory=person)' . \AdApi\App::$customFilter  . $filterQuery . '(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';

        $fields = array(
            'samaccountname',
            'mail',
            'memberof',
            'department',
            'displayname',
            'telephonenumber',
            'primarygroupid',
            'title',
            'company',
            'givename',
            'lastlogon',
            'sn',
            'manager',
            'mobile',
            'physicaldeliveryofficename',
            'streetaddress',
            'postalcode',
            'useraccountcontrol',
            'description',
            'userprincipalname'
        );

        if (!is_null(\AdApi\App::$numberOfExtensionAttr) && !empty(\AdApi\App::$numberOfExtensionAttr)) {
            for ($x = 1; $x <= \AdApi\App::$numberOfExtensionAttr; $x++) {
                $fields[] = "extensionAttribute" . $x;
            }
        }

        $search     = ldap_search(\AdApi\App::$ad, \AdApi\App::$baseDn, $filter, $fields);
        $results    = ldap_get_entries(\AdApi\App::$ad, $search);

        //Remove prohibited accounts
        $return = array();
        foreach ($results as $result) {
            if (isset($result['samaccountname']) && isset($result['samaccountname'][0]) && !empty($result['samaccountname'][0])) {
                if (\AdApi\Helper\Filter::validUserName($result['samaccountname'][0])) {
                    $return[] = strtolower($result['samaccountname'][0]);
                }
            }
        }

        if (empty($return)) {
            \AdApi\Helper\Json::error('Did not find any matching user(s)');
        }

        return $this->formatUserdata($results);
    }

    /**
     * Format the output to be more logic & filter out irellevant data
     * @param  array    Userdata array
     * @return array    Userdata array formatted
     */

    public function formatUserdata($users)
    {
        unset($users['count']);
        $formattedData = array();

        foreach ($users as $user) {
            unset($user['objectsid']);

            $userdata = array();
            $keys = array_filter(array_keys($user), function ($item) {
                return !is_int($item) && $item !== 'count';
            });

            foreach ($keys as $key) {
                if (is_array($user[$key]) && isset($user[$key]['count'])) {
                    unset($user[$key]['count']);
                }
                $userdata[$key] = is_array($user[$key]) ? implode(",", $user[$key]) : $user[$key];
            }

            $formattedData[] = $userdata;
        }

        return $formattedData;
    }
}
