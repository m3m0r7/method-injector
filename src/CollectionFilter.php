<?php declare(strict_types=1);
namespace MethodInjector;

class CollectionFilter
{
    const FILTER_NONE = [];

    const FILTER_METHOD_REPLACER = [
        Inspector::FUNCTION,
        Inspector::INSTANCE,
        Inspector::STATIC_CALL,
        Inspector::CONSTANT_FETCH,
    ];

    const FILTER_CLASS_REPLACER = [
        Inspector::CLASS_CONSTANT,
        Inspector::FIELD,
        Inspector::METHOD,
    ];
}
