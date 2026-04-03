<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute\Discovery;

final class ComposerDiscovery
{
    /**
     * @param list<string> $directories
     * @param list<string> $namespaces
     * @param list<string> $excludePatterns
     * @return list<class-string>
     */
    public function discover(
        string $projectRoot,
        array $directories = [],
        array $namespaces = [],
        array $excludePatterns = [],
    ): array {
        $classmapPath = $projectRoot . '/vendor/composer/autoload_classmap.php';

        if (!file_exists($classmapPath)) {
            return [];
        }

        /** @var array<class-string, string> $classmap */
        $classmap = require $classmapPath;
        $classes = [];
        $resolvedDirectories = array_map(
            static function (string $dir): string {
                $resolved = realpath($dir);

                return $resolved !== false ? $resolved : $dir;
            },
            $directories,
        );

        foreach ($classmap as $className => $filePath) {
            if ($resolvedDirectories !== []) {
                $resolved = realpath($filePath);
                $resolvedFilePath = $resolved !== false ? $resolved : $filePath;
                $matchesDirectory = false;

                foreach ($resolvedDirectories as $directory) {
                    if (str_starts_with($resolvedFilePath, $directory)) {
                        $matchesDirectory = true;
                        break;
                    }
                }

                if (!$matchesDirectory) {
                    continue;
                }
            }

            if ($namespaces !== []) {
                $matchesNamespace = false;

                foreach ($namespaces as $namespace) {
                    if (str_starts_with($className, $namespace)) {
                        $matchesNamespace = true;
                        break;
                    }
                }

                if (!$matchesNamespace) {
                    continue;
                }
            }

            if ($this->isExcluded($className, $excludePatterns)) {
                continue;
            }

            $classes[] = $className;
        }

        sort($classes);

        return $classes;
    }

    /**
     * @param list<string> $excludePatterns
     */
    private function isExcluded(string $className, array $excludePatterns): bool
    {
        foreach ($excludePatterns as $pattern) {
            if (fnmatch($pattern, $className)) {
                return true;
            }
        }

        return false;
    }
}
