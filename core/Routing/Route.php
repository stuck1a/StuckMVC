<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021-2026.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by
 *  the Free Software Foundation. See the GNU General Public License for more details.
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Core\Routing;

/**
 * A routable content page (or backend page) including its SEO data.
 */
final class Route
{
    public const SECTION_PAGE = 'page';
    public const SECTION_ADMIN = 'admin';

    /**
     * @param string  $path        Route path (SEO URL relative to the base path, '' for the start page)
     * @param string  $template    Absolute path to the template file
     * @param ?string $controller  FQCN of the responsible controller or null for the section's default controller
     */
    public function __construct(
        public readonly string $path,
        public readonly string $template,
        public readonly string $title,
        public readonly string $section = self::SECTION_PAGE,
        public readonly ?string $controller = null,
        public readonly string $description = '',
        public readonly string $keywords = '',
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->section === self::SECTION_ADMIN;
    }

    /**
     * Whether the page is a regular content page which may be listed in search results and similar.
     */
    public function isPublicContent(): bool
    {
        return $this->section === self::SECTION_PAGE
            && !str_contains($this->path, '{')
            && $this->path !== 'user'
            && $this->path !== 'account'
            && !str_starts_with($this->path, 'account/')
            && $this->path !== 'error'
            && !str_starts_with($this->path, 'error/');
    }

    /**
     * @return array<string, ?string>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array<string, ?string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['path'],
            (string) $data['template'],
            (string) $data['title'],
            (string) ($data['section'] ?? self::SECTION_PAGE),
            isset($data['controller']) ? (string) $data['controller'] : null,
            (string) ($data['description'] ?? ''),
            (string) ($data['keywords'] ?? ''),
        );
    }
}
