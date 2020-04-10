<?php declare(strict_types=1);
namespace MethodInjector;

use MethodInjector\Helper\NodeBuilder;
use MethodInjector\Replacer\ConstantReplacer;
use MethodInjector\Replacer\FieldReplacer;
use MethodInjector\Replacer\FunctionReplacer;
use MethodInjector\Replacer\InstanceReplacer;
use MethodInjector\Replacer\MethodReplacer;
use MethodInjector\Replacer\ReplacerInterface;
use MethodInjector\Traits\ReplacerAware;
use PhpParser\Node;
use PhpParser\ParserFactory;

class Inspector
{
    use ReplacerAware;

    const FUNCTION = 1;
    const METHOD = 2;
    const CONSTANT = 3;
    const FIELD = 4;
    const INSTANCE = 5;

    /**
     * @var array<null|string>
     */
    protected $replacers = [];

    /**
     * @var \PhpParser\Parser
     */
    protected $parser;

    /**
     * @var array<Condition>
     */
    protected $conditions = [];

    /**
     * @var string
     */
    protected $className;

    /**
     * @var null|Node\Stmt[]
     */
    protected $ast;

    /**
     * @var null|Node
     */
    protected $mockedNode;

    /**
     * @var array
     */
    protected $attributes;

    public static function factory(array $args, string $className, bool $inheritOriginalClass = false)
    {
        return new static(
            $args,
            $className,
            $inheritOriginalClass
        );
    }

    /**
     * @param $from
     * @param $to
     * @return $this
     */
    public function replaceField($from, $to): self
    {
        return $this->replace(
            Inspector::FIELD,
            $from,
            $to
        );
    }

    /**
     * @param $from
     * @param $to
     * @return $this
     */
    public function replaceMethod($from, $to): self
    {
        return $this->replace(
            Inspector::METHOD,
            $from,
            $to
        );
    }

    /**
     * @param $from
     * @param $to
     * @return $this
     */
    public function replaceConstant($from, $to): self
    {
        return $this->replace(
            Inspector::CONSTANT,
            $from,
            $to
        );
    }

    /**
     * @return $this
     */
    public function addReplacer(int $type, string $class): self
    {
        $this->replacers[] = [$type, $class];
        return $this;
    }

    /**
     * @throws \ReflectionException
     */
    protected function __construct(array $args, string $className, bool $inheritOriginalClass = false)
    {
        if (isset($args['replacer'])) {
            $this->replacers = $args['replacer'];
        } else {
            // Default replacer
            $this->replacers = [
                [self::FUNCTION, FunctionReplacer::class],
                [self::METHOD, MethodReplacer::class],
                [self::CONSTANT, ConstantReplacer::class],
                [self::FIELD, FieldReplacer::class],
                [self::INSTANCE, InstanceReplacer::class],
            ];
        }

        $this->parser = (new ParserFactory())
            ->create(ParserFactory::PREFER_PHP7);

        $reflection = new \ReflectionClass($className);
        $filePath = file_get_contents(
            $reflection->getFileName()
        );

        $this->className = $className;
        $this->ast = $this->parser
            ->parse($filePath);

        $this->attributes = [
            'inheritance' => $inheritOriginalClass,
            'extends' => null,
            'implements' => [],
        ];
    }

    public function getExtended(): ?string
    {
        return $this->attributes['extends'] ?? null;
    }

    public function getImplemented(): array
    {
        return $this->attributes['implements'] ?? null;
    }

    /**
     * @return $this
     */
    public function methodGroup(string $condition, callable $callback): self
    {
        $this->conditions[$condition] = $callback(new Condition());
        return $this;
    }

    public function patch()
    {
        foreach ($this->ast as $inspect) {
            foreach ($this->recursiveNode($inspect) as [$node, $namespace]) {
                $this->processInspectedNode(
                    $this->className,
                    $node,
                    $namespace
                );
            }
        }
    }

