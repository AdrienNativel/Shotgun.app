<?php

$dbName   = 'adrien_nativel';
$dbServer = '127.0.0.1';

$dsn = 'mysql:dbname='.$dbName.';host='.$dbServer;
$username = 'root';
$password = 'modal';

// Autoloading (composer is preferred, but for this example let's just do this)
require_once('oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

// $dsn is the Data Source Name for your database
$storage = new OAuth2\Storage\Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));

// Pass a storage object or array of storage objects to the OAuth2 server class
$server = new OAuth2\Server($storage);

// Add the grant type UserCredentials
$grantType = new OAuth2\GrantType\UserCredentials($storage);
$server->addGrantType($grantType);