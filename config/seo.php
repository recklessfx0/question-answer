<?php

declare(strict_types=1);

/**
 * Core SEO, sitemap, feeds, Markdown and crawler policy baseline (ADR 0002, SEO.md).
 */
return [
    'sitemap' => [
        'max_urls_per_file' => 50000,
        'include_users' => false,
    ],
    'feeds' => [
        'max_entries' => 50,
    ],
    'markdown' => [
        'enabled' => true,
        'content_negotiation' => true,
    ],
    'social_cards' => [
        'generate_images' => true,
        'width' => 1200,
        'height' => 630,
    ],
    'crawlers' => [
        'search_indexing' => 'allow',
        'user_fetch' => 'allow',
        'model_training' => 'disallow',
    ],
    'indexnow' => [
        'enabled' => false,
        'max_batch_urls' => 10000,
    ],
];
