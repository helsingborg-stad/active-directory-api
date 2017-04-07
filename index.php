<?php

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once('App.php');
    require_once('configuration.php');

    $api =  new \AdApi\App(
        $configuration
    );
