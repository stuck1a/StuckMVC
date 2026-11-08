<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\View;

use PHPUnit\Framework\TestCase;
use Stuck1A\StuckMVC\Core\View\TemplatePlugins;

final class TemplatePluginsTest extends TestCase
{
    public function testProtectEmail(): void
    {
        self::assertSame('Mail: dev(at)stuck1a.de!', TemplatePlugins::protectEmail('Mail: dev@stuck1a.de!'));
        $script = '<script>var x = "a@b.de";</script><style>@import url(x.css);</style>';
        self::assertSame($script . ' c(at)d.org', TemplatePlugins::protectEmail($script . ' c@d.org'));
    }

    public function testEmend(): void
    {
        self::assertSame('', TemplatePlugins::emend([]));
        self::assertStringContainsString('line-through', TemplatePlugins::emend(['old' => 'a']));
        self::assertStringContainsString('>b</span>', TemplatePlugins::emend(['old' => 'a', 'new' => 'b']));
    }

}
