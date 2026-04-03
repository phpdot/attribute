<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute\Result;

use PHPdot\Attribute\Enum\StructureType;
use PHPdot\Attribute\Enum\TargetType;

final readonly class ClassAttributes
{
    /**
     * @param class-string $class
     * @param list<string> $implements
     * @param list<AttributeResult> $results
     */
    public function __construct(
        public string $class,
        public StructureType $structureType,
        public array $implements,
        public ?string $extends,
        public array $results,
    ) {}

    /**
     * @return list<AttributeResult>
     */
    public function all(): array
    {
        return $this->results;
    }

    /**
     * @return list<AttributeResult>
     */
    public function classAttributes(): array
    {
        return array_values(
            array_filter(
                $this->results,
                static fn(AttributeResult $result): bool => $result->target === TargetType::CLASS_TYPE,
            ),
        );
    }

    /**
     * @return list<AttributeResult>
     */
    public function constantAttributes(?string $constant = null): array
    {
        return array_values(
            array_filter(
                $this->results,
                static fn(AttributeResult $result): bool => $result->target === TargetType::CONSTANT
                    && ($constant === null || $result->constant === $constant),
            ),
        );
    }

    /**
     * @param class-string $attributeClass
     */
    public function get(string $attributeClass): ?AttributeResult
    {
        foreach ($this->results as $result) {
            if ($result->attribute === $attributeClass) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param class-string $attributeClass
     */
    public function has(string $attributeClass): bool
    {
        foreach ($this->results as $result) {
            if ($result->attribute === $attributeClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<AttributeResult>
     */
    public function methodAttributes(?string $method = null): array
    {
        return array_values(
            array_filter(
                $this->results,
                static fn(AttributeResult $result): bool => $result->target === TargetType::METHOD
                    && ($method === null || $result->method === $method),
            ),
        );
    }

    /**
     * @return list<AttributeResult>
     */
    public function parameterAttributes(?string $method = null, ?string $parameter = null): array
    {
        return array_values(
            array_filter(
                $this->results,
                static fn(AttributeResult $result): bool => $result->target === TargetType::PARAMETER
                    && ($method === null || $result->method === $method)
                    && ($parameter === null || $result->parameter === $parameter),
            ),
        );
    }

    /**
     * @return list<AttributeResult>
     */
    public function propertyAttributes(?string $property = null): array
    {
        return array_values(
            array_filter(
                $this->results,
                static fn(AttributeResult $result): bool => $result->target === TargetType::PROPERTY
                    && ($property === null || $result->property === $property),
            ),
        );
    }
}
