<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\RSSFeedCollectBundle\Command\FeedCollectAllCommand;

/**
 * @internal
 */
#[CoversClass(FeedCollectAllCommand::class)]
#[RunTestsInSeparateProcesses]
class FeedCollectAllCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testCommandExists(): void
    {
        $command = self::getService(FeedCollectAllCommand::class);
        $this->assertInstanceOf(FeedCollectAllCommand::class, $command);
    }

    public function testOptionForce(): void
    {
        $commandTester = $this->getCommandTester();
        $this->assertInstanceOf(CommandTester::class, $commandTester);
    }

    public function testOptionStats(): void
    {
        $commandTester = $this->getCommandTester();
        $this->assertInstanceOf(CommandTester::class, $commandTester);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(FeedCollectAllCommand::class);

        return new CommandTester($command);
    }
}
