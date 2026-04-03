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

final class Registry
{
    public function __construct(
        private readonly AttributeMap $map,
    ) {}

    /**
     * @return list<AttributeResult>
     */
    public function all(): array
    {
        $results = [];

        foreach ($this->map->classes as $classAttributes) {
            foreach ($classAttributes->results as $result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    public function count(): int
    {
        return $this->map->count();
    }

    /**
     * @param class-string $attributeClass
     */
    public function countByAttribute(string $attributeClass): int
    {
        return count($this->findByAttribute($attributeClass));
    }

    /**
     * @param class-string $attributeClass
     * @return list<AttributeResult>
     */
    public function findByAttribute(string $attributeClass): array
    {
        $results = [];

        foreach ($this->map->classes as $classAttributes) {
            foreach ($classAttributes->results as $result) {
                if ($result->attribute === $attributeClass) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * @param class-string $className
     */
    public function findByClass(string $className, bool $includeParents = false): ?ClassAttributes
    {
        $classAttributes = $this->map->getClass($className);

        if ($classAttributes === null || !$includeParents) {
            return $classAttributes;
        }

        $parentResults = [];
        $parentName = $classAttributes->extends;

        while ($parentName !== null) {
            $parent = $this->map->getClass($parentName);

            if ($parent === null) {
                break;
            }

            foreach ($parent->results as $result) {
                $parentResults[] = $result;
            }

            $parentName = $parent->extends;
        }

        /** @var list<AttributeResult> $merged */
        $merged = array_merge($classAttributes->results, $parentResults);

        return new ClassAttributes(
            class: $classAttributes->class,
            structureType: $classAttributes->structureType,
            implements: $classAttributes->implements,
            extends: $classAttributes->extends,
            results: $merged,
        );
    }

    /**
     * @param class-string $className
     * @return list<AttributeResult>
     */
    public function findByMethod(string $className, string $method): array
    {
        $classAttributes = $this->map->getClass($className);

        if ($classAttributes === null) {
            return [];
        }

        return $classAttributes->methodAttributes($method);
    }

    /**
     * @param class-string|null $attributeClass
     * @return list<AttributeResult>
     */
    public function findClassAttributes(?string $attributeClass = null): array
    {
        return $this->findByTarget($attributeClass, TargetType::CLASS_TYPE);
    }

    /**
     * @return list<string>
     */
    public function findClasses(): array
    {
        return $this->findByStructureType(StructureType::CLASS_TYPE);
    }

    /**
     * @param class-string|null $attributeClass
     * @return list<AttributeResult>
     */
    public function findConstantAttributes(?string $attributeClass = null): array
    {
        return $this->findByTarget($attributeClass, TargetType::CONSTANT);
    }

    /**
     * @return list<string>
     */
    public function findEnums(): array
    {
        return $this->findByStructureType(StructureType::ENUM_TYPE);
    }

    /**
     * @return list<string>
     */
    public function findExtending(string $parentClass): array
    {
        $results = [];

        foreach ($this->map->classes as $classAttributes) {
            if ($classAttributes->extends === $parentClass) {
                $results[] = $classAttributes->class;
            }
        }

        return $results;
    }

    /**
     * @return list<string>
     */
    public function findImplementing(string $interface): array
    {
        $results = [];

        foreach ($this->map->classes as $classAttributes) {
            if (in_array($interface, $classAttributes->implements, true)) {
                $results[] = $classAttributes->class;
            }
        }

        return $results;
    }

    /**
     * @return list<string>
     */
    public function findInterfaces(): array
    {
        return $this->findByStructureType(StructureType::INTERFACE_TYPE);
    }

    /**
     * @param class-string|null $attributeClass
     * @return list<AttributeResult>
     */
    public function findMethodAttributes(?string $attributeClass = null): array
    {
        return $this->findByTarget($attributeClass, TargetType::METHOD);
    }

    /**
     * @param class-string|null $attributeClass
     * @return list<AttributeResult>
     */
    public function findParameterAttributes(?string $attributeClass = null): array
    {
        return $this->findByTarget($attributeClass, TargetType::PARAMETER);
    }

    /**
     * @param class-string|null $attributeClass
     * @return list<AttributeResult>
     */
    public function findPropertyAttributes(?string $attributeClass = null): array
    {
        return $this->findByTarget($attributeClass, TargetType::PROPERTY);
    }

    /**
     * @return list<string>
     */
    public function findTraits(): array
    {
        return $this->findByStructureType(StructureType::TRAIT_TYPE);
    }

    /**
     * @param class-string $attributeClass
     * @return list<string>
     */
    public function getClassesWithAttribute(string $attributeClass): array
    {
        $classes = [];

        foreach ($this->map->classes as $classAttributes) {
            if ($classAttributes->has($attributeClass)) {
                $classes[] = $classAttributes->class;
            }
        }

        return $classes;
    }

    public function getMap(): AttributeMap
    {
        return $this->map;
    }

    /**
     * @param class-string $attributeClass
     */
    public function hasAttribute(string $attributeClass): bool
    {
        foreach ($this->map->classes as $classAttributes) {
            if ($classAttributes->has($attributeClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function findByStructureType(StructureType $type): array
    {
        $results = [];

        foreach ($this->map->classes as $classAttributes) {
            if ($classAttributes->structureType === $type) {
                $results[] = $classAttributes->class;
            }
        }

        return $results;
    }

    /**
     * @param class-string|null $attributeClass
     * @return list<AttributeResult>
     */
    private function findByTarget(?string $attributeClass, TargetType $target): array
    {
        $results = [];

        foreach ($this->map->classes as $classAttributes) {
            foreach ($classAttributes->results as $result) {
                if ($result->target === $target
                    && ($attributeClass === null || $result->attribute === $attributeClass)) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }
}
