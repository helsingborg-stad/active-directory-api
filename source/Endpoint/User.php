<?php

namespace AdApi\Endpoint;

class User extends \AdApi\Endpoint
{
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
        $queries = func_get_args();
        return $this->search($queries);
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

        $filter = '(&(objectCategory=person)' . $filterQuery . ')';

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
            'extensionattribute3',
            'mobile',
            'physicaldeliveryofficename',
            'streetaddress',
            'postalcode'
        );

        $search = ldap_search(\AdApi\App::$ad, \AdApi\App::$baseDn, $filter, $fields);
        $results = ldap_get_entries(\AdApi\App::$ad, $search);

        if ($results['count'] === 0) {
            \AdApi\Helper\Json::error('Did not find any matching user(s)');
        }

        return $this->formatUserdata($results);
    }

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
                $userdata[$key] = $user[$key][0];
            }

            $formattedData[] = $userdata;
        }

        return $formattedData;
    }
}
