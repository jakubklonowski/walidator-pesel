<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ValidatePeselCommandTest extends KernelTestCase
{
    public function testValidNumberReportsDecodedDataAndSucceeds(): void
    {
        $tester = $this->tester();
        $tester->execute(['pesel' => '44051401359']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1944-05-14', $tester->getDisplay());
        self::assertMatchesRegularExpression('/Gender\s+male\b/', $tester->getDisplay());
    }

    public function testInvalidNumberListsEveryViolationCodeAndFails(): void
    {
        $tester = $this->tester();
        $tester->execute(['pesel' => '00022900011']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('invalid_birth_date', $tester->getDisplay());
        self::assertStringContainsString('invalid_checksum', $tester->getDisplay());
    }

    /**
     * @dataProvider outputProvider
     */
    public function testOutputShowsOnlyTheMaskedNumber(string $pesel, string $masked): void
    {
        $tester = $this->tester();
        $tester->execute(['pesel' => $pesel]);

        self::assertStringNotContainsString($pesel, $tester->getDisplay());
        self::assertStringContainsString($masked, $tester->getDisplay());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function outputProvider(): iterable
    {
        yield 'valid number' => ['44051401359', '44*******59'];
        yield 'invalid number' => ['00022900011', '00*******11'];
    }

    private function tester(): CommandTester
    {
        $application = new Application(self::bootKernel());

        return new CommandTester($application->find('app:pesel:validate'));
    }
}
