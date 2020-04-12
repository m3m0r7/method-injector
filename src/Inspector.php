<?php declare(strict_types=1);
namespace MethodInjector;

use MethodInjector\Helper\NodeBuilder;
use MethodInjector\Replacer\ClassConstantReplacer;
use MethodInjector\Replacer\ConstantFetchReplacer;
use MethodInjector\Replacer\FieldReplacer;
use MethodInjector\Replacer\FunctionReplacer;
use MethodInjector\Replacer\InstanceReplacer;
use MethodInjector\Replacer\MethodReplacer;
use MethodInjector\Replacer\ReplacerInterface;
use MethodInjector\Replacer\StaticCallReplacer;
use MethodInjector\Traits\ReplacerAware;
use PhpParser\Node;
use PhpParser\ParserFactory;

class Inspector
{
    use ReplacerAware;

    const FUNCTION = 1;
    const METHOD = 2;
    const CLASS_CONSTANT = 3;
    const FIELD = 4;
    const INSTANCE = 5;
    const STATIC_CALL = 6;
    const CONSTANT_FETCH = 7;

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
    protected $namespace;

    /**
     * @var array
     */
    protected $attributes;

    protected $enableParentMock = false;

    protected $expandTrait = false;

    protected $inheritOriginalClass = false;

    protected $args = [];

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
            Inspector::CLASS_CONSTANT,
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
        static $parser;

        if (!$this->validateUserDefinedClass($className)) {
            throw new MethodInjectorException(
                'The specified class is not a user defined.'
            );
        }

        $this->args = $args;
        $this->inheritOriginalClass = $inheritOriginalClass;

        if (isset($args['replacer'])) {
            $this->replacers = $args['replacer'];
        } else {
            // Default replacer
            $this->replacers = [
                [self::FUNCTION, FunctionReplacer::class],
                [self::METHOD, MethodReplacer::class],
                [self::CLASS_CONSTANT, ClassConstantReplacer::class],
                [self::FIELD, FieldReplacer::class],
                [self::INSTANCE, InstanceReplacer::class],
                [self::STATIC_CALL, StaticCallReplacer::class],
                [self::CONSTANT_FETCH, ConstantFetchReplacer::class],
            ];
        }

        $this->parser = $parser ?? (new ParserFactory())
            ->create(ParserFactory::PREFER_PHP7);

        $reflection = new \ReflectionClass($className);

        if ($reflection->getFileName() === false) {
            throw new MethodInjectorException(
                'Cannot get declared file. The MethodInjector cannot create a mock with built-in classes.'
            );
        }

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
        $this->conditions[$condition] = $callback;
        return $this;
    }

    public function enableParentMock(bool $which): self
    {
        $this->enableParentMock = $which;
        return $this;
    }

    public function expandTraits(bool $which): self
    {
        $this->expandTrait = $which;
        return $this;
    }

    public function patch()
    {
        foreach ($this->ast as $inspect) {
            $recursiveNodes = $this->recursiveNode($inspect);
            $aliases = [];

            // Hoisting uses
            foreach ($recursiveNodes as $node) {
                $aliases = array_merge(
                    $aliases,
                    $this->processInspectedAliases($node)
                );
            }
            foreach ($recursiveNodes as $node) {
                $this->processInspectedNode(
                    $this->className,
                    $node,
                    $aliases
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
     */
    protected function processInspectedAliases($node): array
    {
        if (!($node instanceof Node\Stmt\Use_)) {
            return [];
        }
        return $node->uses;
    }

    /**
     * @param $node
     */
    protected function processInspectedNode(
        string $className,
        $node,
        array $aliases = []
    ): void {
        $conditions = $this->conditions;

        /**
         * @var Node $node
         */
        if (!($node instanceof Node\Stmt\Class_)) {
            return;
        }

        $classPathAndName = $this->combinePath(
            $this->namespace,
            [$node->name->name]
        );

        if ($className !== ltrim($classPathAndName, '\\')) {
            return;
        }

        // if enable parent mock?
        if ($this->enableParentMock && $node->extends !== null) {
            var_dump($this->enableParentMock, $node->extends);

            $extendedClassPath = $this->combinePath(
                $this->namespace,
                $node->extends->parts
            );

            $result = $this->getMethodsOfExtendedClasses(
                $extendedClassPath
            );

//            var_dump($result);
        }

        foreach ($node->getProperties() as &$property) {
            foreach ($property->props as &$prop) {
                $this->patchCollectionNode(
                    $this->getCollection([static::FIELD]),
                    $prop,
                    $aliases
                );
            }
        }

        foreach ($node->getConstants() as &$constant) {
            foreach ($constant->consts as &$const) {
                $this->patchCollectionNode(
                    $this->getCollection([static::CLASS_CONSTANT]),
                    $const,
                    $aliases
                );
            }
        }

        foreach ($node->getMethods() as &$method) {
            $this->patchCollectionNode(
                $this->getCollection([static::METHOD]),
                $method,
                $aliases
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
                        $condition->getCollection(
                            CollectionFilter::FILTER_METHOD_REPLACER
                        ),
                        $stmt,
                        $aliases
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
                $this->namespace,
                $node->extends->parts
            )
            : null;

        $this->attributes['implements'] = array_map(
            function ($implement) {
                return $this->combinePath(
                    $this->namespace,
                    $implement->parts
                );
            },
            $node->implements
        );

        $this->mockedNode = $node;
    }

    public function getNamespace(): ?string
    {
        if (empty($this->namespace)) {
            return null;
        }
        return implode('\\', $this->namespace);
    }

    /**
     * @param $from
     * @param $to
     */
    protected function patchNode(Node $stmt, string $replacerClass, $from, $to, array $aliases = []): Node
    {
        /**
         * @var ReplacerInterface $replacerClass
         */
        $replacer = $replacerClass::factory($stmt, $from, $to, $this->namespace, $aliases);
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

    protected function patchCollectionNode(array $collection, Node &$node, array $aliases = []): void
    {
        foreach ($collection as [$replaceType, $from, $to]) {
            foreach ($this->replacers as [$type, $replacerClass]) {
                if ($type !== $replaceType) {
                    continue;
                }
                if ($replacerClass === null) {
                    throw new MethodInjectorException('Not supported replace type.');
                }
                $node = $this->patchNode(
                    $node,
                    $replacerClass,
                    $from,
                    $to,
                    $aliases
                );
            }
        }
    }

    protected function getMethodsOfExtendedClasses(string $class)
    {
        $inspector = static::factory(
            $this->args,
            ltrim($class, '\\'),
            $this->inheritOriginalClass
        );
        foreach ($this->conditions as $name => $condition) {
            $inspector->methodGroup(
                $name,
                $condition
            );
        }
        $inspector
            ->enableParentMock(true)
            ->expandTraits(true)
            ->patch();

        return $inspector->mockedNode;
    }

    protected function recursiveNode(Node $node): array
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespace = $node->name->parts;
            $nodes = [];
            foreach ($node->stmts as $stmt) {
                $nodes = array_merge(
                    $nodes,
                    $this->recursiveNode($stmt)
                );
            }
            return $nodes;
        }
        return [$node];
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

    protected function validateUserDefinedClass(string $className): bool
    {
        return class_exists(
            $className
        );
    }
}
