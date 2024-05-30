<?php
namespace PITS\AiTranslate\Override;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use PITS\AiTranslate\Override\LocalizationController;

/**
 * Class for rendering of Web>List module
 */
class DatabaseRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
{



    /**
     * Creates the localization panel
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the localization panel.
     * @return string[] Array with key 0/1 with content for column 1 and 2
     */
    public function makeLocalizationPanel($table, $row, array $translations): string
    {
        $out = '';
        // All records excluding pages
        $possibleTranslations = $this->possibleTranslations;
        if ($table === 'pages') {
            // Calculate possible translations for pages
            $possibleTranslations = array_map(static fn ($siteLanguage) => $siteLanguage->getLanguageId(), $this->languagesAllowedForUser);
            $possibleTranslations = array_filter($possibleTranslations, static fn ($languageUid) => $languageUid > 0);
        }

        // Traverse page translations and add icon for each language that does NOT yet exist and is included in site configuration:
        $pageId = (int)($table === 'pages' ? $row['uid'] : $row['pid']);
        $languageInformation = $this->translateTools->getSystemLanguages($pageId);

        foreach ($possibleTranslations as $lUid_OnPage) {
            if ($this->isEditable($table)
                && !$this->isRecordDeletePlaceholder($row)
                && !isset($translations[$lUid_OnPage])
                && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)
            ) {
                $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                            'justLocalized' => $table . ':' . $row['uid'] . ':' . $lUid_OnPage,
                            'returnUrl' => $this->listURL(),
                        ]
                );
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd'][$table][$row['uid']]['localize'] = $lUid_OnPage;
                $href = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                $title = htmlspecialchars($languageInformation[$lUid_OnPage]['title'] ?? '');

                $lC = ($languageInformation[$lUid_OnPage]['flagIcon'] ?? false)
                    ? $this->iconFactory->getIcon($languageInformation[$lUid_OnPage]['flagIcon'], Icon::SIZE_SMALL)->render()
                    : $title;

                $out .= '<a href="' . htmlspecialchars($href) . '"'
                    . '" class="btn btn-default t3js-action-localize"'
                    . ' title="' . $title . '">'
                    . $lC . '</a> ';
            }
        }
        $localizationAi = '';
        if($table!='sys_category') {
            
        // Instantiate LocalizationController
        $localizationController = GeneralUtility::makeInstance(LocalizationController::class);
        $enabled = json_decode($localizationController->checkSettingsEnabled(null,'record'));
        if($enabled->enableDeepl == '1') {
        $localizationAi .= '<a data-state="hidden" href="#" data-params="data[$table][$uid][hidden]=0" class="ai-a" ><label class="btn btn-default btn-checkbox deepl-btn-wrap"><input class="deepl-button ai-button" id="deepl-translation-enable-'.$row["uid"].'" type="checkbox" name="data[deepl.enable]" onclick="languageTranslate(\''.$table.'\','.$row["uid"].', \'deepl\')" /><span class="ai-span"></span></label></a>';
        }
        if($enabled->enableGoogleTranslator == '1') {
        $localizationAi .= '<a data-state="hidden" href="#" data-params="data[$table][$uid][hidden]=0" class="ai-a"><label class="btn btn-default btn-checkbox deepl-btn-wrap"><input class="google-button ai-button" id="google-translation-enable-'.$row["uid"].'" type="checkbox" name="data[google.enable]" onclick="languageTranslate(\''.$table.'\','.$row["uid"].', \'google\')" /><span class="ai-span"></span></label></a>';
        }
        if($enabled->enableOpenAi == '1') {
        $localizationAi .= '<a data-state="hidden" href="#" data-params="data[$table][$uid][hidden]=0" class="ai-a"><label class="btn btn-default btn-checkbox deepl-btn-wrap"><input class="openai-button ai-button" id="openai-translation-enable-'.$row["uid"].'" type="checkbox" name="data[openai.enable]" onclick="languageTranslate(\''.$table.'\','.$row["uid"].', \'openai\')" /><span class="ai-span"></span></label></a>';
        }
        if($enabled->enableGemini == '1') {
        $localizationAi .= '<a data-state="hidden" href="#" data-params="data[$table][$uid][hidden]=0" class="ai-a"><label class="btn btn-default btn-checkbox deepl-btn-wrap"><input class="geminiai-button ai-button" id="geminiai-translation-enable-'.$row["uid"].'" type="checkbox" name="data[geminiai.enable]" onclick="languageTranslate(\''.$table.'\','.$row["uid"].', \'geminiai\')" /><span class="ai-span"></span></label></a>';
        }
        if($enabled->enableClaude == '1') {
            $localizationAi .= '<a data-state="hidden" href="#" data-params="data[$table][$uid][hidden]=0" class="ai-a"><label class="btn btn-default btn-checkbox deepl-btn-wrap"><input class="claudeai-button ai-button" id="claudeai-translation-enable-'.$row["uid"].'" type="checkbox" name="data[claudeai.enable]" onclick="languageTranslate(\''.$table.'\','.$row["uid"].', \'claudeai\')" /><span class="ai-span"></span></label></a>';
        }

        }

        $out .= $localizationAi;	
		
        return $out;
    }


}
