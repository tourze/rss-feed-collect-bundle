<?php

// phpcs:ignoreFile
// @phpstan-ignore-next-line

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Service;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

/**
 * 简单的测试替身类，用于替代复杂的Mock
 *
 * 此类实现 Knp\Menu\ItemInterface 接口，该接口要求 setter 方法返回 self 以支持流式调用
 * 这与 NoReturnSetterMethodRule 规则冲突，但接口兼容性优先
 *
 * @internal
 * @phpstan-ignore-next-line
 */
final class TestMenuItem implements ItemInterface
{
    /** @var array<string, bool> */
    private array $addChildCalls = [];

    /** @var array<string, int> */
    private array $getChildCalls = [];

    /** @var array<string, ItemInterface|null> */
    private array $childrenToReturn = [];

    public function setChildToReturn(string $name, ?ItemInterface $child): void
    {
        $this->childrenToReturn[$name] = $child;
    }

    public function hasAddChildBeenCalled(string $name): bool
    {
        return isset($this->addChildCalls[$name]);
    }

    public function hasGetChildBeenCalled(string $name): bool
    {
        return isset($this->getChildCalls[$name]);
    }

    public function getGetChildCallCount(string $name): int
    {
        return $this->getChildCalls[$name] ?? 0;
    }

    public function addChild(mixed $child, array $options = []): ItemInterface
    {
        if (is_string($child)) {
            $this->addChildCalls[$child] = true;
        }

        return $this;
    }

    public function getChild(string $name): ?ItemInterface
    {
        $this->getChildCalls[$name] = ($this->getChildCalls[$name] ?? 0) + 1;
        if (isset($this->childrenToReturn[$name])) {
            return $this->childrenToReturn[$name];
        }

        return null;
    }

    public function getName(): string
    {
        return 'test-menu-item';
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setName(string $name): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setUri(?string $uri): ItemInterface
    {
        return $this;
    }

    public function getUri(): ?string
    {
        return null;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setAttribute(string $name, mixed $value): ItemInterface
    {
        return $this;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $default;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setAttributes(array $attributes): ItemInterface
    {
        return $this;
    }

    public function getAttributes(): array
    {
        return [];
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setLabel(?string $label): ItemInterface
    {
        return $this;
    }

    public function getLabel(): string
    {
        return '';
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setExtra(string $name, mixed $value): ItemInterface
    {
        return $this;
    }

    public function getExtra(string $name, mixed $default = null): mixed
    {
        return $default;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setExtras(array $extras): ItemInterface
    {
        return $this;
    }

    public function getExtras(): array
    {
        return [];
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setDisplay(bool $display): ItemInterface
    {
        return $this;
    }

    public function isDisplayed(): bool
    {
        return true;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setDisplayChildren(bool $displayChildren): ItemInterface
    {
        return $this;
    }

    public function getDisplayChildren(): bool
    {
        return true;
    }

    public function getParent(): ?ItemInterface
    {
        return null;
    }

    public function getChildren(): array
    {
        return [];
    }

    public function hasChildren(): bool
    {
        return false;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setParent(?ItemInterface $parent = null): ItemInterface
    {
        return $this;
    }

    public function isRoot(): bool
    {
        return false;
    }

    public function isLast(): bool
    {
        return false;
    }

    public function isFirst(): bool
    {
        return false;
    }

    public function getLevel(): int
    {
        return 0;
    }

    public function getRoot(): ItemInterface
    {
        return $this;
    }

    public function isCurrent(): bool
    {
        return false;
    }

    public function isCurrentAncestor(): bool
    {
        return false;
    }

    public function removeChild(mixed $name): ItemInterface
    {
        return $this;
    }

    public function reorderChildren(array $order): ItemInterface
    {
        return $this;
    }

    public function copy(): ItemInterface
    {
        return clone $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }

    public function count(): int
    {
        return 0;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator([]);
    }

    public function actsLikeFirst(): bool
    {
        return false;
    }

    public function actsLikeLast(): bool
    {
        return false;
    }

    public function getChildrenAttribute(string $name, mixed $default = null): mixed
    {
        return $default;
    }

    public function getChildrenAttributes(): array
    {
        return [];
    }

    public function getFirstChild(): ItemInterface
    {
        return new TestMenuItem();
    }

    public function getLabelAttribute(string $name, mixed $default = null): mixed
    {
        return $default;
    }

    public function getLabelAttributes(): array
    {
        return [];
    }

    public function getLastChild(): ItemInterface
    {
        return new TestMenuItem();
    }

    public function getLinkAttribute(string $name, mixed $default = null): mixed
    {
        return $default;
    }

    public function getLinkAttributes(): array
    {
        return [];
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setChildren(array $children): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setChildrenAttribute(string $name, mixed $value): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setChildrenAttributes(array $attributes): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setCurrent(?bool $bool = null): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setFactory(FactoryInterface $factory): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setLabelAttribute(string $name, mixed $value): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setLabelAttributes(array $attributes): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setLinkAttribute(string $name, mixed $value): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore-next-line symplify.noReturnSetterMethod */
    public function setLinkAttributes(array $attributes): ItemInterface
    {
        return $this;
    }
}
