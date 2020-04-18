<?php declare(strict_types=1);
namespace MethodInjector\Test\Fixtures;

trait Trait1
{
    use Trait2;

    protected $trait1 = 'old value';
}
