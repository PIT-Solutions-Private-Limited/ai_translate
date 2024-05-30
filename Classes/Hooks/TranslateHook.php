<?php
namespace PITS\AiTranslate\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2024 Pits <contact@pitsolutions.com>, PIT Solutions
 *      
 *
 *  You may not remove or change the name of the author above. See:
 *  http://www.gnu.org/licenses/gpl-faq.html#IWantCredit
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use PITS\AiTranslate\Service\DeeplService;
use PITS\AiTranslate\Service\GoogleTranslateService;
use PITS\AiTranslate\Service\OpenAiService;
use PITS\AiTranslate\Service\GeminiTranslateService;
use PITS\AiTranslate\Service\ClaudeTranslateService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\TypoScript\TemplateService;

class TranslateHook
{

    /**
     * @var \PITS\AiTranslate\Service\DeeplService
     */
    protected $deeplService;

    /**
     * @var \PITS\AiTranslate\Service\GoogleTranslateService
     */
    protected $googleService;

     /**
     * @var \PITS\AiTranslate\Service\OpenAiService
     */
    protected $openAiService;

     /**
     * @var \PITS\AiTranslate\Service\GeminiTranslateService
     */
    protected $geminiAiService;  

     /**
     * @var \PITS\AiTranslate\Service\ClaudeTranslateService
     */
    protected $claudeAiService;   

    

    /**
     * @var \PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository
     */
    protected $deeplSettingsRepository;
	
	public function injectDeeplSettingsRepository(DeeplSettingsRepository $deeplSettingsRepository)
    {
        $this->deeplSettingsRepository = $deeplSettingsRepository;
    }


    /**
     * Description
     * @return type
     */
    public function __construct()
    {
        $this->deeplService            = GeneralUtility::makeInstance(DeeplService::class);
        $this->googleService           = GeneralUtility::makeInstance(GoogleTranslateService::class);
        $this->openAiService           = GeneralUtility::makeInstance(OpenAiService::class);
        $this->geminiAiService           = GeneralUtility::makeInstance(GeminiTranslateService::class);
        $this->claudeAiService           = GeneralUtility::makeInstance(ClaudeTranslateService::class);
        $this->deeplSettingsRepository = GeneralUtility::makeInstance(DeeplSettingsRepository::class);
    }

