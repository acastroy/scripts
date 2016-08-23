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
	'timezone' => 'Europe/Berlin'
);

if (file_exists(__DIR__ . '/config.local.php')) {
	$local = include __DIR__ . '/config.local.php';
	$config = array_merge($config, $local);
}

require __DIR__ . '/KimaiInstaller.php';

try {

	$installer = new KimaiInstaller();
	$installer
        ->setBaseUrl('http://www.example.com')
		->setConfig($config)
		->setLogger(function ($msg) {
			echo $msg . PHP_EOL;
		})
        ->setBasePath('htdocs/')
		->execute();
} catch (Exception $ex) {
	die('INSTALLATION FAILED: ' . $ex->getMessage() . PHP_EOL);
}