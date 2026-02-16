<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FindAnniversariesTest extends TestCase
{
    protected function setUp(): void
    {
        date_default_timezone_set('Asia/Kolkata');
    }

    /**
     * Helper to run main script
     */
    private function runScript()
    {
        $testToday = new DateTime('2/16/2026');
        $testToday->setTime(0,0,0);
        return include __DIR__ . '/../find_anniversaries.php';
    }

    /**
     * Parametrized test for all milestones
     */
    #[DataProvider('milestoneProvider')]
    public function testMilestones(string $name, string $milestone): void
    {
        $matches = $this->runScript();
        $found = false;

        foreach ($matches as $m) {
            if ($m['name'] === $name && in_array($milestone, $m['milestones'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "$milestone milestone not detected for $name");
    }

    /**
     * Data provider for all test cases
     */
    public static function milestoneProvider(): array
    {
        return [
            // HOURS
            ['Sober72H', '72 hours'],

            // DAYS
            ['Sober100D', '100 days'],
            ['Sober1W', '7 days'], // 1 week

            // MONTHS
            ['Sober1M', '1 months'],
            ['Sober2M', '2 months'],

            // YEARS
            ['Sober2Y', '2 years'],
            ['Sober6Y', '6 years'],
        ];
    }
}
