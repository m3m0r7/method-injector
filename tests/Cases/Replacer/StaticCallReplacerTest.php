<?php
namespace MethodInjector\Test\Cases\Replacer;

use MethodInjector\Condition;
use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;
use MethodInjector\Test\Fixtures\TestClassExtendedTestClass;

class StaticCallReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testStaticCallWithClassReplacer()
    {
        $this->assertSame(
            '1234',
            TestClass::testFunction()
        );

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
                                    ->replaceStaticCall(
                                        'static',
                                        TestClassExtendedTestClass::class
                                    );
                            }
                        );
                }
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertSame(
            '9876',
            $mock->test2()
        );
    }
}
