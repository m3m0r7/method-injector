<?php
namespace MethodInjector\Test\Cases\Replacer;

use MethodInjector\Condition;
use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\ChildClass;
use MethodInjector\Test\Fixtures\TestClass;

class InstanceReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testInstanceWithClassReplacer()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->methodGroup(
                            '*',
                            function (Condition $condition) {
                                return $condition
                                    ->replaceInstance(
                                        'static',
                                        ChildClass::class
                                    );
                            }
                        );
                }
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertInstanceOf(
            ChildClass::class,
            $mock->test3()
        );
    }

    public function testInstanceWithStaticReplacer()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->methodGroup(
                            '*',
                            function (Condition $condition) {
                                return $condition
                                    ->replaceInstance(
                                        ChildClass::class,
                                        'static'
                                    );
                            }
                        );
                },
                true
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertInstanceOf(
            TestClass::class,
            $mock->test4()
        );
    }

    public function testInstanceWithSelfReplacer()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->methodGroup(
                            '*',
                            function (Condition $condition) {
                                return $condition
                                    ->replaceInstance(
                                        ChildClass::class,
                                        'self'
                                    );
                            }
                        );
                },
                true
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertInstanceOf(
            TestClass::class,
            $mock->test4()
        );
    }
}
