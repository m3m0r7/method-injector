<?php declare(strict_types=1);
namespace MethodInjector;

use MethodInjector\Helper\NodeBuilder;
use MethodInjector\Traits\ReplacerAware;
use PhpParser\Node;

class Condition
{
    use ReplacerAware;

    protected $before = [];
    protected $after = [];

    /**
     * @param $from
     * @param $to
     * @return $this
     */
    public function replaceFunction($from, $to): self
    {
        return $this->replace(
            Inspector::FUNCTION,
            $from,
            $to
        );
    }

    /**
     * @param $from
     * @param $to
     * @return $this
     */
    public function replaceInstance($from, $to): self
    {
        return $this->replace(
            Inspector::INSTANCE,
            $from,
            $to
        );
    }

    /**
     * @param $from
     * @param $to
     * @return $this
     */
    public function replaceStaticCall($from, $to): self
    {
        return $this->replace(
            Inspector::STATIC_CALL,
            $from,
            $to
        );
    }

    /**
     * @param $process
     * @return $this
     */
    public function before(callable $process): self
    {
        $this->before[] = NodeBuilder::expressible(
            NodeBuilder::callable(
                $this->makeAnonymousFunctionEntryNode(
                    $process
                )
            )
        );
        return $this;
    }

    /**
     * @param $process
     * @return $this
     */
    public function after(callable $process): self
    {
        $this->after[] = NodeBuilder::expressible(
            NodeBuilder::callable(
                $this->makeAnonymousFunctionEntryNode(
                    $process
                )
            )
        );
        return $this;
    }

    /**
     * @param Node[] $arguments
     */
    public function getBeforeCollection(array $arguments): array
    {
        return $this->injectArgumentToCollection(
            $this->before,
            $arguments
        );
    }

    /**
     * @param Node[] $arguments
     */
    public function getAfterCollection(array $arguments): array
    {
        return $this->injectArgumentToCollection(
            $this->after,
            $arguments
        );
    }

    /**
     * @param $arguments
     */
    protected function injectArgumentToCollection(array $targets, $arguments)
    {
        // Reset argument
        foreach ($targets as $expression) {
            /**
             * @var Node\Stmt\Expression $expression
             */
            $expression->expr->args = [];
        }

        return array_reduce(
            $targets,
            static function ($carry, $expression) use ($arguments) {
                /**
                 * @var Node\Stmt\Expression $expression
                 */
                $expression->expr->args = array_merge(
                    $expression->expr->args,
                    $arguments
                );

                $carry[] = $expression;
                return $carry;
            },
            []
        );
    }

    protected function makeAnonymousFunctionEntryNode(callable $callable): Node
    {
        $entryNumber = AnonymousFunctionManager::add($callable);
        return NodeBuilder::factory()
            ->fromString(
                '\\MethodInjector\\AnonymousFunctionManager::get(' . $entryNumber . ')'
            );
    }
}
