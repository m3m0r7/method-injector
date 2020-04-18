<?php declare(strict_types=1);
namespace MethodInjector\Helper;

use PhpParser\Node;

class NodeFilter
{
    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public static function filterMethods(array $nodes): array
    {
        $collection = [];
        foreach ($nodes as $node) {
            if (!($node instanceof Node\Stmt\ClassMethod)) {
                continue;
            }
            $collection[$node->name->name] = $node;
        }
        return array_values(
            $collection
        );
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public static function filterFields(array $nodes): array
    {
        $collection = [];
        foreach ($nodes as $node) {
            $flags = (string) $node->flags;
            if (!isset($collection[$flags])) {
                $collection[$flags] = [];
            }
            foreach ($node->props as $field) {
                if (!($field instanceof Node\Stmt\PropertyProperty)) {
                    continue;
                }
                $collection[$flags][$field->name->name] = $field;
            }
        }

        $newCollection = [];
        foreach ($collection as $flags => $fields) {
            $newCollection[] = new Node\Stmt\Property(
                $flags,
                $fields
            );
        }

        return $newCollection;
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public static function filterConstants(array $nodes): array
    {
        $collection = [];
        foreach ($nodes as $node) {
            $flags = (string) $node->flags;
            if (!isset($collection[$flags])) {
                $collection[$flags] = [];
            }
            foreach ($node->consts as $const) {
                if (!($const instanceof Node\Const_)) {
                    continue;
                }
                $collection[$flags][$const->name->name] = $const;
            }
        }

        $newCollection = [];
        foreach ($collection as $flags => $consts) {
            $newCollection[] = new Node\Stmt\ClassConst(
                $consts,
                $flags
            );
        }

        return $newCollection;
    }
}
