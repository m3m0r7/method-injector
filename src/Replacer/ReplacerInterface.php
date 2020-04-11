<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use PhpParser\Node;

interface ReplacerInterface
{
    public static function factory(Node $stmt, $from, $to, array $aliases = []): ReplacerInterface;

    public function validate(): bool;

    public function patchNode(): Node;
}
