<?php
/**
 * @coversDefaultClass \Maer\Router\Router
 */
class CrudTest extends PHPUnit_Framework_TestCase
{
    public $router;


    public function __construct()
    {
        $this->router = new Maer\Router\Router();
        $this->router->crud('/test', 'CrudController', ['name' => 'crud']);

        $this->router->group(['prefix' => '/grouped'], function ($r) {
            $r->crud('/', 'CrudController', ['name' => 'grouped']);
        });
    }

    public function testDispatchRoute()
    {
        $response = $this->router->dispatch('GET', '/test');
        $this->assertEquals('many', $response, 'crud many');

        $response = $this->router->dispatch('GET', '/test/123');
        $this->assertEquals('one 123', $response, 'crud one');

        $response = $this->router->dispatch('POST', '/test');
        $this->assertEquals('create', $response, 'create');

        $response = $this->router->dispatch('PUT', '/test/123');
        $this->assertEquals('update 123', $response, 'update');

        $response = $this->router->dispatch('DELETE', '/test/123');
        $this->assertEquals('delete 123', $response, 'delete');
    }

    public function testGetName()
    {
        $this->assertEquals('/test', $this->router->getRoute('crud.many'), 'crud name many');
        $this->assertEquals('/test', $this->router->getRoute('crud.create'), 'crud name create');

        $this->assertEquals('/test/123', $this->router->getRoute('crud.one', [123]), 'crud name one');
        $this->assertEquals('/test/123', $this->router->getRoute('crud.delete', [123]), 'crud name delete');
        $this->assertEquals('/test/123', $this->router->getRoute('crud.update', [123]), 'crud name update');
    }

    public function testGroupedDispatchRoute()
    {
        $response = $this->router->dispatch('GET', '/grouped');
        $this->assertEquals('many', $response, 'crud many');

        $response = $this->router->dispatch('GET', '/grouped/123');
        $this->assertEquals('one 123', $response, 'crud one');

        $response = $this->router->dispatch('POST', '/grouped');
        $this->assertEquals('create', $response, 'create');

        $response = $this->router->dispatch('PUT', '/grouped/123');
        $this->assertEquals('update 123', $response, 'update');

        $response = $this->router->dispatch('DELETE', '/grouped/123');
        $this->assertEquals('delete 123', $response, 'delete');
    }

    public function testGroupedGetName()
    {
        $this->assertEquals('/grouped', $this->router->getRoute('grouped.many'), 'grouped name many');
        $this->assertEquals('/grouped', $this->router->getRoute('grouped.create'), 'grouped name create');

        $this->assertEquals('/grouped/123', $this->router->getRoute('grouped.one', [123]), 'grouped name one');
        $this->assertEquals('/grouped/123', $this->router->getRoute('grouped.delete', [123]), 'grouped name delete');
        $this->assertEquals('/grouped/123', $this->router->getRoute('grouped.update', [123]), 'grouped name update');
    }
}

class CrudController
{
    public function create()
    {
        return 'create';
    }

    public function update($id)
    {
        return "update {$id}";
    }

    public function many()
    {
        return 'many';
    }

    public function one($id)
    {
        return "one {$id}";
    }

    public function delete($id)
    {
        return "delete {$id}";
    }
}
