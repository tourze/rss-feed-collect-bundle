<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Message;

final class OpmlImportMessage
{
    public function __construct(
        public readonly string $filePath,
        public readonly int $importJobId,
        public readonly ?int $userId = null,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            'OpmlImportMessage(filePath: %s, importJobId: %d, userId: %s)',
            $this->filePath,
            $this->importJobId,
            null !== $this->userId ? (string) $this->userId : 'null'
        );
    }
}
