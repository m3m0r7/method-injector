<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Helper\NodeBuilder;
use MethodInjector\Replacer\Traits\ReplacerStandard;
use PhpParser\Node;

class InstanceReplacer extends AbstractReplacer
{
    use ReplacerStandard;
    protected $targetExpr = Node\Expr\New_::class;

    public function patchNode(): Node
    {
        return $this
            ->finder
            ->patch(
                $this->finderCallback(),
                function (Node $node) {
                    $node->class = NodeBuilder::makeClassName(
                        $this->to->value
                    );
                }
            );
    }
}
