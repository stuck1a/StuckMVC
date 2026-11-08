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

namespace Stuck1A\StuckMVC\Core\Http;

/**
 * HTTP response which is assembled by controllers and sent by the front controller.
 */
class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private string $content = '',
        private int $status = 200,
        array $headers = [],
    ) {
        $this->headers = ['Content-Type' => 'text/html; charset=UTF-8'];
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function setHeader(string $name, string $value): static
    {
        // header values must never contain line breaks (header injection)
        $this->headers[$name] = str_replace(["\r", "\n"], '', $value);
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets a header only if it isn't set yet.
     */
    public function setDefaultHeader(string $name, string $value): static
    {
        if (!isset($this->headers[$name])) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->content;
    }
}
