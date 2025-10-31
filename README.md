# RSS Feed Collect Bundle

[English](README.md) | [中文](README.zh-CN.md)

RSS源抓取收集Bundle，提供定时抓取RSS源内容并存储到数据库的功能。

## 核心功能

- **RSS源管理**: 支持多个RSS源的配置和管理
- **自动抓取**: 基于配置的间隔时间自动抓取RSS内容  
- **智能去重**: 基于文章链接自动去重，避免重复存储
- **状态管理**: 跟踪每个RSS源的抓取状态和错误信息
- **Console命令**: 支持手动触发和批量抓取

## 快速开始

### 1. 安装依赖

```bash
composer install
```

### 2. 运行数据迁移

```bash
php bin/console doctrine:migrations:migrate
```

### 3. 加载测试数据

```bash
php bin/console doctrine:fixtures:load
```

### 4. 执行RSS抓取

```bash
# 抓取单个RSS源 (必须指定feed-id)
php bin/console rss:collect-feed --feed-id=1

# 强制抓取单个RSS源
php bin/console rss:collect-feed --feed-id=1 --force

# 抓取所有到期的RSS源
php bin/console rss:collect-feeds

# 强制抓取所有活跃RSS源
php bin/console rss:collect-feeds --force

# 查看统计信息
php bin/console rss:collect-feeds --stats
```

## 测试用RSS源

Bundle预置了百度科技新闻RSS源用于真实测试：

- **名称**: 百度-科技最新
- **URL**: https://news.baidu.com/n?cmd=4&class=technnews&tn=rss
- **抓取间隔**: 30分钟
- **用途**: 可发起真实HTTP请求进行功能验证

## 架构设计

### 实体结构

```
RssFeed (RSS源)
├── id: 主键
├── name: RSS源名称
├── url: RSS源URL地址
├── category: 分类
├── collectIntervalMinutes: 抓取间隔(分钟)
├── status: 状态(active/error/disabled)
├── lastCollectTime: 最后抓取时间
├── lastError: 最后错误信息
└── itemsCount: 文章总数

RssItem (RSS文章)
├── id: 主键  
├── title: 文章标题
├── link: 文章链接 (去重键)
├── description: 文章描述
├── content: 文章内容
├── guid: 文章GUID
├── publishTime: 发布时间
└── rssFeed: 关联RSS源
```

### 服务层

- `RssFeedCollectService`: 核心抓取服务，处理HTTP请求、XML解析、数据存储
- `RssFeedRepository`: RSS源数据访问层
- `RssItemRepository`: RSS文章数据访问层

## Console命令

### rss:collect-feed

单个RSS源抓取命令，专门处理指定RSS源的抓取。

```bash
# 抓取指定RSS源
php bin/console rss:collect-feed --feed-id=1

# 强制抓取（忽略抓取间隔）
php bin/console rss:collect-feed --feed-id=1 --force
```

### rss:collect-feeds

批量RSS源抓取命令，遍历所有RSS源并执行抓取。

```bash
# 抓取所有到期的RSS源
php bin/console rss:collect-feeds

# 强制抓取所有活跃RSS源  
php bin/console rss:collect-feeds --force

# 查看统计信息
php bin/console rss:collect-feeds --stats
```

## 环境配置

```bash
# RSS抓取超时时间(秒)，默认30
RSS_COLLECT_TIMEOUT=30

# RSS抓取User-Agent，默认"RSS Feed Collector Bot/1.0"  
RSS_COLLECT_USER_AGENT="RSS Feed Collector Bot/1.0"
```

## 运行测试

```bash
# 运行所有测试
./vendor/bin/phpunit

# 运行静态分析
php -d memory_limit=2G vendor/bin/phpstan analyse --level=8
```

## 使用示例

### 添加新的RSS源

```php
$rssFeed = new RssFeed();
$rssFeed->setName('技术博客');
$rssFeed->setUrl('https://example.com/feed.xml');
$rssFeed->setCategory('技术');
$rssFeed->setCollectIntervalMinutes(60);
$rssFeed->setIsActive(true);

$entityManager->persist($rssFeed);
$entityManager->flush();
```

### 手动抓取RSS源

```php
$collectService = $container->get(RssFeedCollectService::class);
$result = $collectService->collectFeed($rssFeed);

if ($result['success']) {
    echo "抓取成功，新增文章: " . $result['items_count'];
} else {
    echo "抓取失败: " . $result['error'];
}
```