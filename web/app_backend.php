<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'){
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

$loader = require_once __DIR__.'/../app/backend/bootstrap.php.cache';

//clear apc cache
// apc_clear_cache();

/**
 * Enable APC for autoloading to improve performance.
 * You should change the ApcClassLoader first argument to a unique prefix
 * in order to prevent cache key conflicts with other applications
 * also using APC.
 *
 * Yilinker Note:
 * This has been commented out due to intermittent require() PHP fatal error from bootstrap.php.cache.
 * The marginal improvement in performance is not worth getting those errors.
 * See: https://github.com/symfony/symfony/issues/7143
 */
//$apcLoader = new ApcClassLoader('yilinker_online_backend', $loader);
//$loader->unregister();
//$apcLoader->register(true);

require_once __DIR__.'/../app/backend/BackendKernel.php';
//require_once __DIR__.'/../app/BackendCache.php';

$kernel = new BackendKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);