<?php

namespace AdApi\Helper;

class Filter
{
    /**
     * Check if username is valid (not prohibited)
     * @return bool
     */
    public static function validUserName($username)
    {
        if (is_array(\AdApi\App::$invalidUserNamePattern) && !empty(\AdApi\App::$invalidUserNamePattern)) {
            foreach ((array) \AdApi\App::$invalidUserNamePattern as $pattern) {
                if (preg_match($pattern, $username)) {
                    return false;
                }
            }
        }
        return true;
    }
}
