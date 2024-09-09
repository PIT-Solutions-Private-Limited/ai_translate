<?php
defined('TYPO3_MODE') or die();
use TYPO3\CMS\Backend\Controller\PageLayoutController;


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ai_translate/Configuration/TsConfig/Page/pagetsconfig.txt">'
);
//hook for translate content
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl'] = 'PITS\\AiTranslate\\Hooks\\TranslateHook';
//hook for overriding localization.js,recordlist.js and including deepl.css
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['deepl'] = 'PITS\\AiTranslate\\Hooks\\TranslateHook->executePreRenderHook';

//xclass localizationcontroller for localizeRecords() and process() action
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\Page\\LocalizationController'] = array(
    'className' => 'PITS\\AiTranslate\\Override\\LocalizationController',
);

//xclass databaserecordlist for rendering custom checkboxes to toggle deepl selection in recordlist
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array(
    'className' => 'PITS\\AiTranslate\\Override\\DatabaseRecordList',
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][PageLayoutController::class] = [
    'className' => PITS\AiTranslate\Xclass\PageLayoutControllerXClass::class
];



