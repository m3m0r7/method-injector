<?php declare(strict_types=1);
namespace MethodInjector;

use MethodInjector\Helper\NodeBuilder;
use MethodInjector\Traits\ReplacerAware;
use PhpParser\Node;

class Condition
{
    use ReplacerAware;

    protected $prepends = [];
    protected $appends = [];

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
     * @param $process
     * @return $this
     */
    public function prepend(callable $process): self
    {
        $this->prepends[] = NodeBuilder::expressible(
            NodeBuilder::callable(
                $this->makeAnonymousFunctionEntryString(
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
    public function append(callable $process): self
    {
        $this->appends[] = NodeBuilder::expressible(
            NodeBuilder::callable(
                $this->makeAnonymousFunctionEntryString(
                    $process
                )
            )
        );
        return $this;
    }

    /**
     * @param $arguments
     */
    public function getPrependsCollection($arguments): array
    {
        return $this->injectArgumentToCollection(
            $this->prepends,
            $arguments
        );
    }

    /**
     * @param $arguments
     */
    public function getAppendsCollection($arguments): array
    {
        return $this->injectArgumentToCollection(
            $this->appends,
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

    /**
     * @return string
     */
    protected function makeAnonymousFunctionEntryString(callable $callable): Node
    {
        $entryNumber = AnonymousFunctionManager::add($callable);
        return NodeBuilder::factory()
            ->fromString(
                '\\MethodInjector\\AnonymousFunctionManager::get(' . $entryNumber . ')'
            );
    }
}
