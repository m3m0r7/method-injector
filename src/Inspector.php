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
    protected $namespace = [];

    /**
     * @var array
     */
    protected $attributes;

    protected $enableParentMock = false;

    protected $enableTraitsMock = true;

    protected $inheritOriginalClass = false;

    protected $args = [];

    protected $isClass = false;
    protected $isTrait = false;
    protected $isBuiltIn = false;

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

        $this->assignClassType($className);

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
            $this->isBuiltIn = true;
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

    public function enableTraitsMock(bool $which): self
    {
        $this->enableTraitsMock = $which;
        return $this;
    }

    public function patch(): self
    {
        if ($this->isBuiltIn) {
            throw new MethodInjectorException(
                'Cannot get declared file. The MethodInjector cannot create a mock with built-in classes.'
            );
        }
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

        return $this;
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
        if (!($node instanceof Node\Stmt\Class_)
            && !($node instanceof Node\Stmt\Trait_)
        ) {
            return;
        }

        // Remove attributes
        foreach ($node->stmts as $stmt) {
            /**
             * @var Node $stmt
             */
            $stmt->setAttributes([]);
        }

        $classPathAndName = $this->combinePath(
            $this->namespace,
            [$node->name->name]
        );

        if ($className !== ltrim($classPathAndName, '\\')) {
            return;
        }

        // if enable parent mock?

        /**
         * @var Inspector[] $extendedClasses
         */
        $extendedClasses = [];

        if ($this->enableTraitsMock) {
            $extendedClasses = array_merge(
                $extendedClasses,
                $this->getTraitInspectorsInClassNode(
                    $node
                )
            );
        }

        if ($this->enableParentMock && $node->extends !== null) {
            if ($this->isClass) {
                $extendedClassPath = $this->combinePath(
                    $this->namespace,
                    $node->extends->parts
                );

                $extendedClasses = array_merge(
                    $extendedClasses,
                    $this->getExtendedClasses(
                        $extendedClassPath
                    )
                );
            }
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

                // Make a condition.
                $condition = $condition(new Condition());
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

        $this->mockedNode = $node;

        $this->mergeInspectors(
            $extendedClasses,
            $node
        );

        if ($this->isTrait) {
            return;
        }

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
    }

    public function getNamespace(): ?string
    {
        if (empty($this->namespace)) {
            return null;
        }
        return implode('\\', $this->namespace);
    }

    public function isTrait(): bool
    {
        return $this->isTrait;
    }

    public function isClass(): bool
    {
        return $this->isClass;
    }

    public function isBuiltIn(): bool
    {
        return $this->isBuiltIn;
    }

    /**
     * @param Node\Stmt\Class_|Node\Stmt\Trait_ $node
     * @return Inspector[]
     */
    protected function getTraitInspectorsInClassNode(Node $node): array
    {
        $extendedClasses = [];
        $traitUses = array_reduce(
            $node->stmts,
            static function ($carry, $stmt) {
                if ($stmt instanceof Node\Stmt\TraitUse) {
                    $carry[] = $stmt;
                }
                return $carry;
            },
            []
        );

        foreach ($traitUses as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $extendedClasses = array_merge(
                    $extendedClasses,
                    $this->getExtendedClasses(
                        $this->combinePath(
                            $this->namespace,
                            $trait->parts
                        )
                    )
                );
            }
        }
        return $extendedClasses;
    }

    /**
     * @param Inspector[] $inspectors
     */
    protected function mergeInspectors(array $inspectors, Node &$node): void
    {
        // Prepend method nodes.
        foreach ($inspectors as $extendedClassInspector) {
            /**
             * @var Node|Node\Stmt\ClassMethod[]|Node\Stmt\Property[] $mockedNode
             */
            $mockedNode = $extendedClassInspector->getMockedNode();

            foreach ($mockedNode->getMethods() as $method) {
                if ($this->containsClassMethod($method, $node->getMethods())) {
                    continue;
                }
                array_unshift(
                    $node->stmts,
                    $method
                );
            }

            foreach ($mockedNode->getProperties() as $fields) {
                $mergeFields = [];
                foreach ($fields->props as $field) {
                    if ($this->containsField($field, $node->getProperties())) {
                        continue;
                    }

                    $mergeFields[] = $field;
                }
                if (empty($mergeFields)) {
                    continue;
                }
                array_unshift(
                    $node->stmts,
                    NodeBuilder::makeClassFieldsGroup(
                        $fields->flags,
                        ...$mergeFields
                    )
                );
            }

            if ($extendedClassInspector->isClass()) {
                foreach ($mockedNode->getConstants() as $consts) {
                    $mergeConstants = [];
                    foreach ($consts->consts as $const) {
                        if ($this->containsConstant($const, $node->getConstants())) {
                            continue;
                        }
                        $mergeConstants[] = $const;
                    }

                    if (empty($mergeConstants)) {
                        continue;
                    }
                    array_unshift(
                        $node->stmts,
                        NodeBuilder::makeClassConstantGroup(
                            $consts->flags,
                            ...$mergeConstants
                        )
                    );
                }
            }
        }
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

    public function __debugInfo()
    {
        return [
            'className' => $this->className,
            'patched' => (bool) $this->mockedNode,
            'isBuiltIn' => $this->isBuiltIn,
            'isClass' => $this->isClass,
            'isTrait' => $this->isTrait,
        ];
    }

    /**
     * @return Inspector[]
     */
    protected function getExtendedClasses(string $class): array
    {
        $inspectors = [];

        do {
            $nextStep = false;
            $class = ltrim($class, '\\');
            $inspector = static::factory(
                $this->args,
                $class,
                $this->inheritOriginalClass
            );

            foreach ($this->conditions as $name => $condition) {
                $inspector->methodGroup(
                    $name,
                    $condition
                );
            }

            if ($inspector->isBuiltIn()) {
                // Cannot ref the class.
                break;
            }

            $mockedNode = $inspector
                ->enableParentMock(false)
                ->enableTraitsMock($this->enableTraitsMock)
                ->patch()
                ->getMockedNode();

            if ($inspector->isClass()) {
                if ($mockedNode->extends !== null) {
                    $class = $this->combinePath(
                        $this->namespace,
                        $mockedNode->extends->parts
                    );
                }
            }

            $inspectors[] = $inspector;

            if ($inspector->isClass()) {
                $nextStep = $mockedNode->extends !== null;
            }
        } while ($nextStep);

        return $inspectors;
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

    protected function assignClassType(string $className): void
    {
        if (class_exists($className)) {
            $this->isClass = true;
            return;
        }

        if (trait_exists($className)) {
            $this->isTrait = true;
            return;
        }

        throw new MethodInjectorException(
            'The specified class is not a user defined.'
        );
    }

    protected function containsConstant(Node\Const_ $needle, array $haystack): bool
    {
        foreach ($haystack as $const) {
            if (!($const instanceof Node\Const_)) {
                continue;
            }
            if ($needle->name->name === $const->name->name) {
                return true;
            }
        }
        return false;
    }

    protected function containsField(Node\Stmt\PropertyProperty $needle, array $haystack): bool
    {
        foreach ($haystack as $property) {
            if (!($property instanceof Node\Stmt\PropertyProperty)) {
                continue;
            }
            if ($needle->name->name === $property->name->name) {
                return true;
            }
        }
        return false;
    }

    protected function containsClassMethod(Node\Stmt\ClassMethod $needle, array $haystack): bool
    {
        foreach ($haystack as $classMethod) {
            if (!($classMethod instanceof Node\Stmt\ClassMethod)) {
                continue;
            }
            if ($needle->name->name === $classMethod->name->name) {
                return true;
            }
        }
        return false;
    }
}
