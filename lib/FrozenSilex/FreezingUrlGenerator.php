<?php

namespace FrozenSilex;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * URL Generator which intercepts calls and passes the generated
 * URLs to the Freezer
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class FreezingUrlGenerator implements UrlGeneratorInterface
{
    protected $generator;
    protected $freezer;

    function __construct(UrlGeneratorInterface $generator, Freezer $freezer)
    {
        $this->generator = $generator;
    }

    /** {@inheritDoc} */
    function generate($name, $parameters = array(), $absolute = false)
    {
        $uri = $this->generator($name, $parameters, $absolute);

        $this->freezer->freezeRoute($uri);

        return $uri;
    }
}

