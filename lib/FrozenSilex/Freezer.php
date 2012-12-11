<?php

namespace FrozenSilex;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpKernel;

/**
 * Decorates the Silex application and freezes all detected routes
 * to static files when `freeze()` is called.
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class Freezer
{
    protected $application;
    protected $generators = array();

    function __construct(\Silex\Application $app)
    {
        $this->application = $app;

        if (!isset($app['freezer.override_url_generator'])) {
            $app['freezer.override_url_generator'] = false;
        }

        if (!isset($app['freezer.destination'])) {
            $app['freezer.destination'] = 'build';
        }

        if ($app['freezer.override_url_generator']) {
            $self = $this;

            # Override the app's URL generator with a generator which freezes
            # every route automatically when the generator is called within
            # the app's controllers or views.
            $app['url_generator'] = $app->share(function() use ($app, $self) {
                if (!isset($app['url_generator'])) {
                    $app->register(new \Silex\Provider\UrlGeneratorServiceProvider);
                }

                return new FreezingUrlGenerator($app['url_generator'], $self);
            });
        }

        # Define the default Generator which yields the app's routes.
        $this->registerGenerator(function() use ($app) {
            $routes = array();

            foreach ($app['routes']->all() as $name => $route) {
                $routes[] = array($name);
            }

            return $routes;
        });
    }

    /**
     * Register a generator for routes.
     *
     * Generators are functions which return an array of routes which should
     * be requested when the site is frozen.
     *
     * Each item of the returned array can either be a plain string, which is
     * treated as a plain URL, or an array of two items (the route name and the params)
     * which is passed to the default URLGenerator of the Symfony Routing Component.
     *
     * Example:
     *
     *   $app->get('/users/{id}', function($id) {})->bind('show_user');
     *
     *   $freezer->registerGenerator(function() use ($app) {
     *     $users = array();
     *
     *     foreach ($app['mongo']->users->find() as $user) {
     *       $users[] = array('show_user', array('id' => (string) $user['_id']));
     *     }
     *
     *     return $users;
     *   });
     *
     * @param callable $generator
     * @return Freezer
     */
    function registerGenerator($generator)
    {
        $this->generators[] = $generator;
        return $this;
    }

    /**
     * Freezes the application and writes the generated files to the
     * destination directory.
     *
     * @return void
     */
    function freeze()
    {
        $this->application->boot();
        $this->application->flush();

        $generator = new UrlGenerator($this->application['routes'], $this->application['request_context']);
        $output = $this->application['freezer.destination'];

        if (!is_dir($output)) {
            mkdir($output, 0755, true);
        }

        $routes = array();

        foreach ($this->generators as $g) {
            foreach ($g() as $route) {
                $routes[] = $route;
            }
        }

        $route = null;

        foreach ($routes as $route) {
            if (is_array($route)) {
                $routeName = $route[0];
                $params = @$route[1] ?: array();

                $this->freezeRoute($generator->generate($routeName, $params));
            } else {
                $this->freezeRoute((string) $route);
            }
        }
    }

    /**
     * Freezes a given URI
     *
     * @todo Fix links so the static page can be viewed without server
     * @todo Add setting to rewrite links to a given base path.
     *
     * @param string $uri
     */
    function freezeRoute($uri)
    {
        $client = new HttpKernel\Client($this->application);
        $client->request('GET', $uri);

        $response = $client->getResponse();

        if (!$response->isOk()) {
            return;
        }

        $destination = $this->application['freezer.destination'] . $this->getFileName($uri);

        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination, 0755, true));
        }

        file_put_contents($destination, $response->getContent());

        if (isset($this->application['logger'])) {
            $app['logger']->addInfo("Freezed URI $uri to $out");
        }
    }

    protected function getFileName($uri)
    {
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos - 1);
        }

        if (substr($uri, -1, 1) === '/') {
            $file = $uri . "index.html";
        } else {
            $file = "$uri.html";
        }

        if (!substr($file, 0, 1) === '/') {
            $file = "/$file";
        }

        return $file;
    }
}

