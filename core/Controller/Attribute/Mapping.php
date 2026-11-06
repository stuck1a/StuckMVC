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

namespace Stuck1A\StuckMVC\Core\Controller\Attribute;

use Attribute;

/**
 * Assigns a controller to one or more route paths.
 *
 * A mapping covers the route itself and all routes below it, e.g. <code>#[Mapping('music')]</code> is responsible
 * for 'music', 'music/index' and 'music/byartist'. If multiple mappings match, the most specific one wins.
 * The empty path '' only matches the start page itself.
 *
 * @example #[Mapping('home', '', 'index')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Mapping
{
    /** @var list<string> */
    public readonly array $paths;

    public function __construct(string ...$paths)
    {
        $this->paths = array_values(array_map(static fn (string $path): string => trim($path, '/'), $paths));
    }
}
