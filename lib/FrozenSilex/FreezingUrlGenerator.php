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
        $url = $this->generator->generate($name, $parameters, $absolute);

        if (!$absolute) {
            $this->freezer->freezeRoute($name, $parameters);
        }

        return $url;
    }

    function setContext(RequestContext $context)
    {
        $this->generator->setContext($context);
    }

    function getContext()
    {
        return $this->generator->getContext();
    }
}

