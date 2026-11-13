<?php

namespace Tests\Unit\Services;

use App\Traits\SearchEscaping;
use Tests\TestCase;

class SearchEscapingTest extends TestCase
{
    use SearchEscaping;

    public function test_escape_like_pattern_escapes_percent_and_underscore(): void
    {
        $result = $this->escapeLikePattern('%_');

        $this->assertStringContainsString('!%', $result);
        $this->assertStringContainsString('!_', $result);
    }

    public function test_escape_like_pattern_preserves_normal_text(): void
    {
        $result = $this->escapeLikePattern('hello');

        $this->assertEquals('hello', $result);
    }

    public function test_escape_like_pattern_escapes_special_chars(): void
    {
        $result = $this->escapeLikePattern('100%');

        $this->assertEquals('100!%', $result);
    }

    public function test_postgres_literal_search_uses_case_insensitive_parameterized_sql(): void
    {
        // Inspect the grammar only; do not connect to the configured cloud database.
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => '127.0.0.1',
            'database.connections.pgsql.database' => 'unreachable_test_database',
            'database.connections.pgsql.username' => 'test',
            'database.connections.pgsql.password' => '',
            'database.connections.pgsql.url' => null,
        ]);
        $this->assertSame('"name" ILIKE ? ESCAPE \'!\'', $this->searchLikeSql('name'));
    }
}
