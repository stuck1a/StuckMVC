<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Core\Config;

/** Persistent boolean overrides, kept outside the disposable cache and PHP configuration. */
final class FeatureSettings
{
    public function __construct(private readonly string $file)
    {
    }

    /** @return array<string, bool> */
    public function load(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $json = @file_get_contents($this->file);
        if ($json === false) {
            throw new \RuntimeException('Website-Einstellungen konnten nicht gelesen werden.');
        }
        $values = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($values) || array_filter($values, static fn ($value): bool => !is_bool($value))) {
            throw new \RuntimeException('Ungültige Website-Einstellungen.');
        }
        return $values;
    }

    /** @param array<string, bool> $values */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (!is_string($key) || !is_bool($value)) {
                throw new \InvalidArgumentException('Website-Einstellungen müssen benannte Schalter sein.');
            }
        }
        $directory = dirname($this->file);
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Website-Einstellungen konnten nicht gespeichert werden.');
        }
        $temporary = @tempnam($directory, '.settings-');
        if ($temporary === false) {
            throw new \RuntimeException('Website-Einstellungen konnten nicht gespeichert werden.');
        }
        try {
            // tempnam can fall back to the system directory when the destination is not writable.
            if (realpath(dirname($temporary)) !== realpath($directory)) {
                throw new \RuntimeException('Website-Einstellungen konnten nicht gespeichert werden.');
            }
            $json = json_encode((object) $values, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
            if (@file_put_contents($temporary, $json, LOCK_EX) !== strlen($json)
                || !@rename($temporary, $this->file)) {
                throw new \RuntimeException('Website-Einstellungen konnten nicht gespeichert werden.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
