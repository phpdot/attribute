<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute;

use PHPdot\Attribute\Enum\StructureType;
use PHPdot\Attribute\Enum\TargetType;
use PHPdot\Attribute\Result\AttributeMap;
use PHPdot\Attribute\Result\AttributeResult;
use PHPdot\Attribute\Result\ClassAttributes;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class ReflectionScanner
{
    /**
     * @param list<string> $classes
     * @param list<class-string> $filter
     * @param list<string> $directories
     */
    public function scan(
        array $classes,
        array $filter = [],
        int $visibilityFilter = 0,
        array $directories = [],
    ): AttributeMap {
        $classMap = [];

        foreach ($classes as $className) {
            if (
                !class_exists($className)
                && !interface_exists($className)
                && !trait_exists($className)
                && !enum_exists($className)
            ) {
                continue;
            }

            $ref = new ReflectionClass($className);

            $structureType = $this->resolveStructureType($ref);
            $implements = $ref->getInterfaceNames();
            $parentClass = $ref->getParentClass();
            $extends = $parentClass !== false ? $parentClass->getName() : null;

            $attributeResults = [];
            $this->scanClassAttributes($ref, $className, $filter, $attributeResults);
            $this->scanMethodAttributes($ref, $className, $filter, $visibilityFilter, $attributeResults);
            $this->scanPropertyAttributes($ref, $className, $filter, $attributeResults);
            $this->scanConstantAttributes($ref, $className, $filter, $attributeResults);

            $classMap[$className] = new ClassAttributes(
                class: $className,
                structureType: $structureType,
                implements: $implements,
                extends: $extends,
                results: $attributeResults,
            );
        }

        return new AttributeMap(
            classes: $classMap,
            generatedAt: time(),
            directories: $directories,
            filter: $filter,
        );
    }

    /**
     * @param list<ReflectionAttribute<object>> $attributes
     * @param class-string $className
     * @param list<class-string> $filterAttributes
     * @param list<AttributeResult> $results
     */
    private function processAttributes(
        array $attributes,
        string $className,
        TargetType $target,
        array $filterAttributes,
        array &$results,
        ?string $method = null,
        ?string $property = null,
        ?string $parameter = null,
        ?string $constant = null,
    ): void {
        foreach ($attributes as $attr) {
            if ($filterAttributes !== [] && !in_array($attr->getName(), $filterAttributes, true)) {
                continue;
            }

            try {
                $instance = $attr->newInstance();
            } catch (Throwable) {
                continue;
            }

            /** @var list<mixed> $arguments */
            $arguments = $attr->getArguments();

            $results[] = new AttributeResult(
                attribute: $attr->getName(),
                instance: $instance,
                arguments: $arguments,
                class: $className,
                target: $target,
                method: $method,
                property: $property,
                parameter: $parameter,
                constant: $constant,
            );
        }
    }

    /**
     * @param ReflectionClass<object> $ref
     * @param class-string $className
     * @param list<class-string> $filterAttributes
     * @param list<AttributeResult> $results
     */
    private function scanClassAttributes(
        ReflectionClass $ref,
        string $className,
        array $filterAttributes,
        array &$results,
    ): void {
        $this->processAttributes(
            attributes: $ref->getAttributes(),
            className: $className,
            target: TargetType::CLASS_TYPE,
            filterAttributes: $filterAttributes,
            results: $results,
        );
    }

    /**
     * @param ReflectionClass<object> $ref
     * @param class-string $className
     * @param list<class-string> $filterAttributes
     * @param list<AttributeResult> $results
     */
    private function scanConstantAttributes(
        ReflectionClass $ref,
        string $className,
        array $filterAttributes,
        array &$results,
    ): void {
        $constants = $ref->getReflectionConstants();

        foreach ($constants as $constant) {
            if ($constant->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            $this->processAttributes(
                attributes: $constant->getAttributes(),
                className: $className,
                target: TargetType::CONSTANT,
                filterAttributes: $filterAttributes,
                results: $results,
                constant: $constant->getName(),
            );
        }
    }

    /**
     * @param ReflectionClass<object> $ref
     * @param class-string $className
     * @param list<class-string> $filterAttributes
     * @param list<AttributeResult> $results
     */
    private function scanMethodAttributes(
        ReflectionClass $ref,
        string $className,
        array $filterAttributes,
        int $visibilityFilter,
        array &$results,
    ): void {
        $methods = $visibilityFilter !== 0
            ? $ref->getMethods($visibilityFilter)
            : $ref->getMethods();

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            $this->processAttributes(
                attributes: $method->getAttributes(),
                className: $className,
                target: TargetType::METHOD,
                filterAttributes: $filterAttributes,
                results: $results,
                method: $method->getName(),
            );

            $this->scanParameterAttributes($method, $className, $filterAttributes, $results);
        }
    }

    /**
     * @param class-string $className
     * @param list<class-string> $filterAttributes
     * @param list<AttributeResult> $results
     */
    private function scanParameterAttributes(
        ReflectionMethod $method,
        string $className,
        array $filterAttributes,
        array &$results,
    ): void {
        $parameters = $method->getParameters();

        foreach ($parameters as $parameter) {
            $this->processAttributes(
                attributes: $parameter->getAttributes(),
                className: $className,
                target: TargetType::PARAMETER,
                filterAttributes: $filterAttributes,
                results: $results,
                method: $method->getName(),
                parameter: $parameter->getName(),
            );
        }
    }

    /**
     * @param ReflectionClass<object> $ref
     * @param class-string $className
     * @param list<class-string> $filterAttributes
     * @param list<AttributeResult> $results
     */
    private function scanPropertyAttributes(
        ReflectionClass $ref,
        string $className,
        array $filterAttributes,
        array &$results,
    ): void {
        $properties = $ref->getProperties();

        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            $this->processAttributes(
                attributes: $property->getAttributes(),
                className: $className,
                target: TargetType::PROPERTY,
                filterAttributes: $filterAttributes,
                results: $results,
                property: $property->getName(),
            );
        }
    }

    /**
     * @param ReflectionClass<object> $ref
     */
    private function resolveStructureType(ReflectionClass $ref): StructureType
    {
        if ($ref->isEnum()) {
            return StructureType::ENUM_TYPE;
        }

        if ($ref->isInterface()) {
            return StructureType::INTERFACE_TYPE;
        }

        if ($ref->isTrait()) {
            return StructureType::TRAIT_TYPE;
        }

        return StructureType::CLASS_TYPE;
    }
}
