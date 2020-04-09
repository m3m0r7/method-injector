<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use PhpParser\Node;

abstract class AbstractReplacer implements ReplacerInterface
{
    protected $stmt;
    protected $from;
    protected $to;

    protected function __construct(Node $stmt, $from, $to)
    {
        $this->stmt = $stmt;
        $this->from = $from;
        $this->to = $to;
    }

    public static function factory(Node $stmt, $from, $to): ReplacerInterface
    {
        return new static($stmt, $from, $to);
    }

    abstract public function validate(): bool;

    abstract public function patchNode(): Node;
}
