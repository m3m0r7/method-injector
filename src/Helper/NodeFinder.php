<?php declare(strict_types=1);
namespace MethodInjector\Helper;

use PhpParser\Node;

class NodeFinder
{
    protected $node;

    protected function __construct(Node &$node)
    {
        $this->node = $node;
    }

    public static function factory(Node &$node): NodeFinder
    {
        return new static($node);
    }

    public function find(callable $callback): ?Node
    {
        return $this->recursiveFind($this->node, $callback);
    }

    public function patch(callable $finderCallback, callable $patchCallback): Node
    {
        $patchCallback(
            $this->find($finderCallback)
        );
        return $this->node;
    }

    protected function recursiveFind(Node &$node, callable $callback): ?Node
    {
        if ((bool) $callback($node)) {
            return $node;
        }
        if (property_exists($node, 'expr')) {
            return $this->recursiveFind($node->expr, $callback);
        }
        return null;
    }
}
