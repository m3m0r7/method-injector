<?php
namespace MethodInjector\Test\Cases\Replacer;

use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;

class ClassConstantReplacerTest extends \PHPUnit\Framework\TestCase
{
    public function testConstantReplacer()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->replaceConstant(
                            'TEST_CONST',
                            'New Value'
                        );
                },
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertSame(
            'New Value',
            $mock::TEST_CONST
        );
    }
}
