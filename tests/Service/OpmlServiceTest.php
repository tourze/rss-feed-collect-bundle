<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Service\OpmlService;

/**
 * @internal
 */
#[CoversClass(OpmlService::class)]
#[RunTestsInSeparateProcesses]
class OpmlServiceTest extends AbstractIntegrationTestCase
{
    private OpmlService $service;

    public function testParseOpmlFileBasic(): void
    {
        $opmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<opml version="2.0">
    <head>
        <title>My RSS Feeds</title>
        <dateCreated>Wed, 01 Jan 2025 12:00:00 GMT</dateCreated>
        <ownerName>John Doe</ownerName>
        <ownerEmail>john@example.com</ownerEmail>
    </head>
    <body>
        <outline type="rss" text="Tech Blog" title="Tech Blog" xmlUrl="https://techblog.com/rss" htmlUrl="https://techblog.com" description="A technology blog"/>
        <outline type="rss" text="News Feed" title="News Feed" xmlUrl="https://news.com/rss" htmlUrl="https://news.com"/>
    </body>
</opml>';

        $result = $this->service->parseOpmlFile($opmlContent);

        $this->assertEquals('My RSS Feeds', $result['title']);
        $this->assertEquals('Wed, 01 Jan 2025 12:00:00 GMT', $result['dateCreated']);
        $this->assertEquals('John Doe', $result['ownerName']);
        $this->assertEquals('john@example.com', $result['ownerEmail']);
        $this->assertCount(2, $result['feeds']);

        $this->assertEquals('Tech Blog', $result['feeds'][0]['name']);
        $this->assertEquals('https://techblog.com/rss', $result['feeds'][0]['url']);
        $this->assertEquals('A technology blog', $result['feeds'][0]['description'] ?? '');

        $this->assertEquals('News Feed', $result['feeds'][1]['name']);
        $this->assertEquals('https://news.com/rss', $result['feeds'][1]['url']);
    }

    public function testParseOpmlFileWithCategories(): void
    {
        $opmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<opml version="2.0">
    <head>
        <title>Categorized Feeds</title>
    </head>
    <body>
        <outline text="Technology" title="Technology">
            <outline type="rss" text="TechCrunch" title="TechCrunch" xmlUrl="https://techcrunch.com/feed"/>
            <outline type="rss" text="Ars Technica" title="Ars Technica" xmlUrl="https://arstechnica.com/feed"/>
        </outline>
        <outline text="News" title="News">
            <outline type="rss" text="BBC News" title="BBC News" xmlUrl="https://bbc.com/rss"/>
        </outline>
        <outline type="rss" text="Uncategorized" title="Uncategorized" xmlUrl="https://uncategorized.com/rss"/>
    </body>
</opml>';

        $result = $this->service->parseOpmlFile($opmlContent);

        $this->assertCount(4, $result['feeds']);

        // 检查分类feeds
        $techFeeds = array_filter($result['feeds'], fn ($feed) => ($feed['category'] ?? '') === 'Technology');
        $this->assertCount(2, $techFeeds);

        $newsFeeds = array_filter($result['feeds'], fn ($feed) => ($feed['category'] ?? '') === 'News');
        $this->assertCount(1, $newsFeeds);

        // 检查无分类feeds
        $uncategorizedFeeds = array_filter($result['feeds'], fn ($feed) => !isset($feed['category']));
        $this->assertCount(1, $uncategorizedFeeds);
    }

    public function testParseOpmlFileOpml10(): void
    {
        $opmlContent = '<?xml version="1.0"?>
<opml version="1.0">
    <head>
        <title>OPML 1.0 Test</title>
    </head>
    <body>
        <outline text="Feed 1" url="https://example.com/rss" xmlUrl="https://example.com/rss"/>
    </body>
</opml>';

        $result = $this->service->parseOpmlFile($opmlContent);

        $this->assertEquals('OPML 1.0 Test', $result['title']);
        $this->assertCount(1, $result['feeds']);
        $this->assertEquals('Feed 1', $result['feeds'][0]['name']);
        $this->assertEquals('https://example.com/rss', $result['feeds'][0]['url']);
    }

