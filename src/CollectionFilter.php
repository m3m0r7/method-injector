<?php declare(strict_types=1);
namespace MethodInjector;

class CollectionFilter
{
    const FILTER_NONE = [];

    const FILTER_METHOD_REPLACER = [
        Inspector::FUNCTION,
        Inspector::INSTANCE,
    ];

    const FILTER_CLASS_REPLACER = [
        Inspector::CONSTANT,
        Inspector::FIELD,
        Inspector::METHOD,
    ];
}
