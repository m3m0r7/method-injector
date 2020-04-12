<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Replacer\Traits\ReplacerStandard;
use PhpParser\Node;

class FunctionReplacer extends AbstractReplacer
{
    use ReplacerStandard;
    protected $targetExpr = Node\Expr\FuncCall::class;

    public function patchNode(): Node
    {
        return $this
            ->finder
            ->patch(
                $this->finderCallback(),
                function (Node\Expr\FuncCall $node) {
                    $node->name = $this->to;
                }
            );
    }
}
