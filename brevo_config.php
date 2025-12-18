<?php
// brevo_config.php

require_once(__DIR__ . '/vendor/autoload.php');

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;

$config = Configuration::getDefaultConfiguration();
$config->setApiKey('api-key', 'APK KEY');

    /**
     * 🔴 SSL BYPASS (LOCAL DEVELOPMENT ONLY)
     */
    $guzzleClient = new Client([
        'verify' => false, // ⬅ THIS IS THE BYPASS
    ]);

    $emailApi = new TransactionalEmailsApi(
        $guzzleClient,
        $config
    );
 