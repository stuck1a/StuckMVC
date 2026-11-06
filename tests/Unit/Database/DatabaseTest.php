<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Stuck1A\StuckMVC\Application\Model\User;
use Stuck1A\StuckMVC\Application\Setup\SchemaInstaller;
use Stuck1A\StuckMVC\Core\Database\Connection;
use Stuck1A\StuckMVC\Core\Database\Model;

final class DatabaseTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = new Connection(['driver' => 'sqlite', 'name' => ':memory:']);
        (new SchemaInstaller($this->db))->install();
        Model::setConnection($this->db);
    }

    protected function tearDown(): void
    {
        Model::setConnection(null);
    }

    public function testSelectSqlUsesPlaceholdersAndQuotedIdentifiers(): void
    {
        [$sql, $params] = $this->db->table('user')
            ->select('id', 'username')
            ->where('username', 'foo')
            ->orWhere('email', 'LIKE', '%@x.de')
            ->whereIn('role', ['admin', 'user'])
            ->whereNull('last_login_at')
            ->orderBy('id', 'desc')
            ->limit(10, 20)
            ->toSelectSql();
        self::assertSame(
            'SELECT "id", "username" FROM "user" WHERE "username" = ? OR "email" LIKE ? AND "role" IN (?, ?) AND "last_login_at" IS NULL ORDER BY "id" DESC LIMIT 10 OFFSET 20',
            $sql,
        );
        self::assertSame(['foo', '%@x.de', 'admin', 'user'], $params);
    }

    public function testMysqlQuoting(): void
    {
        $mysql = new Connection(['driver' => 'mysql']);
        self::assertSame('`user`.`id`', $mysql->quoteIdentifier('user.id'));
    }

    public function testInjectionThroughIdentifiersOrOperatorsIsImpossible(): void
    {
        foreach (["id; DROP TABLE user", 'id`', 'a.b.c', '1id', 'id--', ''] as $identifier) {
            try {
                $this->db->table('user')->where($identifier, 1)->toSelectSql();
                self::fail("Identifier '{$identifier}' must be rejected.");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->expectException(\InvalidArgumentException::class);
        $this->db->table('user')->where('id', '= 1 OR 1 =', 1);
    }

    public function testValuesAreBoundNotInterpolated(): void
    {
        $this->db->table('user')->insert(['username' => 'victim', 'passhash' => 'x', 'created_at' => '2026-01-01']);
        $evil = "' OR '1'='1";
        self::assertNull($this->db->table('user')->where('username', $evil)->first());
        self::assertSame(0, $this->db->table('user')->where('username', $evil)->count());
    }

    public function testCrudOperations(): void
    {
        $users = $this->db->table('user');
        $id = $this->db->table('user')->insert(['username' => 'a', 'passhash' => 'x', 'created_at' => '2026-01-01']);
        $this->db->table('user')->insert(['username' => 'b', 'passhash' => 'y', 'created_at' => '2026-01-01', 'role' => 'admin']);
        self::assertSame('1', $id);
        self::assertSame(2, $users->count());
        self::assertSame('b', $this->db->table('user')->where('role', 'admin')->first()['username']);
        self::assertSame(1, $this->db->table('user')->where('id', 1)->update(['email' => 'a@x.de']));
        self::assertSame('a@x.de', $this->db->table('user')->where('id', 1)->first()['email']);
        self::assertSame(['b'], array_column($this->db->table('user')->where('email', null)->get(), 'username'));
        self::assertSame(['a'], array_column($this->db->table('user')->where('email', '!=', null)->get(), 'username'));
        self::assertSame(1, $this->db->table('user')->where('username', 'b')->delete());
        self::assertFalse($this->db->table('user')->where('username', 'b')->exists());
        self::assertSame([], $this->db->table('user')->whereIn('id', [])->get());
    }

    public function testUnconditionalUpdateAndDeleteAreRefused(): void
    {
        $this->expectException(\LogicException::class);
        $this->db->table('user')->delete();
    }

    public function testTransactionRollsBack(): void
    {
        try {
            $this->db->transaction(function (Connection $db): void {
                $db->table('user')->insert(['username' => 'tx', 'passhash' => 'x', 'created_at' => '2026-01-01']);
                throw new \RuntimeException('abort');
            });
        } catch (\RuntimeException) {
        }
        self::assertSame(0, $this->db->table('user')->count());
    }

    public function testActiveRecordModel(): void
    {
        $user = User::register('stuck1a', 'correct horse battery', 'Stuck@example.com', User::ROLE_ADMIN);
        self::assertFalse($user->exists());
        self::assertTrue($user->save());
        self::assertTrue($user->exists());
        self::assertSame(1, $user->getKey());
        self::assertTrue(password_verify('correct horse battery', $user->getPasswordHash()));

        $loaded = User::find(1);
        self::assertNotNull($loaded);
        self::assertSame('stuck1a', $loaded->getUsername());
        self::assertTrue($loaded->isAdmin());
        self::assertFalse($loaded->isDirty());

        $loaded->email = 'new@example.com';
        self::assertTrue($loaded->isDirty());
        $loaded->save();
        self::assertSame('new@example.com', User::findBy(['username' => 'stuck1a'])?->email);
        self::assertCount(1, User::findAll(['role' => User::ROLE_ADMIN]));
        self::assertTrue(User::usernameExists('stuck1a'));
        self::assertTrue(User::adminExists());

        self::assertTrue($loaded->delete());
        self::assertNull(User::find(1));
    }

    public function testMassAssignmentOnlyFillsAllowedColumns(): void
    {
        $user = new User(['username' => 'foo', 'role' => 'admin', 'passhash' => 'manipulated', 'id' => 99]);
        self::assertSame(['username' => 'foo'], $user->toArray());
    }

    public function testUserValidation(): void
    {
        self::assertSame([], User::validate('stuck1a', 'long enough pw', 'long enough pw', 'a@b.de'));
        self::assertCount(4, User::validate('x', 'short', 'other', 'no-mail'));
        self::assertNotSame([], User::validate('<script>', 'long enough pw', 'long enough pw'));
        foreach (['abcdefg1' => true, 'abcdefg!' => true, 'Äöüßabc7' => true, 'abcdefgh' => false, 'ÄÖÜäöüßé' => false, 'abcde1!' => false] as $password => $strong) {
            self::assertSame($strong, User::isStrongPassword($password), $password);
        }
    }
}
