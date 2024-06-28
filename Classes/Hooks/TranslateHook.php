<?php
namespace PITS\AiTranslate\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2024 Developer <contact@pitsolutions.com>, PIT Solutions
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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
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

    /**
     * siteFinder
     * @var \TYPO3\CMS\Core\Page\SiteFinder
     * 
     */
    protected $siteFinder;
	
	public function injectDeeplSettingsRepository(DeeplSettingsRepository $deeplSettingsRepository)
    {
        $this->deeplSettingsRepository = $deeplSettingsRepository;
    }

    public function injectSiteFinder(SiteFinder $siteFinder)
    {
        $this->siteFinder = $siteFinder;
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
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
    }

    /**
     * processTranslateTo_copyAction hook
     * @param type &$content
     * @param type $languageRecord
     * @param type $dataHandler
     * @return string
     */
    public function processTranslateTo_copyAction(&$content, $languageRecord, $dataHandler, $field)
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
        $modeFromSession = ($_SESSION['custommode']) ?? null;
        $srcFromSession = ($_SESSION['customsrclanguage']) ?? null;
        $customMode = isset($cmdmap['localization']['custom']['mode'])? $cmdmap['localization']['custom']['mode'] : $modeFromSession;
        $srcLanguageId = isset($cmdmap['localization']['custom']['srcLanguageId'])? $cmdmap['localization']['custom']['srcLanguageId'] : $srcFromSession;

        $customMode = $customMode ?? null;
        if ($customMode === null) {
            return $content;
        }
        //translation mode set to deepl or google translate
        if (!is_null($customMode)) {
            $langParam          = explode('-', $srcLanguageId);
            $sourceLanguageCode = $langParam[0];
            $sites = $this->siteFinder->getAllSites();
            foreach($sites as $site){
                $targetLanguage = $site->getLanguageById($languageRecord['uid'])->toArray();
                $sourceLanguage = $site->getLanguageById((int) $sourceLanguageCode)->toArray();
            }
            
            //get target language mapping if any
            $targetLanguageMapping = $this->deeplSettingsRepository->getMappings($targetLanguage['languageId']);
            if ($targetLanguageMapping != null) {
                $targetLanguage['twoLetterIsoCode'] = $targetLanguageMapping;
            }
            if ($sourceLanguage == null) {
                $sourceLanguageIso = 'en';
                //choose between default and autodetect
                $deeplSourceIso = ($sourceLanguageCode == 'auto' ? null : 'EN');
            } else {
                $sourceLanguageMapping = $this->deeplSettingsRepository->getMappings($sourceLanguage['languageId']);
                if ($sourceLanguageMapping != null) {
                    $sourceLanguage['twoLetterIsoCode'] = $sourceLanguageMapping;
                }
                $sourceLanguageIso = $sourceLanguage['twoLetterIsoCode'];
                $deeplSourceIso    = $sourceLanguageIso;
            }
            /*if ($this->isHtml($content)) {
                $content = $this->stripSpecificTags(['br'], $content);
            }*/
            //mode deepl
            if ($customMode == 'deepl') {
                //if target language and source language among supported languages
                if (in_array(strtoupper($targetLanguage['twoLetterIsoCode']), $this->deeplService->apiSupportedLanguages)) {

                    if ($tablename == 'tt_content') {
                        $response = $this->deeplService->translateRequest($content, $targetLanguage['twoLetterIsoCode'], $deeplSourceIso);

                    } else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                        if (!empty($selectedTCAvalues)) {
                            $response = $this->deeplService->translateRequest($selectedTCAvalues, $targetLanguage['twoLetterIsoCode'], $sourceLanguage['twoLetterIsoCode']);
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
                    else{
                        if(is_array($response) && isset($response['status'])){
                            if(!$response['status']){
                                echo $response['message']; exit;
                            }
                        }
                    }
                }
            }
            //mode google
            elseif ($customMode == 'google') {
                if ($tablename == 'tt_content') {
                    $response = $this->googleService->translate($deeplSourceIso, $targetLanguage['twoLetterIsoCode'], $content);

                } else {
                    $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                    $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                    if (!empty($selectedTCAvalues)) {
                        $response = $this->googleService->translate($sourceLanguage['twoLetterIsoCode'], $targetLanguage['twoLetterIsoCode'], $selectedTCAvalues);
                    }
                }
                if (!empty($response)) {
                    if(is_array($response) && isset($response['status'])){
                        if(!$response['status']){
                            echo $response['message']; exit;
                        }
                    }
                    if ($this->isHtml($response)) {
                        $content = preg_replace('/\/\s/', '/', $response);
                        $content = preg_replace('/\>\s+/', '>', $content);
                    } else {
                        $content = $response;
                    }
                }
            }

            elseif ($customMode == 'openai') {
                if (in_array(strtoupper($targetLanguage['twoLetterIsoCode']), $this->deeplService->apiSupportedLanguages)) 
                {
                    if ($tablename == 'tt_content') {
                        $response = $this->openAiService->translateRequest($content, $targetLanguage['twoLetterIsoCode'], $deeplSourceIso);
                    }
                    else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                        if (!empty($selectedTCAvalues)) {
                            $response = $this->openAiService->translateRequest($selectedTCAvalues, $targetLanguage['twoLetterIsoCode'], $sourceLanguage['twoLetterIsoCode']);
                        }
                    }

                    if(is_array($response) && isset($response['status'])){
                        if(!$response['status']){
                            echo $response['message']; exit;
                        }
                    } 

                    $content = $response;
   
                }
            }

            elseif ($customMode == 'geminiai') {
                if (in_array(strtoupper($targetLanguage['twoLetterIsoCode']), $this->deeplService->apiSupportedLanguages)) 
                {
                    if ($tablename == 'tt_content') {
                        $response = $this->geminiAiService->translateRequest($content, $targetLanguage['twoLetterIsoCode'], $deeplSourceIso);
                    }
                    else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);

                        if (!empty($selectedTCAvalues)) {
                            $response = $this->geminiAiService->translateRequest($selectedTCAvalues, $targetLanguage['twoLetterIsoCode'], $sourceLanguage['twoLetterIsoCode']);
                        }
                    }
                    if(is_array($response) && isset($response['status'])){
                        if(!$response['status']){
                            echo $response['message']; exit;
                        }
                    } 

                    $content = $response;
   
                }
            }
            elseif ($customMode == 'claudeai') {
                if (in_array(strtoupper($targetLanguage['twoLetterIsoCode']), $this->deeplService->apiSupportedLanguages)) 
                {
                    if ($tablename == 'tt_content') {
                        $response = $this->claudeAiService->translateRequest($content, $targetLanguage['twoLetterIsoCode'], $deeplSourceIso);
                    }
                    else {
                        $currentRecord     = BackendUtility::getRecord($tablename, (int) $currectRecordId);
                        $selectedTCAvalues = $this->getTemplateValues($currentRecord, $tablename, $field, $content);
                        if (!empty($selectedTCAvalues)) {
                            $response = $this->claudeAiService->translateRequest($selectedTCAvalues, $targetLanguage['twoLetterIsoCode'], $sourceLanguage['twoLetterIsoCode']);
                        }
                    }
                    if(is_array($response) && isset($response['status'])){
                        if(!$response['status']){
                            echo $response['message']; exit;
                        }
                    }    
                    $content = $response;
   
                }
            }  
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
        
        //override Localization.js V12 update: overrided in JavaScriptModules.php
        //$pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageRenderer::class);
        //$pageRenderer->loadJavaScriptModule('@typo3/backend/localization.js'); 

        //inline js for adding deepl button on records list.
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->loadJavaScriptModule('@pits/ai-translate/recordListTranslate.js'); 
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
            $fieldlist = isset($TSObj->setup['plugin.'][$table . '.']) ? $TSObj->setup['plugin.'][$table . '.']['translatableTCAvalues']: null;
            if ($fieldlist != null && strpos($fieldlist, $field) !== false) {
                $value = $this->deeplSettingsRepository->getRecordField($table, $field, $recorddata);
            } else {
                return $content;
            }
        }
    }

}
