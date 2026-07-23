<?php

defined('TYPO3') or die;

// Ensure teaser and bodytext are copied into translated news records
// (prefixed with "[Translate to ...]") so translation handling works correctly.
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')
    && isset($GLOBALS['TCA']['tx_news_domain_model_news']['columns'])
) {
    foreach (['title', 'teaser', 'bodytext'] as $field) {
        if (isset($GLOBALS['TCA']['tx_news_domain_model_news']['columns'][$field])) {
            $GLOBALS['TCA']['tx_news_domain_model_news']['columns'][$field]['l10n_mode'] = 'prefixLangTitle';
        }
    }
}
