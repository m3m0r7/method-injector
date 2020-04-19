<?php declare(strict_types=1);
namespace MethodInjector\Builder;

use MethodInjector\Condition;
use MethodInjector\Inspector;
use MethodInjector\MethodInjector;

class ConditionBuilder
{
    protected $classPath;
    protected $injectorClass;
    protected $methodInjector;
    protected $fieldReplacers = [];
    protected $constantReplacers = [];
    protected $methodReplacers = [];

    protected $functionReplacers = ['*' => []];
    protected $constFetchReplacers = ['*' => []];
    protected $instanceReplacers = ['*' => []];
    protected $staticCallReplacers = ['*' => []];

    protected $before = [];
    protected $after = [];

    protected $groups = ['*'];

    public static function factory(string $classPath, string $injectorClass = MethodInjector::class): self
    {
        return new static($classPath, $injectorClass);
    }

    protected function __construct(string $classPath, string $injectorClass)
    {
        $this->classPath = $classPath;
        $this->injectorClass = $injectorClass;
    }

    public function after($callback): self
    {
        $this->after[] = $callback;
        return $this;
    }

    public function before($callback): self
    {
        $this->before[] = $callback;
        return $this;
    }

    public function replaceField($from, $to): self
    {
        $this->fieldReplacers[] = [$from, $to];
        return $this;
    }

    public function replaceConstant($from, $to): self
    {
        $this->constantReplacers[] = [$from, $to];
        return $this;
    }

    public function replaceMethod($from, $to): self
    {
        $this->methodReplacers[] = [$from, $to];
        return $this;
    }

    public function replaceFunction($from, $to): self
    {
        $this->functionReplacers[$this->getCurrentGroup()][] = [$from, $to];
        return $this;
    }

    public function replaceConstFetch($from, $to): self
    {
        $this->constFetchReplacers[$this->getCurrentGroup()][] = [$from, $to];
        return $this;
    }

    public function replaceInstances($from, $to): self
    {
        $this->instanceReplacers[$this->getCurrentGroup()][] = [$from, $to];
        return $this;
    }

    public function replaceStaticCall($from, $to): self
    {
        $this->staticCallReplacers[$this->getCurrentGroup()][] = [$from, $to];
        return $this;
    }

    public function group(string $group): self
    {
        $this->groups[] = $group;

        $this->functionReplacers[$group] = $this->functionReplacers[$group] ?? [];
        $this->constFetchReplacers[$group] = $this->constFetchReplacers[$group] ?? [];
        $this->instanceReplacers[$group] = $this->instanceReplacers[$group] ?? [];
        $this->staticCallReplacers[$group] = $this->staticCallReplacers[$group] ?? [];

        return $this;
    }

    public function make(): MethodInjector
    {
        /**
         * @var MethodInjector $methodInjector
         */
        $methodInjector = ($this->injectorClass)::factory();
        $methodInjector
            ->inspect(
                $this->classPath,
                function (Inspector $inspector) {
                    $inspector
                        ->enableParentMock(true)
                        ->enableTraitsMock(true);

                    foreach ($this->constantReplacers as [$from, $to]) {
                        $inspector->replaceConstant($from, $to);
                    }

                    foreach ($this->methodReplacers as [$from, $to]) {
                        $inspector->replaceMethod($from, $to);
                    }

                    foreach ($this->fieldReplacers as [$from, $to]) {
                        $inspector->replaceField($from, $to);
                    }

                    foreach ($this->groups as $group) {
                        $inspector->methodGroup(
                            $group,
                            function (Condition $condition) use ($group) {
                                foreach (($this->functionReplacers[$group] ?? []) as [$from, $to]) {
                                    $condition->replaceFunction($from, $to);
                                }
                                foreach (($this->constFetchReplacers[$group] ?? []) as [$from, $to]) {
                                    $condition->replaceConstantFetch($from, $to);
                                }
                                foreach (($this->staticCallReplacers[$group] ?? []) as [$from, $to]) {
                                    $condition->replaceConstantFetch($from, $to);
                                }
                                foreach (($this->instanceReplacers[$group] ?? []) as [$from, $to]) {
                                    $condition->replaceInstance($from, $to);
                                }

                                foreach ($this->after as $callback) {
                                    $condition->after($callback);
                                }

                                foreach ($this->before as $callback) {
                                    $condition->before($callback);
                                }
                                return $condition;
                            },
                        );
                    }

                    return $inspector;
                },
                true
            );
        return $methodInjector;
    }

    protected function getCurrentGroup()
    {
        return $this->groups[count($this->groups) - 1];
    }
}
