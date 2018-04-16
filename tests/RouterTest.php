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
        $this->addFilters();
    }

    public function addRoutes()
    {
        $this->router->addPattern('date', '\d{4}-\d{2}-\d{2}');

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

        $this->router->get('/regex/(:date)', function ($param) {
            return $param;
        });

        $this->router->group(['before' => 'filter_fail'], function ($router) {
            $router->get('/filter/fail', function () {
                return 'hello world';
            });
        });

        $this->router->group(['before' => 'filter_success'], function ($router) {
            $router->get('/filter/success', function () {
                return 'hello world';
            });
        });

        $this->router->group(['before' => 'filter_class_fail'], function ($router) {
            $router->get('/filter/class/fail', function () {
                return 'hello world';
            });
        });

        $this->router->group(['before' => 'filter_class_success'], function ($router) {
            $router->get('/filter/class/success', function () {
                return 'hello world';
            });
        });

        $this->router->group(['prefix' => '/(:any)'], function ($router) {
            $router->group(['prefix' => '/(:any)'], function ($router) {
                $router->get('/', function ($param1, $param2) {
                    return "{$param1}:{$param2}";
                }, ['name' => 'nested_params']);
            });
        });
    }

    public function addFilters()
    {
        // Add the filters
        $this->router->filter('filter_fail', function () {
            return 'fail';
        });

        $this->router->filter('filter_success', function () {
            return null;
        });

        $this->router->filter('filter_class_fail', 'Controller@filterFail');

        $this->router->filter('filter_class_success', 'Controller@filterSuccess');

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

        // Nested route params
        $route = $this->router->getRoute('nested_params', ['test1', 'test2']);
        $this->assertEquals("/test1/test2", $route, 'Test get nested params');
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

        $response = $this->router->dispatch('GET', '/regex/' . date('Y-m-d'));
        $this->assertEquals(date('Y-m-d'), $response, "Test regex param");

        $response = $this->router->dispatch('GET', '/hello/world');
        $this->assertEquals("hello:world", $response, "Test nested_params");
    }

    public function testFilters()
    {
        $response = $this->router->dispatch('GET', '/filter/fail');
        $this->assertEquals("fail", $response, 'Test filter fail');

        $response = $this->router->dispatch('GET', '/filter/success');
        $this->assertEquals("hello world", $response, 'Test filter success');

        $response = $this->router->dispatch('GET', '/filter/class/fail');
        $this->assertEquals("fail", $response, 'Test filter fail');

        $response = $this->router->dispatch('GET', '/filter/class/success');
        $this->assertEquals("hello world", $response, 'Test filter success');
    }

    /**
     * @expectedException Maer\Router\NotFoundException
     */
    public function testNotFoundException()
    {
        $router = new Maer\Router\Router;
        $router->dispatch();
    }

    public function testNotFoundCallback()
    {
        // Closure
        $router = new Maer\Router\Router;
        $router->notFound(function () {
            return 'foo';
        });
        $this->assertEquals('foo', $router->dispatch(), 'Closure');

        // Class method
        $router = new Maer\Router\Router;
        $router->notFound('Controller@fooCallback');
        $this->assertEquals('foo', $router->dispatch(), 'Closure');
    }

    /**
     * @expectedException Maer\Router\MethodNotAllowedException
     */
    public function testMethodNotAllowedException()
    {
        $router = new Maer\Router\Router;
        $router->get('/', 'dummy');
        $router->dispatch('POST', '/');
    }

    public function testMethodNotAllowedCallback()
    {
        // Closure
        $router = new Maer\Router\Router;
        $router->post('/', 'dummy');
        $router->methodNotAllowed(function () {
            return 'foo';
        });
        $this->assertEquals('foo', $router->dispatch('GET', '/'), 'Closure');

        // Class method
        $router = new Maer\Router\Router;
        $router->post('/', 'dummy');
        $router->methodNotAllowed('Controller@fooCallback');
        $this->assertEquals('foo', $router->dispatch('GET', '/'), 'Closure');
    }

    public function testIdenticalRoutesDifferentMethods()
    {
        $router = new Maer\Router\Router;
        $router->get('/', function () { return 'GET /'; });
        $router->post('/', function () { return 'POST /'; });

        $this->assertEquals('GET /', $router->dispatch('GET', '/'));
        $this->assertEquals('POST /', $router->dispatch('POST', '/'));
    }

    public function testIdenticalRoutesWithOptionalParamsDifferentMethods()
    {
        $router = new Maer\Router\Router;
        $router->get('/test/(:alpha)?', function () { return 'GET /test'; });
        $router->post('/test', function () { return 'POST /test'; });

        $this->assertEquals('GET /test', $router->dispatch('GET', '/test'));
        $this->assertEquals('GET /test', $router->dispatch('GET', '/test/hello'));
        $this->assertEquals('POST /test', $router->dispatch('POST', '/test'));
    }

    /**
     * @expectedException Maer\Router\ControllerNotFoundException
     */
    public function testInvalidController()
    {
        $router = new Maer\Router\Router;
        $router->get('/test', 'NonExistentController@callback');

        $router->dispatch('GET', '/test');
    }

    /**
     * @expectedException Maer\Router\ControllerNotFoundException
     */
    public function testInvalidControllerMethod()
    {
        $router = new Maer\Router\Router;
        $router->get('/test', 'Controller@callback');

        $router->dispatch('GET', '/test');
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

    public function fooCallback()
    {
        return 'foo';
    }

    public function filterFail()
    {
        return 'fail';
    }

    public function filterSuccess()
    {
        return null;
    }
}
