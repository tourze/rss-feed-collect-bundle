<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Service;

use Tourze\RSSFeedCollectBundle\Entity\RssFeed;

/**
 * OPML 文件解析和生成服务
 * 支持 OPML 1.0 和 2.0 格式
 */
final class OpmlService
{
    private const SUPPORTED_VERSIONS = ['1.0', '1.1', '2.0'];

    /**
     * 解析OPML文件
     *
     * @param string $opmlContent OPML文件内容
     * @return array{title: string, dateCreated: ?string, dateModified: ?string, ownerName: ?string, ownerEmail: ?string, feeds: array<array{name: string, url: string, description?: string, category?: string}>}
     * @throws \InvalidArgumentException 当OPML格式无效时
     */
    public function parseOpmlFile(string $opmlContent): array
    {
        if ('' === $opmlContent) {
            throw new \InvalidArgumentException('OPML content cannot be empty');
        }

        $dom = $this->loadXmlDocument($opmlContent);
        $this->validateOpmlStructure($dom);
        $xpath = new \DOMXPath($dom);

        $head = $this->parseOpmlHead($xpath);
        $feeds = $this->parseOpmlFeeds($xpath);

        return [
            'title' => $head['title'],
            'dateCreated' => $head['dateCreated'],
            'dateModified' => $head['dateModified'],
            'ownerName' => $head['ownerName'],
            'ownerEmail' => $head['ownerEmail'],
            'feeds' => $feeds,
        ];
    }

    /**
     * 加载XML文档
     */
    private function loadXmlDocument(string $opmlContent): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = true;

        $previousErrorReporting = libxml_use_internal_errors(true);

        try {
            if (!$dom->loadXML($opmlContent)) {
                throw new \InvalidArgumentException('Invalid XML format in OPML file');
            }
        } finally {
            libxml_use_internal_errors($previousErrorReporting);
        }

