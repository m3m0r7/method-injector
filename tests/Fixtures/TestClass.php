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
}
