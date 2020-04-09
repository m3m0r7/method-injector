<?php declare(strict_types=1);
namespace MethodInjector\Traits;

use MethodInjector\AnonymousFunctionManager;
use MethodInjector\CollectionFilter;
use MethodInjector\Helper\NodeBuilder;
use MethodInjector\MethodInjectorException;

trait ReplacerAware
{
    /**
     * @var array<>
     */
    protected $replaces = [];

    /**
     * @param $from
     * @param $to
     * @return $this
     */
    public function replace(int $replaceType, $from, $to): self
    {
        if (!is_string($to) && is_callable($to)) {
            // Add anonymous function into manager
            $functionEntryNumber = AnonymousFunctionManager::add($to);

            // make node.
            $to = NodeBuilder::factory()
                ->fromString(
                    '\\MethodInjector\\AnonymousFunctionManager::get(' . $functionEntryNumber . ')'
                );
        } elseif (is_scalar($to)) {
            $to = NodeBuilder::factory()
                ->fromScalar($to);
        } else {
            throw new MethodInjectorException(
                'Method injector is not supported.'
            );
        }
        $this->replaces[] = [$replaceType, $from, $to];
        return $this;
    }

    public function getCollection(array $filter = CollectionFilter::FILTER_NONE): array
    {
        return array_reduce(
            $this->replaces,
            static function ($carry, $item) use ($filter) {
                [$replaceType, $from, $to] = $item;
                if (in_array($replaceType, $filter, true)) {
                    $carry[] = $item;
                }
                return $carry;
            },
            []
        );
    }
}
