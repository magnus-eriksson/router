<?php
use PHPUnit\Framework\TestCase;
use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class FiltersTest extends TestCase
{
    /**
     * Get named routes without any parameters
     *
     * @covers Router::getRoute
     */
    public function testGetRouteWithoutRouteParameters()
    {
        $r = new Router;

        $r->get('/', function () {}, [
            'name' => 'home',
        ]);

        $r->get('/foo', function () {}, [
            'name' => 'foo',
        ]);

        $r->get('/foo/bar', function () {}, [
            'name' => 'foobar',
        ]);

        $this->assertEquals('/', $r->getRoute('home'));
        $this->assertEquals('/foo', $r->getRoute('foo'));
        $this->assertEquals('/foo/bar', $r->getRoute('foobar'));
    }


    /**
     * Get named routes with route parameters
     *
     * @covers Router::getRoute
     */
    public function testGetRouteWithRouteParameters()
    {
        $r = new Router;

        // Set up routes
        $r->get('/foo/(:any)', function () {}, [
            'name' => 'r1',
        ]);

        $r->get('/foo/(:any)/(:any)', function () {}, [
            'name' => 'r2',
        ]);

        // Optional param
        $r->get('/foobar/(:any)?', function () {}, [
            'name' => 'r3',
        ]);


        // Test
        $this->assertEquals(
            '/foo/bar',
            $r->getRoute('r1', ['bar'])
        );

        $this->assertEquals(
            '/foo/bar/lipsum',
            $r->getRoute('r2', ['bar', 'lipsum'])
        );

        $this->assertEquals(
            '/foobar/lipsum',
            $r->getRoute('r3', ['lipsum'])
        );

        $this->assertEquals(
            '/foobar',
            $r->getRoute('r3')
        );
    }


    /**
     * Get named routes with route parameters
     *
     * @covers Router::getRoute
     */
    public function testGetRouteWithInvalidParameterCount()
    {
        $r = new Router;

        // Set up routes
        $r->get('/foo/(:any)', function () {}, [
            'name' => 'r1',
        ]);

        $this->expectException(BadMethodCallException::class);

        $r->getRoute('r1');
    }
}
