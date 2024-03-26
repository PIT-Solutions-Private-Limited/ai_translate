<?php
defined('TYPO3') || die('Access denied.');
$extKey = $_EXTKEY ='ai_translate';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:backend/Resources/Private/Language/locallang_layout.xlf'][] = 'EXT:ai_translate/Resources/Private/Language/locallang.xlf';
