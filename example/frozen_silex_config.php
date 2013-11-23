<?php

$app = require(__DIR__ . '/app.php');
$app['debug'] = true;

/*
$app->before(function() use ($app) {
    $app['request_context']->setBaseUrl('/foo');
});
*/

$freezer = new \FrozenSilex\Freezer($app);

return $freezer;
