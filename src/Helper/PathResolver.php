<?php declare(strict_types=1);
namespace MethodInjector\Helper;

use PhpParser\Node;

class PathResolver
{
    /**
     * @var array|Node\Stmt\Use_[]
     */
    protected $aliases = [];

    protected $namespace = [];

    /**
     * @param Node\Stmt\Use_[] $aliases
     */
    protected function __construct(array $namespace = [], array $aliases = [])
    {
        $this->namespace = $namespace;
        $this->aliases = $aliases;
    }

    public static function factory(array $namespace = [], array $aliases = []): PathResolver
    {
        return new static($namespace, $aliases);
    }

    public function contains(string $target, string $from): bool
    {
        if ($target === $from) {
            return true;
        }
        $from = ltrim($from, '\\');
        $default = static::toStringPath(array_merge($this->namespace, [$target]));
        [ $head ] = explode('\\', $target);
        foreach ($this->filteredAliases(Node\Stmt\Use_::TYPE_UNKNOWN) as $alias) {
            $last = array_pop($alias);
            if ($last !== $head) {
                continue;
            }

            $alias[] = $head;
            $path = static::toStringPath($alias);
            return $path === $from;
        }

        return ltrim($default, '\\') === $from;
    }

    public static function toStringPath(array $parts)
    {
        return implode('\\', $parts);
    }

    protected function filteredAliases(int $type): array
    {
        return array_reduce(
            $this->aliases,
            function ($carry, $item) use ($type) {
                if ($item->type === $type) {
                    $carry[] = $item->name->parts;
                }
                return $carry;
            },
            []
        );
    }
}
