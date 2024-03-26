<?php
defined('TYPO3') or die();

call_user_func(function()
{
   $extensionKey = 'ai_translate';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript', 'ai_translate');
});