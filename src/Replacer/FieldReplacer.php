<?php declare(strict_types=1);
namespace MethodInjector\Replacer;

use PhpParser\Node;

class FieldReplacer extends AbstractReplacer
{
    public function validate(): bool
    {
        return $this->stmt instanceof Node\Stmt\PropertyProperty &&
            $this->stmt->name->name === $this->from;
    }

    public function patchNode(): Node
    {
        /**
         * @var Node\Stmt\PropertyProperty $stmt
         */
        $stmt = $this->stmt;
        $stmt->default = $this->to;

        return $this->stmt;
    }
}
