<?php
namespace MethodInjector\Tests\Cases\Inspector;

use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;

class ConstantReplacerTest extends \PHPUnit\Framework\TestCase
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
