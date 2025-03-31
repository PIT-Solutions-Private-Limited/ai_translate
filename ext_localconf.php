<?php
defined('TYPO3') or die();
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;

//hook for translate content
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl'] = 'PITS\\AiTranslate\\Hooks\\TranslateHook';
//hook for overriding localization.js,recordlist.js and including deepl.css
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['deepl'] = 'PITS\\AiTranslate\\Hooks\\TranslateHook->executePreRenderHook';

//xclass localizationcontroller for localizeRecords() and process() action
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Backend\Controller\Page\LocalizationController::class] = [
    'className' => \PITS\AiTranslate\Override\LocalizationController::class,
];

//xclass databaserecordlist for rendering custom checkboxes to toggle deepl selection in recordlist
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class] = [
    'className' => \PITS\AiTranslate\Override\DatabaseRecordList::class,
];
//To do : Change xclass to event. possible one TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent
