<?php
namespace MethodInjector\Tests\Cases\Inspector;

use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;

class MethodReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testMethodReplacer()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->replaceMethod(
                            'test',
                            function () {
                                return 'Fixed value';
                            }
                        );
                }
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertSame(
            'Fixed value',
            $mock->test()
        );
    }
}
