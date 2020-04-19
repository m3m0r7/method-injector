<?php declare(strict_types=1);
namespace MethodInjector\Test\Builder;

use MethodInjector\Builder\ConditionBuilder;
use MethodInjector\Test\Fixtures\TestClass;

class BuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testBuilder()
    {
        $builder = ConditionBuilder::factory(TestClass::class)
            ->group('test')
            ->replaceFunction(
                'date',
                function (...$args) {
                    return '9999-99-99';
                }
            )
            ->make()
            ->patch();

        $test = $builder->createMock(TestClass::class);

        $this->assertSame(
            '9999-99-99',
            $test->test()
        );
    }
}
