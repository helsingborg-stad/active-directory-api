<?php

    require_once('App.php');

    $api =  new \AdApi\App(
        json_decode(file_get_contents('configuration.json'))
    );
