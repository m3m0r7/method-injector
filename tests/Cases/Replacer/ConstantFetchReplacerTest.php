<?php
namespace MethodInjector\Test\Cases\Replacer;

use MethodInjector\Condition;
use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;

class ConstantFetchReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testConstantReplacer()
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
                                    ->replaceConstantFetch(
                                        'CONSTANT_TEST_1',
                                        'CONSTANT_TEST_2'
                                    );
                            }
                        );
                },
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertSame(
            CONSTANT_TEST_2,
            $mock->test5()
        );
    }
}
