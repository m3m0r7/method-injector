<?php declare(strict_types=1);
namespace MethodInjector\Test\Fixtures;

class ExtendedClassTest extends AbstractTestClass
{
    use Trait1;
    use Trait3;

    protected $property = 'old value';
    protected $extendedProperty2 = 'new value';

    public function testMethod()
    {
    }
}
