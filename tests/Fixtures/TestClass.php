<?php declare(strict_types=1);
namespace MethodInjector\Test\Fixtures;

class TestClass
{
    const TEST_CONST = 'OLD TEXT';

    public $property = 'OLD PROPERTY';

    public static $staticProperty = 'OLD PROPERTY';

    public function test()
    {
        return date('Y-m-d H:i:s');
    }

    public function test1()
    {
    }

    public static function testFunction()
    {
        return '1234';
    }

    public function test2()
    {
        static::testFunction();
    }

    public function test3()
    {
        return new static();
    }

    public function test4()
    {
        return new ChildClass();
    }
}
