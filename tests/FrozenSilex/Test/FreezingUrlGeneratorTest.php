<?php

namespace FrozenSilex\Test;

use FrozenSilex\FreezingUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator;

class FrozenUrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    function test()
    {
        $app = new \Silex\Application;
        $app->get('/foo', function() {})->bind('foo');
        $app->flush();

        $mock = $this->getMock('\\FrozenSilex\\Freezer', array('freezeRoute'), array($app));
        $mock->expects($this->once())->method('freezeRoute');

        $generator = new FreezingUrlGenerator(new UrlGenerator($app['routes'], $app['request_context']), $mock);
        $generator->generate('foo');
    }
}
