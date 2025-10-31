<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RSSFeedCollectBundle\Message\OpmlImportMessage;

/**
 * @internal
 */
#[CoversClass(OpmlImportMessage::class)]
class OpmlImportMessageTest extends TestCase
{
    public function testCreateOpmlImportMessage(): void
    {
        $filePath = '/path/to/file.opml';
        $importJobId = 123;
        $userId = 456;

        $message = new OpmlImportMessage($filePath, $importJobId, $userId);

        $this->assertSame($filePath, $message->filePath);
        $this->assertSame($importJobId, $message->importJobId);
        $this->assertSame($userId, $message->userId);
    }

    public function testCreateOpmlImportMessageWithoutUserId(): void
    {
        $filePath = '/path/to/file.opml';
        $importJobId = 123;

        $message = new OpmlImportMessage($filePath, $importJobId);

        $this->assertSame($filePath, $message->filePath);
        $this->assertSame($importJobId, $message->importJobId);
        $this->assertNull($message->userId);
    }

    public function testToStringWithUserId(): void
    {
        $filePath = '/path/to/file.opml';
        $importJobId = 123;
        $userId = 456;

        $message = new OpmlImportMessage($filePath, $importJobId, $userId);

        $expected = 'OpmlImportMessage(filePath: /path/to/file.opml, importJobId: 123, userId: 456)';
        $this->assertSame($expected, $message->__toString());
        $this->assertSame($expected, (string) $message);
    }

    public function testToStringWithoutUserId(): void
    {
        $filePath = '/path/to/file.opml';
        $importJobId = 123;

        $message = new OpmlImportMessage($filePath, $importJobId);

        $expected = 'OpmlImportMessage(filePath: /path/to/file.opml, importJobId: 123, userId: null)';
        $this->assertSame($expected, $message->__toString());
        $this->assertSame($expected, (string) $message);
    }
}
