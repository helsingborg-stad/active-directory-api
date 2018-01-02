<?php

namespace AdApi\Helper;

class Cache
{

    private $cacheKey;
    private $cacheFolder = __DIR__ . "/../../cache/";
    private $fileSeparator = ".";
    private $fileExtension = "json";

    /**
     * Setup required parameters for the class to work propoply
     * @param  array $query Full query to ad server
     * @return void
     */
    public function __construct($query)
    {
        //Generate cache key
        $this->cacheKey = $this->generateCacheKey($query);

        //Create cache filename
        $this->filename = $this->cacheFolder . $this->generateCacheFilename();
    }

    /**
     * Store reponse in a file
     * @param  array $response Response as an array
     * @return boolean
     */
    public function store($response)
    {
        return (bool) file_put_contents($this->filename, json_encode($response));
    }

    /**
     * Get response if it's not stale
     * @return array
     */
    public function get()
    {
        if (file_exists($this->filename)) {
            if (date("U", filectime($this->filename) >= time() - (60*10))) {
                return json_decode(file_get_contents($this->filename));
            }
        }
        return null;
    }

    /**
     * Generates a cache key based on input vars.
     * @param  array $args Connection arguments
     * @return string
     */
    public function generateCacheKey($query)
    {
        if (is_array($query)) {
            $query = implode("", $query);
        }

        return preg_replace('/[^a-zA-Z0-9\-\._]/', '', @crypt($query, date("Y-m-d")));
    }

    /**
     * CConcatinate variables to create a full filename
     * @return string
     */
    public function generateCacheFilename()
    {
        return $this->cacheKey . $this->fileSeparator . $this->fileExtension;
    }
}
