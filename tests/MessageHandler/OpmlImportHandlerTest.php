<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RSSFeedCollectBundle\MessageHandler\OpmlImportHandler;

/**
 * @internal
 */
#[CoversClass(OpmlImportHandler::class)]
#[RunTestsInSeparateProcesses]
class OpmlImportHandlerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testHandlerExists(): void
    {
        $handler = self::getContainer()->get(OpmlImportHandler::class);
        $this->assertInstanceOf(OpmlImportHandler::class, $handler);
    }
}
