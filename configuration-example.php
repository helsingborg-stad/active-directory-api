<?php

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
        )
    );
