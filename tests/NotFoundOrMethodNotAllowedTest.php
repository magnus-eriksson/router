<?php
use PHPUnit\Framework\TestCase;
use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class NotFoundOrMethodNotAllowedTest extends TestCase
{
    /**
     * Test setting custom 404
     *
     * @covers Router::onNotFound
     */
    public function testNotFoundCallback()
    {
        $r = new Router;

        $r->onNotFound(function () {
            return 'not found';
        });

        // Add some routes
        $r->get('/some/route', function () {});
        $r->get('/foo/(:alpha)', function () {});

        $this->assertEquals('not found', $r->dispatch('GET', '/foobar'));
        $this->assertEquals('not found', $r->dispatch('GET', '/foo/1337'));
    }


    /**
     * Test setting custom 405
     *
     * @covers Router::onMethodNotAllowed
     */
    public function testMethodNotAllowedCallback()
    {
        $r = new Router;

        $r->onMethodNotAllowed(function () {
            return 'not allowed';
        });

        // Add some routes
        $r->get('/some/route', function () {});
        $r->get('/foo/(:alpha)', function () {});

        $this->assertEquals('not allowed', $r->dispatch('POST', '/some/route'));
        $this->assertEquals('not allowed', $r->dispatch('POST', '/foo/bar'));
    }
}
