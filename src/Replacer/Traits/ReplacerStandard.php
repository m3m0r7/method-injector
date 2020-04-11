<?php declare(strict_types=1);
namespace MethodInjector\Replacer\Traits;

use MethodInjector\Helper\NodeFinder;
use MethodInjector\Helper\PathResolver;
use MethodInjector\MethodInjectorException;
use PhpParser\Node;

/**
 * @property NodeFinder $finder
 * @property PathResolver $pathResolver
 * @property string $targetExpr
 * @property Node|string $from
 */
trait ReplacerStandard
{
    public function validate(): bool
    {
        return (bool) $this
            ->finder
            ->find(
                $this->finderCallback()
            );
    }

    protected function getNameByNode(Node $node)
    {
        $name = null;
        if (property_exists($node, 'class')) {
            if ($node->class instanceof Node\Name) {
                return PathResolver::toStringPath(
                    $node->class->parts
                );
            }
        }
        if ($node->name instanceof Node\Identifier) {
            return $node->name->name;
        }

        throw new MethodInjectorException(
            'Failed to get naming node'
        );
    }

    protected function finderCallback(): callable
    {
        return function (Node $node) {
            return $node instanceof $this->targetExpr
                && $this->pathResolver
                    ->contains(
                        $this->getNameByNode($node),
                        $this->from
                    );
        };
    }
}
