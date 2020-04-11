<?php declare(strict_types=1);
namespace MethodInjector\Test\Fixtures;

class TestClassExtendedTestClass extends TestClass
{
    public static function testFunction()
    {
        return '9876';
    }
}
