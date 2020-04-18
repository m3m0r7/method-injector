<?php
namespace MethodInjector\Test\Cases\Inspector;

use MethodInjector\Inspector;

class ExtendedClassTest extends \PHPUnit\Framework\TestCase
{
    public function testExtendedClass()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                \MethodInjector\Test\Fixtures\ExtendedClassTest::class,
                function (Inspector $inspector) {
                    return $inspector
                        ->enableParentMock(true)
                        ->expandTraits(true);
                },
                true
            )
            ->patch();

//        $test->createMock(\MethodInjector\Test\Fixtures\ExtendedClassTest::class);

        $test->generateMockedCode(\MethodInjector\Test\Fixtures\ExtendedClassTest::class);
        var_dump($test->getGeneratedCodeTraceAsString());
        exit();
    }
}
