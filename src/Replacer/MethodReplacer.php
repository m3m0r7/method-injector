<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Helper\NodeBuilder;
use PhpParser\Node;

class MethodReplacer extends AbstractReplacer
{
    public function validate(): bool
    {
        return $this->stmt instanceof Node\Stmt\ClassMethod &&
            $this->stmt->name->name === $this->from;
    }

    public function patchNode(): Node
    {
        /**
         * @var Node\Stmt\ClassMethod $stmt
         */
        $stmt = $this->stmt;

        // Replace
        $stmt->stmts = [
            NodeBuilder::returnable(
                NodeBuilder::callable(
                    $this->to
                )
            ),
        ];

        return $this->stmt;
    }
}
