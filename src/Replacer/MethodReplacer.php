<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Helper\NodeBuilder;
use MethodInjector\Replacer\Traits\ReplacerStandard;
use PhpParser\Node;

class MethodReplacer extends AbstractReplacer
{
    use ReplacerStandard;
    protected $targetExpr = Node\Stmt\ClassMethod::class;

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
