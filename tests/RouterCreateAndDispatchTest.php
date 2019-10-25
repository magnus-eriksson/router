<?php
use PHPUnit\Framework\TestCase;
use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class RouterCreateAndDispatchTest extends TestCase
{

    /**
     * Add some routes with closures as callback and dispatch them
     *
     * @covers Router::add
     * @covers Router::dispatch
     */
    public function testCreateAndDispatchRoutesClosuresAsCallback()
    {
        $r = new Router;
        $paths = ['/', '/foo', '/foo/bar'];

        // Create some routes
        foreach ($paths as $path) {
            $r->get($path, function () use ($path) {
                return "get: $path";
            });

            $r->put($path, function () use ($path) {
                return "put: $path";
            });

            $r->post($path, function () use ($path) {
                return "post: $path";
            });

            $r->delete($path, function () use ($path) {
                return "delete: $path";
            });
        }

        // Test dispatching them and check if we get the correct values
        foreach ($paths as $path) {
            $this->assertEquals("get: $path", $r->dispatch('GET', $path));
            $this->assertEquals("put: $path", $r->dispatch('PUT', $path));
            $this->assertEquals("post: $path", $r->dispatch('POST', $path));
            $this->assertEquals("delete: $path", $r->dispatch('DELETE', $path));
        }
    }


    /**
     * Add some routes with class as callback and dispatch
     *
     * @covers Router::add
     * @covers Router::dispatch
     */
    public function testCreateAndDispatchRoutesClassAsCallback()
    {
        $r = new Router;
        $paths = ['/', '/foo', '/foo/bar'];

        // Add some routes with different ways of defining the callback
        $r->get('/', 'Controller@foo');
        $r->get('/foo', ['Controller', 'foo']);
        $r->get('/foo/bar', [new Controller, 'foo']);

        // Test dispatching them and check if we get the correct values
        foreach ($paths as $path) {
            $this->assertEquals("foo-controller", $r->dispatch('GET', $path));
        }

        // Test static callback
        $r->get('/foo/bar/lorem', ['Controller', 'bar']);
        $this->assertEquals("bar-controller-static", $r->dispatch('GET', '/foo/bar/lorem'));
    }


    /**
     * Test routes with optional and non-optional route params
     *
     * @covers Route::dispatch
     */
    public function testCreateAndDisptachRoutesWithParameters()
    {
        $r = new Router;

        // Define routes
        $r->get('/foo/(:alpha)', function ($alpha) {
            return 'first:' . $alpha;
        });

        $r->get('/foo/(:int)', function ($int) {
            return 'second:' . $int;
        });

        $r->get('/foo/(:int)/(:alpha)', function ($int, $alpha) {
            return 'third:' . $int . '/' . $alpha;
        });

        $r->get('/foo/(:int)/(:int)', function ($int, $int2) {
            return 'fourth:' . $int . '/' . $int2;
        });

        // Test
        $this->assertEquals('first:bar', $r->dispatch('GET', '/foo/bar'));
        $this->assertEquals('second:1337', $r->dispatch('GET', '/foo/1337'));
        $this->assertEquals('third:1337/bar', $r->dispatch('GET', '/foo/1337/bar'));
        $this->assertEquals('fourth:1337/1338', $r->dispatch('GET', '/foo/1337/1338'));
    }


    /**
     * Test routes with optional and non-optional route params
     *
     * @covers Route::dispatch
     */
    public function testCreateAndDisptachRoutesWithOptionalParameters()
    {
        $r = new Router;

        $r->get('/foo/(:alpha)/(:int)?', function ($alpha, $int = null) {
            return '/foo/' . $alpha . ($int ? '/' . $int : '');
        });

        // Test
        $this->assertEquals('/foo/bar/1337', $r->dispatch('GET', '/foo/bar/1337'));
        $this->assertEquals('/foo/bar', $r->dispatch('GET', '/foo/bar'));
    }


    /**
     * Test routes with optional and non-optional route params
     *
     * @covers Route::dispatch
     */
    public function testCreateAndDisptachRoutesWithInvalidParameters()
    {
        $r = new Router;

        $r->onNotFound(function () {
            return 404;
        });

        // Define routes
        $r->get('/foo/(:alpha)', function ($alpha) {
            return 'alpha:' . $alpha;
        });

        $r->get('/bar/(:int)', function ($int) {
            return 'int:' . $int;
        });

        // Test
        $this->assertEquals(404, $r->dispatch('GET', '/foo/1337'));
        $this->assertEquals(404, $r->dispatch('GET', '/bar/hello'));
    }
}

