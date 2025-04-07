<?php

use PITS\AiTranslate\Override\DatabaseRecordList;
use PITS\AiTranslate\Override\LocalizationController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList as CoreDatabaseRecordList;
use TYPO3\CMS\Backend\Controller\Page\LocalizationController as CoreLocalizationController;

defined('TYPO3') or die();

//hook for translate content
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl'] = 'PITS\\AiTranslate\\Hooks\\TranslateHook';
//hook for overriding localization.js,recordlist.js and including deepl.css
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['deepl'] = 'PITS\\AiTranslate\\Hooks\\TranslateHook->executePreRenderHook';

//xclass localizationcontroller for localizeRecords() and process() action
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][CoreLocalizationController::class] = [
    'className' => LocalizationController::class,
];

//xclass databaserecordlist for rendering custom checkboxes to toggle deepl selection in recordlist
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][CoreDatabaseRecordList::class] = [
    'className' => DatabaseRecordList::class,
];
//To do : Change xclass to event. possible one TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent

// Place the translate button in File List just after the regular translations button
ExtensionManagementUtility::addUserTSConfig(
    '@import "EXT:ai_translate/Configuration/TsConfig/User/user.tsconfig"',
);
