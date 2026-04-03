<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute\Discovery;

final class ClassDiscovery
{
    public function __construct(
        private readonly ?ComposerDiscovery $composerDiscovery = null,
        private readonly ?TokenDiscovery $tokenDiscovery = null,
    ) {}

    /**
     * @param list<string> $directories
     * @param list<string> $namespaces
     * @param list<string> $excludePatterns
     * @return list<class-string>
     */
    public function discover(
        array $directories,
        string $projectRoot = '',
        array $namespaces = [],
        array $excludePatterns = [],
    ): array {
        if ($this->composerDiscovery !== null && $projectRoot !== '') {
            $classes = $this->composerDiscovery->discover(
                projectRoot: $projectRoot,
                directories: $directories,
                namespaces: $namespaces,
                excludePatterns: $excludePatterns,
            );

            if ($classes !== []) {
                return $classes;
            }
        }

        if ($this->tokenDiscovery !== null) {
            return $this->tokenDiscovery->discover(
                directories: $directories,
                namespaces: $namespaces,
                excludePatterns: $excludePatterns,
            );
        }

        return [];
    }
}
