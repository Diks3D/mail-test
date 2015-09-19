<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Setup autoloading
require 'init_autoloader.php';
$loader = new Zend\Loader\StandardAutoloader();
$loader->registerNamespace('App', ROOT_PATH . '/src/App');
$loader->register();

$mailSender = new \App\MailSender();
$response = $mailSender->handle();

return $response->send();