<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Helper\NodeBuilder;
use MethodInjector\Helper\PathResolver;
use PhpParser\Node;

class StaticCallReplacer extends AbstractReplacer
{
    public function validate(): bool
    {
        return (bool) $this->finder->find($this->finderCallback());
    }

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

    protected function finderCallback(): callable
    {
        return function ($node) {
            return $node instanceof Node\Expr\StaticCall
                && $this->pathResolver
                    ->contains(
                        PathResolver::toStringPath(
                            $node->class->parts
                        ),
                        $this->from
                    );
        };
    }
}