    /**
     * processTranslateTo_copyAction hook
     * @param type &$content
     * @param type $languageRecord
     * @param type $dataHandler
     * @return string
     */
    public function processTranslateTo_copyAction(&$content, $languageRecord, $dataHandler)
    {
        $cmdmap = $dataHandler->cmdmap;
        foreach ($cmdmap as $key => $array) {
            $tablename = $key;
            foreach ($array as $innerkey => $innervalue) {
                $currectRecordId = $innerkey;
                break;
            }
            break;
        }
        $customMode = ($cmdmap['localization']['custom']['mode'])? $cmdmap['localization']['custom']['mode'] : $_SESSION['custommode'];
        $srcLanguageId = ($cmdmap['localization']['custom']['srcLanguageId'])? $cmdmap['localization']['custom']['srcLanguageId'] : $_SESSION['customsrclanguage'];
       
        //translation mode set to deepl or google translate
        if (!is_null($customMode)) {
            $langParam          = explode('-',  $srcLanguageId);
            $sourceLanguageCode = $langParam[0];
            $targetLanguage     = BackendUtility::getRecord('sys_language', $this->getLanguageUidFromTitle($languageRecord['title']));
            $sourceLanguage     = BackendUtility::getRecord('sys_language', (int) $sourceLanguageCode);
            //get target language mapping if any
            $targetLanguageMapping = $this->deeplSettingsRepository->getMappings($targetLanguage['uid']);
            if ($targetLanguageMapping != null) {
                $targetLanguage['language_isocode'] = $targetLanguageMapping;
            }

            if ($sourceLanguage == null) {
                $sourceLanguageIso = 'en';
                //choose between default and autodetect
                $deeplSourceIso = ($sourceLanguageCode == 'auto' ? null : 'EN');
            } else {
                $sourceLanguageMapping = $this->deeplSettingsRepository->getMappings($sourceLanguage['uid']);
                if ($sourceLanguageMapping != null) {
                    $sourceLanguage['language_isocode'] = $sourceLanguageMapping;
                }
                $sourceLanguageIso = $sourceLanguage['language_isocode'];
                $deeplSourceIso    = $sourceLanguageIso;
            }
            if ($this->isHtml($content)) {
                $content = $this->stripSpecificTags(['br'], $content);
            }
            //mode deepl
            if ($customMode == 'deepl') {
                //if target language and source language among supported languages
                if (in_array(strtoupper($targetLanguage['language_isocode']), $this->deeplService->apiSupportedLanguages)) {

                    if ($tablename == 'tt_content') {
                        $response = $this->deeplService->translateRequest($content, $targetLanguage['language_isocode'], $deeplSourceIso);
                       
                    } else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                        if (!empty($selectedTCAvalues)) {
                            $response = $this->deeplService->translateRequest($selectedTCAvalues, $targetLanguage['language_isocode'], $sourceLanguage['language_isocode']);
                        }
                    }
                    if (!empty($response) && isset($response->translations)) {
                        foreach ($response->translations as $translation) {
                            if ($translation->text != '') {
                                $content = $translation->text;
                                break;
                            }
                        }
                    }
                }
            }
            //mode google
            elseif ($customMode == 'google') {
                if ($tablename == 'tt_content') {
                    $response = $this->googleService->translate($deeplSourceIso, $targetLanguage['language_isocode'], $content);

                } else {
                    $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                    $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                    if (!empty($selectedTCAvalues)) {
                        $response = $this->googleService->translate($sourceLanguage['language_isocode'], $targetLanguage['language_isocode'], $selectedTCAvalues);
                    }
                }
                if (!empty($response)) {
                    if ($this->isHtml($response)) {
                        $content = preg_replace('/\/\s/', '/', $response);
                        $content = preg_replace('/\>\s+/', '>', $content);
                    } else {
                        $content = $response;
                    }
                }
            }

            elseif ($customMode == 'openai') {
                if (in_array(strtoupper($targetLanguage['language_isocode']), $this->deeplService->apiSupportedLanguages)) 
                {

                    if ($tablename == 'tt_content') {
                        $response = $this->openAiService->translateRequest($content, $targetLanguage['language_isocode'], $deeplSourceIso);
                    }
                    else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                        if (!empty($selectedTCAvalues)) {
                            $response = $this->openAiService->translateRequest($selectedTCAvalues, $targetLanguage['language_isocode'], $sourceLanguage['language_isocode']);
                        }
                    }   
                    $content = $response;
   
                }
            }    
            elseif ($customMode == 'geminiai') {
                if (in_array(strtoupper($targetLanguage['language_isocode']), $this->deeplService->apiSupportedLanguages)) 
                {
                    if ($tablename == 'tt_content') {
                        $response = $this->geminiAiService->translateRequest($content, $targetLanguage['language_isocode'], $deeplSourceIso);
                    }
                    else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                        if (!empty($selectedTCAvalues)) {
                            $response = $this->geminiAiService->translateRequest($selectedTCAvalues, $targetLanguage['language_isocode'], $sourceLanguage['language_isocode']);
                        }
                    }   
                    $content = $response;
   
                }
            } 
            elseif ($customMode == 'claudeai') {
                if (in_array(strtoupper($targetLanguage['language_isocode']), $this->deeplService->apiSupportedLanguages)) 
                {
                    if ($tablename == 'tt_content') {
                        $response = $this->claudeAiService->translateRequest($content, $targetLanguage['language_isocode'], $deeplSourceIso);
                    }
                    else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                        if (!empty($selectedTCAvalues)) {
                            $response = $this->claudeAiService->translateRequest($selectedTCAvalues, $targetLanguage['language_isocode'], $sourceLanguage['language_isocode']);
                        }
                    }   
                    $content = $response;
   
                }
            } 


             
            //
        }
    }

    /**
     * Execute PreRenderHook for possible manipulation:
     * Add deepl.css,overrides localization.js
     */
    public function executePreRenderHook(&$hook)
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
        && !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            return;
        }
        
        //include deepl.css
        if (is_array($hook['cssFiles'])) {
            $hook['cssFiles']['EXT:ai_translate/Resources/Public/Css/deepl-min.css'] = [
                'rel'                      => 'stylesheet',
                'media'                    => 'all',
                'title'                    => '',
                'compress'                 => true,
                'forceOnTop'               => false,
                'allWrap'                  => '',
                'excludeFromConcatenation' => false,
                'splitChar'                => '|',
            ];
        }
       
  
        
        //inline js for adding deepl button on records list.
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $hook['jsInline']['RecordListInlineJS']['code'] = "function languageTranslate(a, b, c) {
                var parentDiv = $('.ai-button').closest('.btn-group');
                // Find all occurrences of ai-button except 
                var aiButtons = parentDiv.find('.ai-button').not('#' + c + '-translation-enable-' + b);
  
                aiButtons.each(function() {
                    $(this).prop('checked', false);
                });
            
                $('#' + c + '-translation-enable-' + b).parent().parent().siblings().each(function() {
                    if ($(this).data('state') === undefined || $(this).data('state') === null) {
                        var url = $(this).attr('href');
                        var lastIndex = url.lastIndexOf('&cmd[localization]');
                        var testing = (lastIndex > 0) ? url.substring(0, lastIndex) : url;
                        if (document.getElementById(c + '-translation-enable-' + b).checked == true) {
                            var newUrl = $(this).attr('href', testing + '&cmd[localization][custom][mode]=' + c);
                        }
                    }
            
                });
            
            }";
        }
    }

    /**
     * check whether the string contains html
     * @param type $string
     * @return boolean
     */
    public function isHtml($string)
    {
        return preg_match("/<[^<]+>/", $string, $m) != 0;
    }

    /**
     * stripoff the tags provided
     * @param type $tags
     * @return string
     */
    public function stripSpecificTags($tags, $content)
    {
        foreach ($tags as $tag) {
            $content = preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/", '', $content);
        }
        return $content;
    }

    /**
     * Returns default content of records according to the typoscript setting from typoscript
     * @param array $recorddata
     * @param string $table
     * @param string $field
     * @return void
     */
    public function getTemplateValues($recorddata, $table, $field, $content)
    {

        $rootLineUtility = GeneralUtility::makeInstance('TYPO3\CMS\Core\Utility\RootlineUtility',$recorddata['pid']);
        $rootLine = $rootLineUtility->get();
        $TSObj = GeneralUtility::makeInstance(TemplateService::class);

        $TSObj->tt_track = 0;
        $TSObj->runThroughTemplates($rootLine);
        $TSObj->generateConfig();
        if ($table != '') {
            $fieldlist = $TSObj->setup['plugin.'][$table . '.']['translatableTCAvalues'];
            if ($fieldlist != null && strpos($fieldlist, $field) !== false) {
                $value = $this->deeplSettingsRepository->getRecordField($table, $field, $recorddata);
            } else {
                return $content;
            }
        }
    }

     /**
     * Get language ISO code from title
     *
     * @param string $title
     * @return string|null
     */
    public function getLanguageUidFromTitle($title)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $query = $queryBuilder
            ->select('uid')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title))
            )
            ->setMaxResults(1);

        $result = $query->execute()->fetch();
        return $result ? $result['uid'] : null;
    }   

}
