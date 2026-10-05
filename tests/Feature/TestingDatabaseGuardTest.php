<?php

namespace Tests\Feature;

use Tests\TestCase;

class TestingDatabaseGuardTest extends TestCase
{
    public function test_phpunit_uses_sqlite_memory_not_mysql(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame('sqlite', config('database.connections.sqlite.driver'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertNotSame('mysql', config('database.default'));
        $this->assertNotSame('rrs', config('database.connections.sqlite.database'));
    }
}
