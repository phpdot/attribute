<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute;

use PHPdot\Attribute\Cache\FileCache;
use PHPdot\Attribute\Discovery\ClassDiscovery;
use PHPdot\Attribute\Discovery\TokenDiscovery;
use RuntimeException;

final class Scanner
{
    private readonly ClassDiscovery $discovery;

    private readonly ReflectionScanner $reflectionScanner;

    private ?Registry $registry = null;

    public function __construct(
        ?ClassDiscovery $discovery = null,
        ?ReflectionScanner $reflectionScanner = null,
        private readonly ?FileCache $cache = null,
    ) {
        $this->discovery = $discovery ?? new ClassDiscovery(tokenDiscovery: new TokenDiscovery());
        $this->reflectionScanner = $reflectionScanner ?? new ReflectionScanner();
    }

    public function clearCache(): void
    {
        $this->cache?->clear();
        $this->registry = null;
    }

    public function registry(): Registry
    {
        if ($this->registry !== null) {
            return $this->registry;
        }

        throw new RuntimeException('Scanner has not been scanned yet. Call scan() or scanClasses() first.');
    }

    /**
     * @param list<string> $directories
     * @param list<class-string> $filter
     * @param list<string> $namespaces
     * @param list<string> $excludePatterns
     */
    public function scan(
        array $directories,
        string $projectRoot = '',
        array $filter = [],
        array $namespaces = [],
        array $excludePatterns = [],
        int $visibilityFilter = 0,
        bool $forceRescan = false,
    ): Registry {
        if (!$forceRescan && $this->cache !== null && $this->cache->has()) {
            $map = $this->cache->read();

            if ($map !== null) {
                $this->registry = new Registry($map);

                return $this->registry;
            }
        }

        $classes = $this->discovery->discover(
            directories: $directories,
            projectRoot: $projectRoot,
            namespaces: $namespaces,
            excludePatterns: $excludePatterns,
        );

        return $this->buildRegistry($classes, $filter, $visibilityFilter, $directories);
    }

    /**
     * @param list<class-string> $classes
     * @param list<class-string> $filter
     */
    public function scanClasses(
        array $classes,
        array $filter = [],
        int $visibilityFilter = 0,
        bool $forceRescan = false,
    ): Registry {
        if (!$forceRescan && $this->cache !== null && $this->cache->has()) {
            $map = $this->cache->read();

            if ($map !== null) {
                $this->registry = new Registry($map);

                return $this->registry;
            }
        }

        return $this->buildRegistry($classes, $filter, $visibilityFilter, []);
    }

    /**
     * @param list<class-string> $classes
     * @param list<class-string> $filter
     * @param list<string> $directories
     */
    private function buildRegistry(
        array $classes,
        array $filter,
        int $visibilityFilter,
        array $directories,
    ): Registry {
        $map = $this->reflectionScanner->scan(
            classes: $classes,
            filter: $filter,
            visibilityFilter: $visibilityFilter,
            directories: $directories,
        );

        if ($this->cache !== null) {
            $this->cache->write($map);
        }

        $this->registry = new Registry($map);

        return $this->registry;
    }
}
