<?php
// brevo_config.php
require_once(__DIR__ . '/vendor/autoload.php'); // Adjust path as necessary

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;

$config = Configuration::getDefaultConfiguration();
// Load API key securely (e.g., from environment variables)
$config->setApiKey('api-key', 'API KEY'); 

$emailApi = new TransactionalEmailsApi(
    new Client(),
    $config
);
?>