<?php
defined('TYPO3_MODE') || die('Access denied.');
$extKey = $_EXTKEY ='ai_translate';
call_user_func(
    function ($extKey) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extKey, 'Configuration/TypoScript', 'ai_translate');
    },
    $_EXTKEY
);

//icons to icon registry
$iconRegistry         = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$deeplIconIdentifier = 'actions-localize-deepl';
$googleIconIdentifier = 'actions-localize-google';
$openaiIconIdentifier = 'actions-localize-openai';
$geminiaiIconIdentifier = 'actions-localize-geminiai';
$claudeaiIconIdentifier = 'actions-localize-claudeai';

$iconRegistry->registerIcon(
    $deeplIconIdentifier,
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:ai_translate/Resources/Public/Icons/' . $deeplIconIdentifier . '.svg']
);

$iconRegistry->registerIcon(
    $googleIconIdentifier,
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:ai_translate/Resources/Public/Icons/' . $googleIconIdentifier . '.svg']
);

$iconRegistry->registerIcon(
    $openaiIconIdentifier,
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:ai_translate/Resources/Public/Icons/' . $openaiIconIdentifier . '.svg']
);

$iconRegistry->registerIcon(
    $geminiaiIconIdentifier,
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:ai_translate/Resources/Public/Icons/' . $geminiaiIconIdentifier . '.svg']
);
$iconRegistry->registerIcon(
    $claudeaiIconIdentifier,
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:ai_translate/Resources/Public/Icons/' . $claudeaiIconIdentifier . '.svg']
);
//register backend module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'PITS.ai_translate',
    'Deepl',
    '',
    '',
    array(),
    array(
        'access' => 'admin',
        'icon'   => 'EXT:ai_translate/Resources/Public/Icons/ai-dashboard.png',
        'labels' => 'LLL:EXT:ai_translate/Resources/Private/Language/locallang.xlf',
    )
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'PITS.ai_translate',
    'Deepl',
    'Settings',
    '',
    array(
        \PITS\AiTranslate\Controller\SettingsController::class => 'index,saveSettings',
    ),
    array(
        'icon'   => 'EXT:ai_translate/Resources/Public/Icons/settings.png',
        'access' => 'user,group',
        'labels' => 'LLL:EXT:ai_translate/Resources/Private/Language/locallang_module_settings.xlf',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => true,
    )
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['/typo3/sysext/backend/Resources/Private/Language/locallang_layout.xlf'] = 'EXT:ai_translate/Resources/Private/Language/locallang.xlf';
