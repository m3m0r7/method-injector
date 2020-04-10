<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use MethodInjector\Helper\NodeBuilder;
use PhpParser\Node;

class InstanceReplacer extends AbstractReplacer
{
    public function validate(): bool
    {
        return $this->stmt->expr->expr instanceof Node\Expr\New_ &&
            implode('\\', $this->stmt->expr->expr->class->parts) === $this->from;
    }

    public function patchNode(): Node
    {
        /**
         * @var Node\Expr\New_ $new
         */
        $this->stmt->expr->expr->class = NodeBuilder::makeClassName(
            $this->to->value
        );

        return $this->stmt;
    }
}