    public function testParseOpmlFileMinimal(): void
    {
        $opmlContent = '<?xml version="1.0"?>
<opml>
    <body>
        <outline xmlUrl="https://minimal.com/rss"/>
    </body>
</opml>';

        $result = $this->service->parseOpmlFile($opmlContent);

        $this->assertEquals('Untitled', $result['title']); // 默认title
        $this->assertCount(1, $result['feeds']);
        $this->assertEquals('https://minimal.com/rss', $result['feeds'][0]['name']); // URL作为名称
        $this->assertEquals('https://minimal.com/rss', $result['feeds'][0]['url']);
    }

    public function testParseOpmlFileThrowsExceptionForEmptyContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OPML content cannot be empty');

        $this->service->parseOpmlFile('');
    }

    public function testParseOpmlFileThrowsExceptionForInvalidXml(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML format in OPML file');

        $this->service->parseOpmlFile('<invalid>xml<content>');
    }

    public function testValidateOpmlStructureValid(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<?xml version="1.0"?>
<opml version="2.0">
    <head><title>Test</title></head>
    <body><outline xmlUrl="test"/></body>
</opml>');

        // 不应该抛出异常
        $this->service->validateOpmlStructure($dom);

        $this->expectNotToPerformAssertions(); // 仅验证不抛出异常
    }

    public function testValidateOpmlStructureThrowsExceptionForMissingOpmlRoot(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><root><body></body></root>');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid OPML format: missing opml root element');

        $this->service->validateOpmlStructure($dom);
    }

    public function testValidateOpmlStructureThrowsExceptionForMissingBody(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><opml><head></head></opml>');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid OPML format: missing body element');

        $this->service->validateOpmlStructure($dom);
    }

    public function testValidateOpmlStructureThrowsExceptionForUnsupportedVersion(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<?xml version="1.0"?><opml version="3.0"><head></head><body></body></opml>');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported OPML version: 3.0');

        $this->service->validateOpmlStructure($dom);
    }

    public function testExportToOpmlBasic(): void
    {
        $feeds = [
            $this->createRssFeed('Tech Feed', 'https://tech.com/rss', 'Technology blog'),
            $this->createRssFeed('News Feed', 'https://news.com/rss', 'Daily news'),
        ];

        $metadata = [
            'title' => 'My Export',
            'ownerName' => 'Test User',
            'ownerEmail' => 'test@example.com',
        ];

        $result = $this->service->exportToOpml($feeds, $metadata);

        $this->assertStringContainsString('<opml version="2.0">', $result);
        $this->assertStringContainsString('<title>My Export</title>', $result);
        $this->assertStringContainsString('<ownerName>Test User</ownerName>', $result);
        $this->assertStringContainsString('<ownerEmail>test@example.com</ownerEmail>', $result);
        $this->assertStringContainsString('xmlUrl="https://tech.com/rss"', $result);
        $this->assertStringContainsString('xmlUrl="https://news.com/rss"', $result);
    }

    private function createRssFeed(string $name, string $url, ?string $description = null, ?string $category = null): RssFeed
    {
        $feed = new RssFeed();
        $feed->setName($name);
        $feed->setUrl($url);
        $feed->setDescription($description);
        $feed->setCategory($category);
        $feed->setIsActive(true);
        $feed->setCreateTime(new \DateTimeImmutable());
        $feed->setUpdateTime(new \DateTimeImmutable());

        return $feed;
    }

    public function testExportToOpmlWithCategories(): void
    {
        $feeds = [
            $this->createRssFeed('Tech Feed', 'https://tech.com/rss', 'Tech blog', 'Technology'),
            $this->createRssFeed('Another Tech', 'https://tech2.com/rss', 'More tech', 'Technology'),
            $this->createRssFeed('News Feed', 'https://news.com/rss', 'News', 'News'),
            $this->createRssFeed('Uncategorized', 'https://other.com/rss'),
        ];

        $result = $this->service->exportToOpml($feeds);

        // 应该包含分类outline
        $this->assertStringContainsString('<outline text="Technology" title="Technology">', $result);
        $this->assertStringContainsString('<outline text="News" title="News">', $result);

        // 验证XML结构
        $dom = new \DOMDocument();
        $dom->loadXML($result);

        $xpath = new \DOMXPath($dom);

        // 检查Technology分类下的feeds
        $techFeeds = $xpath->query('//outline[@text="Technology"]/outline[@xmlUrl]');
        $this->assertNotFalse($techFeeds);
        $this->assertEquals(2, $techFeeds->length);

        // 检查News分类下的feeds
        $newsFeeds = $xpath->query('//outline[@text="News"]/outline[@xmlUrl]');
        $this->assertNotFalse($newsFeeds);
        $this->assertEquals(1, $newsFeeds->length);

        // 检查直接在body下的无分类feeds
        $uncategorizedFeeds = $xpath->query('/opml/body/outline[@xmlUrl]');
        $this->assertNotFalse($uncategorizedFeeds);
        $this->assertEquals(1, $uncategorizedFeeds->length);
    }

    public function testExportToOpmlEmpty(): void
    {
        $result = $this->service->exportToOpml([]);

        $this->assertStringContainsString('<opml version="2.0">', $result);
        $this->assertStringContainsString('<title>RSS Feed Collection</title>', $result);
        $this->assertStringContainsString('<body/>', $result);
    }

    public function testExportToOpmlWithSpecialCharacters(): void
    {
        $feeds = [
            $this->createRssFeed('Feed with "quotes" & <brackets>', 'https://example.com/rss', 'Description with & special chars'),
        ];

        $metadata = [
            'title' => 'Title with "quotes" & <brackets>',
            'ownerName' => 'Owner & Name',
        ];

        $result = $this->service->exportToOpml($feeds, $metadata);

        // 验证特殊字符被正确转义（属性中会双重转义&）
        $this->assertStringContainsString('&quot;quotes&quot; &amp;amp; &amp;lt;brackets&amp;gt;', $result);

        // 验证生成的XML是有效的
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($result));
    }

    public function testRoundTripParseAndExport(): void
    {
        // 创建一些feeds
        $originalFeeds = [
            $this->createRssFeed('Tech Feed', 'https://tech.com/rss', 'Technology blog', 'Technology'),
            $this->createRssFeed('News Feed', 'https://news.com/rss', 'Daily news', 'News'),
            $this->createRssFeed('Uncategorized', 'https://other.com/rss', 'No category'),
        ];

        // 导出为OPML
        $opmlContent = $this->service->exportToOpml($originalFeeds, [
            'title' => 'Test Export',
            'ownerName' => 'Test User',
        ]);

        // 解析导出的OPML
        $parsed = $this->service->parseOpmlFile($opmlContent);

        // 验证解析结果
        $this->assertEquals('Test Export', $parsed['title']);
        $this->assertEquals('Test User', $parsed['ownerName']);
        $this->assertCount(3, $parsed['feeds']);

        // 验证feeds的内容（不验证顺序，因为分类可能改变顺序）
        $feedsByUrl = [];
        foreach ($parsed['feeds'] as $feed) {
            $feedsByUrl[$feed['url']] = $feed;
        }

        $this->assertEquals('Tech Feed', $feedsByUrl['https://tech.com/rss']['name']);
        $this->assertEquals('Technology', $feedsByUrl['https://tech.com/rss']['category'] ?? '');

        $this->assertEquals('News Feed', $feedsByUrl['https://news.com/rss']['name']);
        $this->assertEquals('News', $feedsByUrl['https://news.com/rss']['category'] ?? '');

        $this->assertEquals('Uncategorized', $feedsByUrl['https://other.com/rss']['name']);
        $this->assertArrayNotHasKey('category', $feedsByUrl['https://other.com/rss']);
    }

    protected function onSetUp(): void
    {
        $service = self::getContainer()->get(OpmlService::class);
        if (!$service instanceof OpmlService) {
            throw new \RuntimeException('Expected OpmlService instance from container');
        }
        $this->service = $service;
    }
}
