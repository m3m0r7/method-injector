<?php declare(strict_types=1);
namespace MethodInjector;

class AnonymousFunctionManager
{
    protected static $pool;

    public static function add(callable $function): int
    {
        static $counter = 0;
        static::$pool[$counter] = $function;
        return $counter++;
    }

    public static function get(int $number): callable
    {
        if (!isset(static::$pool[$number])) {
            throw new MethodInjectorException(
                'The anonymous function is not defined.'
            );
        }
        return static::$pool[$number];
    }
}
