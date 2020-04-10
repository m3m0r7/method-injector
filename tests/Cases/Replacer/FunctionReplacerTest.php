<?php
namespace MethodInjector\Test\Cases\Replacer;

use MethodInjector\Condition;
use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;

class FunctionReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testFunctionReplacer()
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
                                    ->replaceFunction(
                                        'date',
                                        function (...$args) {
                                            return '1800-01-01';
                                        }
                                    );
                            }
                        );
                }
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertSame(
            '1800-01-01',
            $mock->test()
        );
    }
}
