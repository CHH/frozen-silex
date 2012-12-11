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
            $app['freezer.destination'] = '_site';
        }

        $self = $this;

        if ($app['freezer.override_url_generator']) {
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

        $crawler = $client->request('GET', $uri);
        $response = $client->getResponse();

        $out = ltrim($uri, '/');

        if (($pos = strpos($out, '?')) !== false) {
            $out = substr($out, 0, $pos - 1);
        }

        if (empty($out)) {
            $out = "index.html";
        } else {
            $out = "$out.html";
        }

        foreach ($crawler->filter("a") as $link) {
            if (!$href = $link->getAttribute('href')) {
                continue;
            }

            # Freeze only local links
            if (substr($href, 0, 2) !== '//' and !preg_match('~^[a-zA-Z]+://~', $href)) {
                $this->freezeRoute($href);
            }
        }

        file_put_contents($this->application['freezer.destination'] . "/$out", $response->getContent());

        if (isset($this->application['logger'])) {
            $app['logger']->addInfo("Freezed URI $uri to $out");
        }
    }
}

