<?php declare(strict_types=1);
namespace MethodInjector\Helper;

use MethodInjector\MethodInjectorException;
use PhpParser\Node;
use PhpParser\ParserFactory;

class NodeBuilder
{
    /**
     * @var \PhpParser\Parser
     */
    protected $parser;

    protected function __construct()
    {
        $this->parser = (new ParserFactory())
            ->create(ParserFactory::PREFER_PHP7);
    }

    public static function factory(): NodeBuilder
    {
        static $cache;
        return $cache = $cache ?? new static();
    }

    public function fromString(string $string): Node
    {
        /**
         * @var Node[] $nodes
         */
        $nodes = $this
            ->parser
            ->parse(
                '<?php ' . $string . ';'
            );

        // Unneeded expression
        return $nodes[0]
            ->expr;
    }

    /**
     * @param $scalar
     */
    public function fromScalar($scalar): Node
    {
        if (is_string($scalar)) {
            return new Node\Scalar\String_(
                $scalar
            );
        }
        if (is_int($scalar)) {
            return new Node\Scalar\LNumber(
                $scalar
            );
        }
        if (is_bool($scalar)) {
            return new Node\Scalar\LNumber(
                $scalar === true
                    ? 1
                    : 0
            );
        }
        if (is_float($scalar) || is_double($scalar)) {
            return new Node\Scalar\DNumber(
                $scalar
            );
        }

        throw new MethodInjectorException(
            'Passed parameter is not a scalar.'
        );
    }

    /**
     * @param $text
     * @return Node
     */
    public function convertVariableToNode($text, bool $simplify = true)
    {
        if (is_array($text)) {
            $exported = var_export(
                $text,
                true
            );
            return $this->fromString($exported);
        }
        if (is_string($text)) {
            return $this->fromString('"' . addslashes($text) . '"');
        }

        throw new MethodInjectorException(
            'Cannot convert scalar to a variable.'
        );
    }

    public static function callable(Node $caller, array $args = []): Node\Expr
    {
        return new Node\Expr\FuncCall($caller, $args);
    }

    public static function returnable(Node $result): Node
    {
        return new Node\Stmt\Return_($result);
    }

    public static function expressible(Node $result): Node
    {
        return new Node\Stmt\Expression($result);
    }

    /**
     * @return Node\Expr\Variable
     */
    public static function variable(string $name)
    {
        return new Node\Expr\Variable($name);
    }

    public static function deferrable(array $stmt, array $deferrableStatement): Node
    {
        return new Node\Stmt\TryCatch(
            $stmt,
            [],
            new Node\Stmt\Finally_(
                $deferrableStatement
            )
        );
    }

    public static function magicConstant(string $name): Node
    {
        if ($name === '__METHOD__') {
            return new Node\Scalar\MagicConst\Method();
        }
        if ($name === '__CLASS__') {
            return new Node\Scalar\MagicConst\Class_();
        }
        throw new MethodInjectorException(
            'The magic const `' . $name . '` is not supported'
        );
    }

    /**
     * @param Node ...$arguments
     * @return Node[]
     */
    public static function makeArguments(Node ...$arguments): array
    {
        $result = [];
        foreach ($arguments as $argument) {
            $result[] = new Node\Arg(
                $argument
            );
        }
        return $result;
    }
}
