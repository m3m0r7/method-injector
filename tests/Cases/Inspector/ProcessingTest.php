<?php
namespace MethodInjector\Tests\Cases\Inspector;

use MethodInjector\Condition;
use MethodInjector\Inspector;
use MethodInjector\MethodInjectorInterface;
use MethodInjector\Test\Fixtures\TestClass;

class ProcessingTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessing()
    {
        $test = \MethodInjector\MethodInjector::factory();
        $counter = 0;
        $test
            ->inspect(
                TestClass::class,
                function (Inspector $inspector) use (&$counter) {
                    return $inspector
                        ->methodGroup(
                            'test',
                            function (Condition $condition) use (&$counter) {
                                return $condition
                                    ->before(
                                        function ($class) use (&$counter) {
                                            $this->assertInstanceOf(
                                                MethodInjectorInterface::class,
                                                $class
                                            );
                                            $this->assertSame(0, $counter);
                                            $counter++;
                                        }
                                    )
                                    ->after(
                                        function ($class) use (&$counter) {
                                            $this->assertInstanceOf(
                                                MethodInjectorInterface::class,
                                                $class
                                            );
                                            $this->assertSame(1, $counter);
                                            $counter++;
                                        }
                                    );
                            }
                        );
                }
            )
            ->patch();

        $mock = $test->createMock(TestClass::class);
        $mock->test();
        $this->assertSame(2, $counter);
    }
}
