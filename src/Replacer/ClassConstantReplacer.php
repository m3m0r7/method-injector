<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use PhpParser\Node;

class ClassConstantReplacer extends AbstractReplacer
{
    public function validate(): bool
    {
        return $this->stmt instanceof Node\Const_ &&
            $this->stmt->name->name === $this->from;
    }

    public function patchNode(): Node
    {
        /**
         * @var Node\Const_ $stmt
         */
        $stmt = $this->stmt;
        $stmt->value = $this->to;
        return $stmt;
    }
}
