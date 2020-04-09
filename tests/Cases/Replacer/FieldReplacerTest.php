<?php
namespace MethodInjector\Tests\Cases\Inspector;

use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;

class FieldReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testFieldReplacerWithDynamic()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->replaceField(
                            'property',
                            'New Value'
                        );
                },
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertSame(
            'New Value',
            $mock->property
        );
    }

    public function testFieldReplacerWithStatic()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->replaceField(
                            'staticProperty',
                            'New Value'
                        );
                },
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertSame(
            'New Value',
            $mock::$staticProperty
        );
    }
}
