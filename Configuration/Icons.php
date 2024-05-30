<?php
declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'deepl-main-menu-icon' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:ai_translate/Resources/Public/Icons/ai-dashboard.png',
    ],

    'deepl-settings-menu-icon' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:ai_translate/Resources/Public/Icons/settings.png',
    ],
    'actions-localize-deepl' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_translate/Resources/Public/Icons/actions-localize-deepl.svg',
    ],
    'actions-localize-google' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_translate/Resources/Public/Icons/actions-localize-google.svg',
    ],
    'actions-localize-openai' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_translate/Resources/Public/Icons/actions-localize-openai.svg',
    ],
    'actions-localize-geminiai' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_translate/Resources/Public/Icons/actions-localize-geminiai.svg',
    ],
    'actions-localize-claudeai' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ai_translate/Resources/Public/Icons/actions-localize-claudeai.svg',
    ],
];