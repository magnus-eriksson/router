<?php

class Controller
{
    public function foo()
    {
        return 'foo-controller';
    }

    public static function bar()
    {
        return 'bar-controller-static';
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
