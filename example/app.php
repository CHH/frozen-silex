<?php

$app = new \Silex\Application;

$app->get('/', function() {
    return <<<HTML
Hi!

<a href="/hello">Foo</a>
HTML;
});

$app->get('/hello', function() {
    return "Hello!";
});

return $app;

