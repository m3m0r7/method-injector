<?php
namespace MethodInjector\Test\Cases\Replacer;

use MethodInjector\Condition;
use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;
use MethodInjector\Test\Fixtures\TestClassExtendedTestClass;

class StaticCallReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testStaticCallReplacer()
    {
        $this->assertSame(
            '1234',
            TestClass::testFunction()
        );

        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClassExtendedTestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->methodGroup(
                            '*',
                            function (Condition $condition) {
                                return $condition
                                    ->replaceStaticCall(
                                        'static',
                                        'TestClass'
                                    );
                            }
                        );
                }
            )
            ->patch();

        $mock = $test->createMock(TestClassExtendedTestClass::class);

        $this->assertSame(
            '9876',
            $mock::testFunction()
        );
    }
}
