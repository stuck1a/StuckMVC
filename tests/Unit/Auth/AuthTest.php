<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Stuck1A\StuckMVC\Application\Model\User;
use Stuck1A\StuckMVC\Application\Model\UserProvider;
use Stuck1A\StuckMVC\Application\Setup\SchemaInstaller;
use Stuck1A\StuckMVC\Core\Auth\Auth;
use Stuck1A\StuckMVC\Core\Auth\AuthResult;
use Stuck1A\StuckMVC\Core\Cache\FileCache;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Database\Connection;
use Stuck1A\StuckMVC\Core\Database\Model;
use Stuck1A\StuckMVC\Core\Http\Csrf;
use Stuck1A\StuckMVC\Core\Http\Session;

final class AuthTest extends TestCase
{
    private string $cacheDir;

    private Session $session;

    private FileCache $cache;

    protected function setUp(): void
    {
        $db = new Connection(['driver' => 'sqlite', 'name' => ':memory:']);
        (new SchemaInstaller($db))->install();
        Model::setConnection($db);
        User::register('stuck1a', 'correct horse battery', 'stuck@example.com', User::ROLE_ADMIN)->save();
        User::register('guest', 'another long password')->save();
        $this->cacheDir = sys_get_temp_dir() . '/stuckmvc-auth-' . bin2hex(random_bytes(4));
        $this->cache = new FileCache($this->cacheDir);
        $this->session = new Session('test', false, false);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        @rmdir($this->cacheDir);
        Model::setConnection(null);
    }

    private function auth(array $config = []): Auth
    {
        return new Auth($this->session, new Csrf($this->session), new UserProvider(), $this->cache, new Config(['auth' => $config + [
            'max_attempts' => 3, 'lockout_seconds' => 60, 'idle_timeout' => 3600,
        ]]), new NullLogger());
    }

    public function testSuccessfulLoginByUsernameOrEmail(): void
    {
        $auth = $this->auth();
        self::assertFalse($auth->check());
        self::assertSame(AuthResult::Success, $auth->attempt('stuck1a', 'correct horse battery', '1.2.3.4'));
        self::assertTrue($auth->check());
        self::assertTrue($auth->isAdmin());
        self::assertNotNull(User::find(1)?->last_login_at);

        $auth->logout();
        self::assertFalse($auth->check());
        self::assertSame(AuthResult::Success, $this->auth()->attempt('STUCK@example.com', 'correct horse battery', '1.2.3.4'));
    }

    public function testSessionSurvivesNewRequest(): void
    {
        $this->auth()->attempt('guest', 'another long password', '1.2.3.4');
        $nextRequest = $this->auth();
        self::assertSame('guest', $nextRequest->user()?->getUsername());
        self::assertFalse($nextRequest->isAdmin());
    }

    public function testWrongPasswordAndUnknownUserGiveTheSameResult(): void
    {
        self::assertSame(AuthResult::InvalidCredentials, $this->auth()->attempt('stuck1a', 'wrong', '1.2.3.4'));
        self::assertSame(AuthResult::InvalidCredentials, $this->auth()->attempt('nobody', 'wrong', '1.2.3.4'));
        self::assertSame(AuthResult::InvalidCredentials, $this->auth()->attempt('', '', '1.2.3.4'));
        self::assertFalse($this->auth()->check());
    }

    public function testLockoutAfterTooManyFailures(): void
    {
        for ($i = 0; $i < 3; $i++) {
            self::assertSame(AuthResult::InvalidCredentials, $this->auth()->attempt('stuck1a', 'wrong', '1.2.3.4'));
        }
        self::assertSame(AuthResult::LockedOut, $this->auth()->attempt('stuck1a', 'correct horse battery', '1.2.3.4'));
        // other IPs are not affected
        self::assertSame(AuthResult::Success, $this->auth()->attempt('stuck1a', 'correct horse battery', '5.6.7.8'));
    }

    public function testPasswordChangeInvalidatesSessions(): void
    {
        $this->auth()->attempt('stuck1a', 'correct horse battery', '1.2.3.4');
        $user = User::find(1);
        $user->setPassword('a completely new password');
        $user->save();
        self::assertFalse($this->auth()->check());
    }

    public function testIdleTimeout(): void
    {
        $this->auth()->attempt('stuck1a', 'correct horse battery', '1.2.3.4');
        $data = $this->session->get('_auth');
        $data['activity'] = time() - 7200;
        $this->session->set('_auth', $data);
        self::assertFalse($this->auth()->check());
    }

    public function testOutdatedHashIsUpgraded(): void
    {
        $user = User::find(2);
        $user->passhash = password_hash('another long password', PASSWORD_BCRYPT, ['cost' => 4]);
        $user->save();
        self::assertSame(AuthResult::Success, $this->auth()->attempt('guest', 'another long password', '1.2.3.4'));
        self::assertFalse(password_needs_rehash((string) User::find(2)?->passhash, PASSWORD_DEFAULT));
    }

    public function testUnavailableDatabase(): void
    {
        Model::setConnection(new Connection(['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'name' => 'x']));
        self::assertSame(AuthResult::Unavailable, $this->auth()->attempt('stuck1a', 'correct horse battery', '1.2.3.4'));
    }

    public function testCsrfTokenIsRotatedOnLogin(): void
    {
        $csrf = new Csrf($this->session);
        $before = $csrf->token();
        self::assertTrue($csrf->isValid($before));
        $this->auth()->attempt('stuck1a', 'correct horse battery', '1.2.3.4');
        self::assertFalse($csrf->isValid($before));
        self::assertFalse($csrf->isValid(null));
        self::assertFalse($csrf->isValid(''));
    }
}
