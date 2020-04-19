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

    public function find(callable $callback): bool
    {
        return (bool) $this->recursiveFind(
            $this->node,
            $callback
        );
    }

    public function patch(callable $finderCallback, callable $patchCallback): Node
    {
        $nodes = $this->recursiveFind($this->node, $finderCallback);
        foreach ($nodes as $node) {
            $patchCallback($node);
        }
        return $this->node;
    }

    protected function recursiveFind(Node &$node, callable $callback): array
    {
        if ((bool) $callback($node)) {
            return [$node];
        }

        if (property_exists($node, 'args')) {
            return $this->processMulti(
                $node->args,
                $callback
            );
        }
        if (property_exists($node, 'exprs')) {
            return $this->processMulti(
                $node->exprs,
                $callback
            );
        }
        if (property_exists($node, 'stmts')) {
            return $this->processMulti(
                $node->stmts,
                $callback
            );
        }

        if (property_exists($node, 'expr')) {
            return $this->recursiveFind($node->expr, $callback);
        }
        return [];
    }

    protected function processMulti(array $nodes, callable $callback): array
    {
        $result = [];
        foreach ($nodes as $arg) {
            $result = array_merge(
                $result,
                $this->recursiveFind($arg, $callback)
            );
        }
        return $result;
    }
}
