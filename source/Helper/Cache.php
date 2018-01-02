<?php

namespace AdApi\Helper;

class Cache
{

    private $cacheKey;
    private $cacheFolder = __DIR__ . "/../../cache/";
    private $fileSeparator = ".";
    private $fileExtension = "json";

    public function __construct($query)
    {
        //Generate cache key
        $this->cacheKey = $this->generateCacheKey($query);

        //Create cache filename
        $this->filename = $this->cacheFolder . $this->generateCacheFilename();
    }

    public function store($response)
    {
        if (!empty($response)) {
            return (bool) file_put_contents($this->filename, json_encode($response));
        }

        return false;
    }

    public function get()
    {
        if (file_exists($this->filename)) {
            if (date("U", filectime($this->filename) >= time() - (60*10))) {
                return json_decode(file_get_contents($this->filename));
            }
        }
        return null;
    }

    public function generateCacheKey($query)
    {
        if (is_array($query)) {
            $query = implode("", $query);
        }

        return preg_replace('/[^a-zA-Z0-9\-\._]/', '', @crypt($query, date("Y-m-d")));
    }

    public function generateCacheFilename()
    {
        return $this->cacheKey . $this->fileSeparator . $this->fileExtension;
    }
}