        return $dom;
    }

    /**
     * 验证OPML结构
     *
     * @throws \InvalidArgumentException 当OPML结构无效时
     */
    public function validateOpmlStructure(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);

        $this->validateRootElement($xpath);
        $this->validateBodyElement($xpath);
        $this->validateVersion($xpath);
    }

    /**
     * 验证根元素
     */
    private function validateRootElement(\DOMXPath $xpath): void
    {
        $opmlNodes = $xpath->query('//opml');
        if (false === $opmlNodes || 0 === $opmlNodes->length) {
            throw new \InvalidArgumentException('Invalid OPML format: missing opml root element');
        }
    }

    /**
     * 验证body元素
     */
    private function validateBodyElement(\DOMXPath $xpath): void
    {
        $bodyNodes = $xpath->query('//opml/body');
        if (false === $bodyNodes || 0 === $bodyNodes->length) {
            throw new \InvalidArgumentException('Invalid OPML format: missing body element');
        }
    }

    /**
     * 验证OPML版本
     */
    private function validateVersion(\DOMXPath $xpath): void
    {
        $opmlNodes = $xpath->query('//opml');
        if (false === $opmlNodes || 0 === $opmlNodes->length) {
            return;
        }

        $opmlElement = $opmlNodes->item(0);
        if (!$opmlElement instanceof \DOMElement) {
            return;
        }

        $version = $opmlElement->getAttribute('version');
        if ('' !== $version && !in_array($version, self::SUPPORTED_VERSIONS, true)) {
            throw new \InvalidArgumentException("Unsupported OPML version: {$version}");
        }
    }

    /**
     * 解析OPML头部信息
     *
     * @return array{title: string, dateCreated: ?string, dateModified: ?string, ownerName: ?string, ownerEmail: ?string}
     */
    private function parseOpmlHead(\DOMXPath $xpath): array
    {
        return [
            'title' => $this->getHeadElement($xpath, 'title') ?? 'Untitled',
            'dateCreated' => $this->getHeadElement($xpath, 'dateCreated'),
            'dateModified' => $this->getHeadElement($xpath, 'dateModified'),
            'ownerName' => $this->getHeadElement($xpath, 'ownerName'),
            'ownerEmail' => $this->getHeadElement($xpath, 'ownerEmail'),
        ];
    }

    /**
     * 获取头部元素值
     */
    private function getHeadElement(\DOMXPath $xpath, string $elementName): ?string
    {
        $nodes = $xpath->query("//opml/head/{$elementName}");
        if (false === $nodes || 0 === $nodes->length) {
            return null;
        }

        $node = $nodes->item(0);
        if (null === $node || !$node instanceof \DOMNode) {
            return null;
        }

        $content = trim($node->textContent);

        return '' !== $content ? $content : null;
    }

    /**
     * 解析OPML中的feeds
     *
     * @return array<array{name: string, url: string, description?: string, category?: string}>
     */
    private function parseOpmlFeeds(\DOMXPath $xpath): array
    {
        $feeds = [];
        $feedNodes = $xpath->query('//outline[@xmlUrl]');

        if (false === $feedNodes) {
            return [];
        }

        foreach ($feedNodes as $feedNode) {
            if (!$feedNode instanceof \DOMElement) {
                continue;
            }

            $feedData = $this->parseFeedNode($feedNode, $xpath);
            if (null !== $feedData) {
                $feeds[] = $feedData;
            }
        }

        return $feeds;
    }

    /**
     * 解析单个feed节点
     *
     * @return array{name: string, url: string, description?: string, category?: string}|null
     */
    private function parseFeedNode(\DOMElement $feedNode, \DOMXPath $xpath): ?array
    {
        $url = trim($feedNode->getAttribute('xmlUrl'));
        if ('' === $url) {
            return null;
        }

        $title = $feedNode->getAttribute('title');
        $text = $feedNode->getAttribute('text');
        $name = '' !== $title ? $title : ('' !== $text ? $text : $url);
        $feed = [
            'name' => trim($name),
            'url' => $url,
        ];

        $description = $feedNode->getAttribute('description');
        if ('' !== $description) {
            $feed['description'] = trim($description);
        }

        $category = $this->getFeedCategory($feedNode);
        if (null !== $category && '' !== $category) {
            $feed['category'] = $category;
        }

        return $feed;
    }

    /**
     * 获取feed的分类
     */
    private function getFeedCategory(\DOMElement $feedNode): ?string
    {
        $parentNode = $feedNode->parentNode;

        if (null === $parentNode || 'body' === $parentNode->nodeName) {
            return null;
        }

        if (!$parentNode instanceof \DOMElement || 'outline' !== $parentNode->nodeName || $parentNode->hasAttribute('xmlUrl')) {
            return null;
        }

        $title = $parentNode->getAttribute('title');
        $text = $parentNode->getAttribute('text');
        $category = '' !== $title ? $title : ('' !== $text ? $text : '');

        return '' !== $category ? trim($category) : null;
    }

    /**
     * 将RSS Feed列表导出为OPML格式
     *
     * @param RssFeed[] $feeds
     * @param array{title?: string, ownerName?: string, ownerEmail?: string} $metadata
     */
    public function exportToOpml(array $feeds, array $metadata = []): string
    {
        $dom = $this->createOpmlDocument();
        $opml = $this->createOpmlRoot($dom);
        $head = $this->createOpmlHead($dom, $opml, $metadata);
        $body = $this->createOpmlBody($dom, $opml, $feeds);

        $xml = $dom->saveXML();

        return false === $xml ? '' : $xml;
    }

    /**
     * 创建OPML文档
     */
    private function createOpmlDocument(): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        return $dom;
    }

    /**
     * 创建OPML根元素
     */
    private function createOpmlRoot(\DOMDocument $dom): \DOMElement
    {
        $opml = $dom->createElement('opml');
        $opml->setAttribute('version', '2.0');
        $dom->appendChild($opml);

        return $opml;
    }

    /**
     * 创建OPML头部
     *
     * @param array{title?: string, ownerName?: string, ownerEmail?: string} $metadata
     */
    private function createOpmlHead(\DOMDocument $dom, \DOMElement $opml, array $metadata): \DOMElement
    {
        $head = $dom->createElement('head');
        $opml->appendChild($head);

        $title = $metadata['title'] ?? 'RSS Feed Collection';
        $head->appendChild($dom->createElement('title', htmlspecialchars($title, ENT_XML1, 'UTF-8')));

        $now = date('r');
        $head->appendChild($dom->createElement('dateCreated', $now));
        $head->appendChild($dom->createElement('dateModified', $now));

        if (isset($metadata['ownerName'])) {
            $head->appendChild($dom->createElement('ownerName', htmlspecialchars($metadata['ownerName'], ENT_XML1, 'UTF-8')));
        }

        if (isset($metadata['ownerEmail'])) {
            $head->appendChild($dom->createElement('ownerEmail', htmlspecialchars($metadata['ownerEmail'], ENT_XML1, 'UTF-8')));
        }

        return $head;
    }

    /**
     * 创建OPML主体
     *
     * @param RssFeed[] $feeds
     */
    private function createOpmlBody(\DOMDocument $dom, \DOMElement $opml, array $feeds): \DOMElement
    {
        $body = $dom->createElement('body');
        $opml->appendChild($body);

        $categorizedFeeds = $this->categorizeFeedsForExport($feeds);

        foreach ($categorizedFeeds as $category => $categoryFeeds) {
            if ('' === $category) {
                $this->addFeedsToBody($dom, $body, $categoryFeeds);
            } else {
                $this->addCategorizedFeeds($dom, $body, $category, $categoryFeeds);
            }
        }

        return $body;
    }

    /**
     * 按分类对feeds进行分组
     *
     * @param RssFeed[] $feeds
     * @return array<string, RssFeed[]>
     */
    private function categorizeFeedsForExport(array $feeds): array
    {
        $categorized = [];

        foreach ($feeds as $feed) {
            $category = $feed->getCategory() ?? '';
            $categorized[$category] ??= [];
            $categorized[$category][] = $feed;
        }

        return $categorized;
    }

    /**
     * 添加无分类的feeds到body
     *
     * @param RssFeed[] $feeds
     */
    private function addFeedsToBody(\DOMDocument $dom, \DOMElement $body, array $feeds): void
    {
        foreach ($feeds as $feed) {
            $body->appendChild($this->createFeedOutline($dom, $feed));
        }
    }

    /**
     * 创建feed的outline元素
     */
    private function createFeedOutline(\DOMDocument $dom, RssFeed $feed): \DOMElement
    {
        $outline = $dom->createElement('outline');

        $outline->setAttribute('type', 'rss');
        $outline->setAttribute('text', htmlspecialchars($feed->getName(), ENT_XML1, 'UTF-8'));
        $outline->setAttribute('title', htmlspecialchars($feed->getName(), ENT_XML1, 'UTF-8'));
        $outline->setAttribute('xmlUrl', htmlspecialchars($feed->getUrl(), ENT_XML1, 'UTF-8'));
        $outline->setAttribute('htmlUrl', htmlspecialchars($feed->getUrl(), ENT_XML1, 'UTF-8'));

        if (null !== $feed->getDescription()) {
            $outline->setAttribute('description', htmlspecialchars($feed->getDescription(), ENT_XML1, 'UTF-8'));
        }

        return $outline;
    }

    /**
     * 添加有分类的feeds
     *
     * @param RssFeed[] $categoryFeeds
     */
    private function addCategorizedFeeds(\DOMDocument $dom, \DOMElement $body, string $category, array $categoryFeeds): void
    {
        $categoryOutline = $dom->createElement('outline');
        $categoryOutline->setAttribute('text', htmlspecialchars($category, ENT_XML1, 'UTF-8'));
        $categoryOutline->setAttribute('title', htmlspecialchars($category, ENT_XML1, 'UTF-8'));

        foreach ($categoryFeeds as $feed) {
            $categoryOutline->appendChild($this->createFeedOutline($dom, $feed));
        }

        $body->appendChild($categoryOutline);
    }
}
