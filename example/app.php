<?php

$app = new \Silex\Application;
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider);

$app->get('/', function() use ($app) {
    $fooUrl = $app['url_generator']->generate('foo');

    return <<<HTML
Hi!

<a href="$fooUrl">Foo</a>
HTML;
});

$app->get('/hello', function() {
    return "Hello!";
})->bind('foo');

return $app;

