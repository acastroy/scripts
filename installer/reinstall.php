<?php
/**
 * A simple command line installer for Kimai.
 * Could be used for setting up demo websites or development environments.
 *
 * Find the newest version at: https://github.com/kimai/scripts
 */

$config = array(
    'server_hostname' => 'localhost',
    'server_database' => 'kimai',
    'server_username' => 'kimai',
    'server_password' => 'kimai',
    'server_prefix' => 'kimai_',
    'language' => 'en',
    'password_salt' => '1xO4dlmSwk21rASvp7S50',
);

require __DIR__ . '/KimaiInstaller.php';

$installer = new KimaiInstaller();
$installer
    ->setBaseUrl('http://www.example.com')
    ->setConfig($config)
    ->setLogger(function ($msg) {
        echo $msg . PHP_EOLD;
    })
    ->setTimezone('Europe/Berlin')
    ->setBasePath('htdocs/')
    ->execute();