    public function getMockedNode(): ?Node
    {
        return $this->mockedNode;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMockedClassName(): string
    {
        return $this->makeMockedClassName(
            $this->className
        );
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param $node
     * @param $namespace
     * @return $this
     */
    protected function processInspectedNode(
        string $className,
        $node,
        $namespace
    ): void {
        $conditions = $this->conditions;

        /**
         * @var Node $node
         */
        if (!($node instanceof Node\Stmt\Class_)) {
            return;
        }

        $classPathAndName = implode('\\', $namespace) . '\\' . $node->name->name;
        if ($className !== $classPathAndName) {
            return;
        }

        foreach ($node->getProperties() as &$property) {
            foreach ($property->props as &$prop) {
                $this->patchCollectionNode(
                    $this->getCollection([static::FIELD]),
                    $prop
                );
            }
        }

        foreach ($node->getConstants() as &$constant) {
            foreach ($constant->consts as &$const) {
                $this->patchCollectionNode(
                    $this->getCollection([static::CONSTANT]),
                    $const
                );
            }
        }

        foreach ($node->getMethods() as &$method) {
            $this->patchCollectionNode(
                $this->getCollection([static::METHOD]),
                $method
            );

            foreach ($conditions as $name => $condition) {
                if ($name !== '*' && $method->name->name !== $name) {
                    continue;
                }
                foreach ($method->stmts as &$stmt) {
                    /**
                     * @var Condition $condition
                     */
                    $this->patchCollectionNode(
                        $condition->getCollection(CollectionFilter::FILTER_METHOD_REPLACER),
                        $stmt
                    );
                }

                // add collections
                $method->stmts = array_merge(
                    $condition->getBeforeCollection(
                        NodeBuilder::makeArguments(...[
                            NodeBuilder::variable('this'),
                            NodeBuilder::magicConstant('__METHOD__'),
                        ])
                    ),
                    [NodeBuilder::deferrable(
                        $method->stmts,
                        $condition->getAfterCollection(
                            NodeBuilder::makeArguments(...[
                                NodeBuilder::variable('this'),
                                NodeBuilder::magicConstant('__METHOD__'),
                            ])
                        )
                    )]
                );
            }
        }

        $inspects = null;
        $this->attributes['extends'] = ($node->extends->parts ?? null)
            ? $this->combinePath(
                $namespace,
                $node->extends->parts
            )
            : null;

        $this->attributes['implements'] = array_map(
            function ($implement) use ($namespace) {
                return $this->combinePath(
                    $namespace,
                    $implement->parts
                );
            },
            $node->implements
        );

        $this->mockedNode = $node;
    }

    /**
     * @param $from
     * @param $to
     */
    protected function patchNode(Node $stmt, string $replacerClass, $from, $to): Node
    {
        /**
         * @var ReplacerInterface $replacerClass
         */
        $replacer = $replacerClass::factory($stmt, $from, $to);
        if (!($replacer instanceof ReplacerInterface)) {
            throw new MethodInjectorException(
                '`' . $replacerClass . '` is not implementing ReplacerInterface.'
            );
        }
        if (!$replacer->validate()) {
            return $stmt;
        }

        return $replacer
            ->patchNode();
    }

    protected function patchCollectionNode(array $collection, Node &$node): void
    {
        foreach ($collection as [$replaceType, $from, $to]) {
            foreach ($this->replacers as [$type, $replacerClass]) {
                if ($type !== $replaceType) {
                    continue;
                }
                if ($replacerClass === null) {
                    throw new MethodInjectorException('Not supported replace type.');
                }
                $node = $this->patchNode($node, $replacerClass, $from, $to);
            }
        }
    }

    protected function recursiveNode(Node $node, array $namespace = []): array
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $namespace = $node->name->parts;
            $nodes = [];
            foreach ($node->stmts as $stmt) {
                $nodes = array_merge(
                    $nodes,
                    $this->recursiveNode(
                        $stmt,
                        $namespace
                    )
                );
            }
            return $nodes;
        }
        return [[$node, $namespace]];
    }

    protected function makeMockedClassName(string $originalClassName): string
    {
        static $counterOfClassGenerated = 0;
        return '__MOCKED__' . str_replace('\\', '__', $originalClassName) . '_'
            . ($counterOfClassGenerated++) . '__';
    }

    protected function combinePath(array $namespace, array $names): string
    {
        return '\\' . implode(
            '\\',
            array_merge(
                $namespace,
                $names
            )
        );
    }
}
