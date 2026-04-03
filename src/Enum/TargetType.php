<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute\Enum;

enum TargetType: string
{
    case CLASS_TYPE = 'class';
    case CONSTANT = 'constant';
    case METHOD = 'method';
    case PARAMETER = 'parameter';
    case PROPERTY = 'property';
}
