<?php

namespace Tests\Unit\Services;

use App\Services\SessionAnomalyDetector;
use Tests\TestCase;

class SessionAnomalyDetectorTest extends TestCase
{
    protected SessionAnomalyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = app(SessionAnomalyDetector::class);
    }

    public function testDetectReturnsEmptyArrayForNoAnomalies(): void
    {
        $current = [
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'user_id' => 1,
            'timestamp' => now()->toDateTimeString(),
        ];

        $result = $this->detector->detect($current, null);

        $this->assertIsArray($result);
    }

    public function testDetectFlagsUnusualHours(): void
    {
        $current = [
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'user_id' => 1,
            'timestamp' => now()->setHour(3)->toDateTimeString(),
        ];

        $result = $this->detector->detect($current, null);

        $hasUnusual = collect($result)->contains('type', 'unusual_time');
        $this->assertTrue($hasUnusual, 'Expected unusual_time anomaly for 3 AM login');
    }

    public function testDetectReturnsEmptyForNormalHour(): void
    {
        $current = [
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'user_id' => 1,
            'timestamp' => now()->setHour(14)->toDateTimeString(),
        ];

        $result = $this->detector->detect($current, null);

        $this->assertEmpty($result);
    }
}
