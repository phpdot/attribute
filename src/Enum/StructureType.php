<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute\Enum;

enum StructureType: string
{
    case CLASS_TYPE = 'class';
    case ENUM_TYPE = 'enum';
    case INTERFACE_TYPE = 'interface';
    case TRAIT_TYPE = 'trait';
}
