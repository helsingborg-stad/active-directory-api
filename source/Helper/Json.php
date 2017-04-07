<?php

namespace AdApi\Helper;

class Json
{
    /**
     * Send json response and disconnect if wanted
     * @param  array   $data        Data to respond with
     * @param  boolean $disconnect Weather to disconnect or not
     * @return void
     */
    public static function send($data, $disconnect = true)
    {
        if ($disconnect) {
            \AdApi\Server::disconnect();
        }

        if (\AdApi\App::$debug) {
            var_dump($data);
            die();
        }

        @header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        die();
    }

    /**
     * Sends json error message
     * @param  string  $message Error message
     * @param  boolean $code    Error code (optional)
     * @return void
     */
    public static function error($message, $code = false)
    {
        $error = array(
            'message' => $message
        );

        if ($code) {
            $error['code'] = $code;
        }

        self::send(array(
            'error' => $error
        ));
    }
}
