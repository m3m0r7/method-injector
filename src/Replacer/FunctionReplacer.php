<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use PhpParser\Node;

class FunctionReplacer extends AbstractReplacer
{
    public function validate(): bool
    {
        $expression = $this->stmt->expr ?? null;
        if (!($expression instanceof Node\Expr\FuncCall)) {
            return false;
        }
        return implode('\\', $expression->name->parts ?? []) === $this->from;
    }

    public function patchNode(): Node
    {
        /**
         * @var Node\Stmt\Expression $stmt
         * @var Node\Expr\FuncCall $expr
         */
        $stmt = $this->stmt;
        $expr = $stmt->expr;

        $originalAttribute = $expr->name->getAttributes();
        if (is_string($this->to)) {
            $expr->name->parts = '\\' . explode('\\', $this->to);
        } elseif ($this->to instanceof Node) {
            /**
             * @var Node\Expr\StaticCall $anonymous
             */
            $expr->name = $this->to;
        }
        return $stmt;
    }
}
