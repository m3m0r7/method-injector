<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Helper\PathResolver;
use PhpParser\Node;

class FunctionReplacer extends AbstractReplacer
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
                function (Node\Expr\FuncCall $node) {
                    $node->name = $this->to;
                }
            );
    }

    protected function finderCallback(): callable
    {
        return function ($node) {
            return $node instanceof Node\Expr\FuncCall
                && $this->pathResolver
                    ->contains(
                        PathResolver::toStringPath(
                            $node->name->parts
                        ),
                        $this->from
                    );
        };
    }
}
