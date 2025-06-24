<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

final class Date extends AbstractTag
{
    protected string $name = 'date';

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        echo $this->datetime(
            trim($innerHTML),
            $attrs['df'] ?? 'long',
            $attrs['tf'] ?? 'short',
            $attrs['locale'] ?? null
        );
    }

    private function datetime(
        string $dtm,
        string $dateFormat = 'long',
        string $timeFormat = 'short',
        string $locale = null
    ): string {
        $formats = [
            'none' => \IntlDateFormatter::NONE,
            'short' => \IntlDateFormatter::SHORT,
            'medium' => \IntlDateFormatter::MEDIUM,
            'long' => \IntlDateFormatter::LONG,
            'full' => \IntlDateFormatter::FULL,
        ];

        $formatter = \IntlDateFormatter::create(
            $locale ?: 'en_US',
            $formats[$dateFormat] ?? throw new \RuntimeException(
            "Unknown date format '$dateFormat' for IntlDateFormatter"
        ),
            $formats[$timeFormat] ?? throw new \RuntimeException(
            "Unknown time format '$dateFormat' for IntlDateFormatter"
        ),
        );

        return $formatter->format(strtotime($dtm)) ?: '';
    }
}