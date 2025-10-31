<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\RSSFeedCollectBundle\Command\FeedCollectCommand;

/**
 * @internal
 */
#[CoversClass(FeedCollectCommand::class)]
#[RunTestsInSeparateProcesses]
class FeedCollectCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testCommandExists(): void
    {
        $command = self::getService(FeedCollectCommand::class);
        $this->assertInstanceOf(FeedCollectCommand::class, $command);
    }

    public function testOptionForce(): void
    {
        $commandTester = $this->getCommandTester();
        $this->assertInstanceOf(CommandTester::class, $commandTester);
    }

    public function testOptionFeedId(): void
    {
        $commandTester = $this->getCommandTester();
        $this->assertInstanceOf(CommandTester::class, $commandTester);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(FeedCollectCommand::class);

        return new CommandTester($command);
    }
}
