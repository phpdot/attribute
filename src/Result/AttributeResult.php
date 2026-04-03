<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute\Result;

use PHPdot\Attribute\Enum\TargetType;

final readonly class AttributeResult
{
    /**
     * @param class-string $attribute
     * @param list<mixed> $arguments
     * @param class-string $class
     */
    public function __construct(
        public string $attribute,
        public object $instance,
        public array $arguments,
        public string $class,
        public TargetType $target,
        public ?string $method = null,
        public ?string $property = null,
        public ?string $parameter = null,
        public ?string $constant = null,
    ) {}
}
