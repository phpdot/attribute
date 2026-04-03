<?php

/**
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

declare(strict_types=1);

namespace PHPdot\Attribute\Cache;

use PHPdot\Attribute\Result\AttributeMap;

final class FileCache
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function clear(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function has(): bool
    {
        return file_exists($this->path);
    }

    public function read(): ?AttributeMap
    {
        if (!file_exists($this->path)) {
            return null;
        }

        /** @var array{classes: array<string, array{class: string, structureType: string, implements: list<string>, extends: ?string, results: list<array{attribute: string, arguments: list<mixed>, class: string, target: string, method: ?string, property: ?string, parameter: ?string, constant: ?string}>}>, generatedAt: int, directories: list<string>, filter: list<string>} $data */
        $data = require $this->path;

        return AttributeMap::fromCache($data);
    }

    public function write(AttributeMap $map): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($map->toCache(), true) . ";\n";

        file_put_contents($this->path, $content);
    }
}
