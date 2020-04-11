<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Helper\NodeFinder;
use PhpParser\Node;

abstract class AbstractReplacer implements ReplacerInterface
{
    /**
     * @var Node
     */
    protected $stmt;

    /**
     * @var
     */
    protected $from;

    /**
     * @var
     */
    protected $to;

    /**
     * @var NodeFinder
     */
    protected $finder;

    /**
     * @var Node\Stmt\Use_[]
     */
    protected $aliases = [];

    /**
     * AbstractReplacer constructor.
     * @param $from
     * @param $to
     * @param Node\Stmt\Use_[] $aliases
     */
    protected function __construct(Node $stmt, $from, $to, array $aliases = [])
    {
        $this->stmt = $stmt;
        $this->from = $from;
        $this->to = $to;
        $this->aliases = $aliases;
        $this->finder = NodeFinder::factory($stmt);
    }

    /**
     * @param $from
     * @param $to
     */
    public static function factory(Node $stmt, $from, $to, array $aliases = []): ReplacerInterface
    {
        return new static($stmt, $from, $to, $aliases);
    }

    abstract public function validate(): bool;

    abstract public function patchNode(): Node;
}
