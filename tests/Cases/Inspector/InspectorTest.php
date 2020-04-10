<?php
namespace MethodInjector\Test\Cases\Inspector;

use MethodInjector\Inspector;
use MethodInjector\Test\Fixtures\TestClass;
use MethodInjector\Test\Fixtures\TestClassExtendedTestClass;
use MethodInjector\Test\Fixtures\TestImplementedTestInterface;
use MethodInjector\Test\Fixtures\TestInterface;

class InspectorTest extends \PHPUnit\Framework\TestCase
{
    public function testInspectorWithNotInherited()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector;
                },
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertMatchesRegularExpression(
            '/MethodInjector\\\\Mocked\\\\__MOCKED__MethodInjector__Test__Fixtures__TestClass_\d+__/',
            get_class($mock)
        );
    }

    public function testInspectorWithInherited()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) {
                    return $inspector;
                },
                true
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);

        $this->assertMatchesRegularExpression(
            '/MethodInjector\\\\Mocked\\\\__MOCKED__MethodInjector__Test__Fixtures__TestClass_\d+__/',
            get_class($mock)
        );

        $this->assertInstanceOf(
            TestClass::class,
            $mock
        );
    }

    public function testInspectorWithExtendedAndInherited()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClassExtendedTestClass::class,
                function (Inspector $inspector) {
                    return $inspector;
                },
                true
            )
            ->patch();

        $mock = $test->createMock(TestClassExtendedTestClass::class);

        $this->assertMatchesRegularExpression(
            '/MethodInjector\\\\Mocked\\\\__MOCKED__MethodInjector__Test__Fixtures__TestClassExtendedTestClass_\d+__/',
            get_class($mock)
        );

        $this->assertInstanceOf(
            TestClass::class,
            $mock
        );

        $this->assertInstanceOf(
            TestClassExtendedTestClass::class,
            $mock
        );
    }

    public function testInspectorWithExtendedAndNotInherited()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestClassExtendedTestClass::class,
                function (Inspector $inspector) {
                    return $inspector;
                }
            )
            ->patch();

        $mock = $test->createMock(TestClassExtendedTestClass::class);

        $this->assertMatchesRegularExpression(
            '/MethodInjector\\\\Mocked\\\\__MOCKED__MethodInjector__Test__Fixtures__TestClassExtendedTestClass_\d+__/',
            get_class($mock)
        );

        $this->assertInstanceOf(
            TestClass::class,
            $mock
        );

        $this->assertNotInstanceOf(
            TestClassExtendedTestClass::class,
            $mock
        );
    }

    public function testInspectorWithImplementedAndInherited()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $test
            ->inspect(
                TestImplementedTestInterface::class,
                function (Inspector $inspector) {
                    return $inspector;
                },
                true
            )
            ->patch();

        $mock = $test->createMock(TestImplementedTestInterface::class);

        $this->assertMatchesRegularExpression(
            '/MethodInjector\\\\Mocked\\\\__MOCKED__MethodInjector__Test__Fixtures__TestImplementedTestInterface_\d+__/',
            get_class($mock)
        );

        $this->assertInstanceOf(
            TestImplementedTestInterface::class,
            $mock
        );

        $this->assertInstanceOf(
            TestInterface::class,
            $mock
        );
    }
}
