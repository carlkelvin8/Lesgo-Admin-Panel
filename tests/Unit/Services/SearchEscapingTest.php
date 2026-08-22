<?php

namespace Tests\Unit\Services;

use App\Traits\SearchEscaping;
use Tests\TestCase;

class SearchEscapingTest extends TestCase
{
    use SearchEscaping;

    public function testEscapeLikePatternEscapesPercentAndUnderscore(): void
    {
        $result = $this->escapeLikePattern('%_');

        $this->assertStringContainsString('\%', $result);
        $this->assertStringContainsString('\_', $result);
    }

    public function testEscapeLikePatternPreservesNormalText(): void
    {
        $result = $this->escapeLikePattern('hello');

        $this->assertEquals('hello', $result);
    }

    public function testEscapeLikePatternEscapesSpecialChars(): void
    {
        $result = $this->escapeLikePattern('100%');

        $this->assertEquals('100\%', $result);
    }
}
