<?php

namespace FrozenSilex;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

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
    protected $context;

    function __construct(UrlGeneratorInterface $generator, Freezer $freezer)
    {
        $this->generator = $generator;
        $this->freezer = $freezer;
    }

    /** {@inheritDoc} */
    function generate($name, $parameters = array(), $absolute = false)
    {
        $uri = $this->generator->generate($name, $parameters, $absolute);

        $this->freezer->freezeRoute($uri);

        return $uri;
    }

    function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    function getContext()
    {
        return $this->context;
    }
}

