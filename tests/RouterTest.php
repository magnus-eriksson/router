<?php
/**
 * @coversDefaultClass \Maer\Router\Router
 */
class RouterTest extends PHPUnit_Framework_TestCase
{

    public $router;


    public function __construct()
    {
        $this->router = new Maer\Router\Router();

        $this->addRoutes();
    }

    public function addRoutes()
    {
        $this->router->get(['/', 'home'], function () {
            return 'home route';
        });

        $this->router->get('/test/(:any)?/(:any)?',
            'Controller@testCallback',
            ['name' => 'test']
        );

        $this->router->group(['prefix' => 'grp'], function ($router) {

            $this->router->get(['/', 'grp.home'], function () {
                return 'grp home route';
            });

        });

        $this->router->get(['/last', 'last'], function () {
            return 'last route';
        });

        $this->router->get('/all/(:all)', function ($param) {
            return $param;
        });
    }

    public function testGetRoute()
    {
        // Home
        $route = $this->router->getRoute('home');
        $this->assertEquals("/", $route, 'Test get home route');


        // Test, optional params
        $route = $this->router->getRoute('test');
        $this->assertEquals("/test", $route, 'Test get test route');

        $route = $this->router->getRoute('test', ['first']);
        $this->assertEquals("/test/first", $route, 'Test get test route, 1 param');

        $route = $this->router->getRoute('test', ['first', 'second']);
        $this->assertEquals("/test/first/second", $route, 'Test get test route, 2 params');


        // Group - Home
        $route = $this->router->getRoute('grp.home');
        $this->assertEquals("/grp", $route, 'Test get group home route');

        // Last
        $route = $this->router->getRoute('last');
        $this->assertEquals("/last", $route, 'Test get last route');
    }

    public function testDispatch()
    {
        $response = $this->router->dispatch('GET', '/');
        $this->assertEquals("home route", $response, 'Test dispatch home');

        $response = $this->router->dispatch('GET', '/test');
        $this->assertEquals("test route", $response, 'Test dispatch test');

        $response = $this->router->dispatch('GET', '/test/first');
        $this->assertEquals("test route, first", $response, 'Test dispatch test, 1 param');

        $response = $this->router->dispatch('GET', '/test/first/second');
        $this->assertEquals("test route, first, second", $response, 'Test dispatch test, 2 params');

        $response = $this->router->dispatch('GET', '/all/hello/world');
        $this->assertEquals("hello/world", $response, "Test :all");
    }
}

class Controller
{
    public function testCallback($p1 = null, $p2 = null)
    {
        if (is_null($p1)) {
            return 'test route';
        }

        if (is_null($p2)) {
            return 'test route, ' . $p1;
        }

        return 'test route, ' . $p1 . ', ' . $p2;
    }
}