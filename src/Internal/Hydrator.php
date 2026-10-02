<?php

declare(strict_types=1);

namespace IPMax\Internal;

use IPMax\Exception\DecodeException;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

final class Hydrator
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public static function hydrate(string $class, mixed $data): object
    {
        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new DecodeException(sprintf('Expected a JSON object for %s', $class));
        }
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getAttributes(OneOf::class) as $attribute) {
            $union = $attribute->newInstance();
            $variant = $union->variant($data[$union->field] ?? null);
            $object = class_exists($variant) ? self::hydrate($variant, $data) : null;

            return $object instanceof $class ? $object : throw new LogicException(sprintf('%s is not a variant of %s', $variant, $class));
        }
        $arguments = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $key = array_key_exists($name, $data) ? $name : strtolower((string) preg_replace('/[A-Z]/', '_$0', $name));
            if (array_key_exists($key, $data)) {
                $arguments[$name] = self::argument($parameter, $data[$key], "{$class}::{$key}");
            } elseif (!$parameter->isDefaultValueAvailable()) {
                throw new DecodeException(sprintf('Missing field %s::%s', $class, $key));
            }
        }

        return new $class(...$arguments);
    }

    private static function argument(ReflectionParameter $parameter, mixed $value, string $field): mixed
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException(sprintf('%s must have a single named type', $field));
        }
        if ($value === null && $type->allowsNull()) {
            return null;
        }
        if ($type->getName() !== 'array') {
            return self::convert($type->getName(), $value, $field);
        }
        $items = $parameter->getAttributes(ListOf::class)[0] ?? throw new LogicException(sprintf('%s needs #[ListOf]', $field));
        if (!is_array($value) || !array_is_list($value)) {
            throw new DecodeException(sprintf('Expected a JSON array for %s', $field));
        }
        $itemType = $items->newInstance()->type;

        return array_map(static fn(mixed $item): mixed => self::convert($itemType, $item, $field), $value);
    }

    private static function convert(string $type, mixed $value, string $field): mixed
    {
        return match (true) {
            $type === 'string' && is_string($value),
            $type === 'int' && is_int($value),
            $type === 'bool' && is_bool($value) => $value,
            $type === 'float' && (is_int($value) || is_float($value)) => (float) $value,
            class_exists($type) || interface_exists($type) => self::hydrate($type, $value),
            default => throw new DecodeException(sprintf('Expected %s for %s, got %s', $type, $field, get_debug_type($value))),
        };
    }
}
