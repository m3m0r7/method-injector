<?php
namespace MethodInjector\Test\Cases\Inspector;

use MethodInjector\Inspector;
use MethodInjector\MethodInjectorInterface;
use MethodInjector\Test\Fixtures\AbstractTestClass;

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
                        ->enalbeTraitsMock(true);
                },
                true
            )
            ->patch();

        $mock = $test->createMock(\MethodInjector\Test\Fixtures\ExtendedClassTest::class);
        $reflection = new \ReflectionClass($mock);

        // Check implements
        $interfaces = $reflection->getInterfaces();
        $this->assertArrayHasKey(MethodInjectorInterface::class, $interfaces);
        $this->assertCount(1, $interfaces);

        // Check extends
        $this->assertTrue(
            $reflection->isSubclassOf(AbstractTestClass::class)
        );

        // Check fields
        $fields = $reflection->getProperties();

        $this->assertCount(8, $fields);

        foreach ($fields as $field) {
            $this->assertTrue($field->isProtected());
            $field->setAccessible(true);
        }

        $this->assertSame('extendedProperty2', $fields[0]->getName());
        $this->assertSame('extendedProperty1', $fields[1]->getName());
        $this->assertSame('trait3', $fields[2]->getName());
        $this->assertSame('trait1', $fields[3]->getName());
        $this->assertSame('trait2_1', $fields[4]->getName());
        $this->assertSame('trait2_2', $fields[5]->getName());
        $this->assertSame('trait2_3', $fields[6]->getName());
        $this->assertSame('property', $fields[7]->getName());

        $this->assertSame('new value', $fields[0]->getValue($mock));
        $this->assertSame('old value', $fields[1]->getValue($mock));
        $this->assertSame('old value', $fields[2]->getValue($mock));
        $this->assertSame('old value', $fields[3]->getValue($mock));
        $this->assertSame('old value', $fields[4]->getValue($mock));
        $this->assertSame('old value', $fields[5]->getValue($mock));
        $this->assertSame('old value', $fields[6]->getValue($mock));
        $this->assertSame('old value', $fields[7]->getValue($mock));

        // Check constants
        $constants = $reflection->getConstants();

        $this->assertCount(2, $constants);
        $this->assertSame('A', $constants['A']);
        $this->assertSame('B', $constants['B']);

        // Check method
        $this->assertCount(1, $reflection->getMethods());
        $this->assertInstanceOf(
            \ReflectionMethod::class,
            $reflection->getMethod('testMethod')
        );
    }
}
