<?php declare(strict_types=1);
namespace MethodInjector;

use MethodInjector\Helper\NodeFilter;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

class MethodInjector
{
    /**
     * @var array
     */
    protected $inspectors = [];

    protected $inspectorClass = Inspector::class;
    protected $generatedCode;

    protected $args;

    protected function __construct(array $args)
    {
        $this->args = $args;
    }

    /**
     * @return static
     */
    public static function factory(array $args = [])
    {
        return new static($args);
    }

    public function getInspectors(): array
    {
        return array_values(
            $this->inspectors
        );
    }

    /**
     * @throws \ReflectionException
     * @return $this
     */
    public function inspect(string $className, callable $callback, bool $inheritOriginalClass = false): self
    {
        if (isset($this->args['inspectorClass'])) {
            $this->inspectorClass = $this->args['inspectorClass'];
        }

        $this->inspectors[$className] = $callback(
            ($this->inspectorClass)::factory(
                $this->args,
                $className,
                $inheritOriginalClass
            )
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function patch(): self
    {
        /**
         * @var Inspector $inspector
         */
        foreach ($this->inspectors as $inspector) {
            $inspector->patch();
        }

        return $this;
    }

    /**
     * @param mixed ...$arguments
     */
    public function createMock(string $className, ...$arguments): MethodInjectorInterface
    {
        if (!isset($this->inspectors[$className])) {
            throw new MethodInjectorException(
                'Failed to initiate ' . $className . ' because the class is not inspected.'
            );
        }

        $mockedClassName = $this->generateMockedCode($className);

        eval($this->generatedCode);
        return new $mockedClassName(...$arguments);
    }

    public function generateMockedCode(string $className)
    {
        $prettyPrinter = new Standard();

        /**
         * @var Inspector $inspector
         */
        $inspector = $this->inspectors[$className];

        if (!($inspector->getMockedNode() instanceof Node\Stmt\Class_)) {
            throw new MethodInjectorException(
                'Failed to generate a mock. The inspected class is not a class.'
            );
        }

        if ($inspector->getMockedNode()->isAbstract()) {
            throw new MethodInjectorException(
                'Failed to generate a mock. The inspected class is an abstracted class.'
            );
        }

        $enableInheritance = $inspector->getAttributes()['inheritance'];

        // validate more
        if ($enableInheritance) {
            $this->validateInheritableClass(
                $inspector
                    ->getMockedNode()
            );
        }

        $mockedCode = implode([
            $prettyPrinter
                ->prettyPrint(
                    NodeFilter::filterFields(
                        $inspector
                            ->getMockedNode()
                            ->getProperties()
                    )
                ),
            $prettyPrinter
                ->prettyPrint(
                    NodeFilter::filterConstants(
                        $inspector
                            ->getMockedNode()
                            ->getConstants()
                    )
                ),
            $prettyPrinter
                ->prettyPrint(
                    NodeFilter::filterMethods(
                        $inspector
                            ->getMockedNode()
                            ->getMethods()
                    )
                ),
        ]);

        $mockedClassName = $inspector->getMockedClassName();

        $classAttribute = $inspector->getAttributes();

        $classAttribute['implements'][] = $implementedMethodInjectorInterface = '\\MethodInjector\\MethodInjectorInterface';

        $extended = '';
        $implements = '';

        if ($enableInheritance) {
            $extended = 'extends \\' . $className;
            $implements = $implementedMethodInjectorInterface;
        } elseif ($classAttribute['extends'] !== null) {
            $extended = 'extends ' . $classAttribute['extends'];
        }

        if (!empty($classAttribute['implements'])) {
            $implements = implode(', ', $classAttribute['implements']);
        }

        $generatedCode = 'class ' . $mockedClassName . ' '
            . $extended . ' implements ' . $implements
            . '{'
            . $mockedCode
            . '}';

        if ($inspector->getNamespace() !== null) {
            $generatedCode = 'namespace ' . $inspector->getNamespace() . ' {' . $generatedCode . '}';
            $mockedClassName = '\\' . $inspector->getNamespace() . '\\' . $mockedClassName;
        }

        $this->generatedCode = $generatedCode;

        return $mockedClassName;
    }

    public function getGeneratedCodeTraceAsString(): string
    {
        if ($this->generatedCode === null) {
            throw new MethodInjectorException(
                'Not generated codes yet. Did you forget to run `createMock` or `generateMockedCode`?'
            );
        }
        return (string) $this->generatedCode;
    }

    protected function validateInheritableClass(Node\Stmt\Class_ $node)
    {
        static $inheritedFinalMessageForClass = 'Cannot inherit the original class `%1$s` because it is defined `%2$s` modifier. ' .
            'Please remove `%2$s` modifier or disable inheritance mode.';
        static $inheritedFinalMessageForMethod = 'Cannot inherit the method `%1$s` of the original class `%2$s` because it is defined `%3$s` modifier. ' .
            'Please remove `%3$s` modifier or disable inheritance mode.';

        if ($node->isFinal()) {
            throw new MethodInjectorException(
                sprintf(
                    $inheritedFinalMessageForClass,
                    $node->name->name,
                    'final'
                )
            );
        }

        foreach ($node->getMethods() as $method) {
            if ($method->isFinal()) {
                throw new MethodInjectorException(
                    sprintf(
                        $inheritedFinalMessageForMethod,
                        $method->name->name,
                        $node->name->name,
                        'final'
                    )
                );
            }
        }
    }
}
