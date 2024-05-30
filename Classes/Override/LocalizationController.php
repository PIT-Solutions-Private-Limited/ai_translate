<?php
declare (strict_types = 1);
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

 use Psr\EventDispatcher\EventDispatcherInterface;
 use Psr\Http\Message\ResponseInterface;
 use Psr\Http\Message\ServerRequestInterface;
 use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
 use TYPO3\CMS\Backend\Controller\Event\AfterPageColumnsSelectedForLocalizationEvent;
 use TYPO3\CMS\Backend\Controller\Event\AfterRecordSummaryForLocalizationEvent;
 use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
 use TYPO3\CMS\Backend\Utility\BackendUtility;
 use TYPO3\CMS\Backend\View\BackendLayoutView;
 use TYPO3\CMS\Core\DataHandling\DataHandler;
 use TYPO3\CMS\Core\Http\JsonResponse;
 use TYPO3\CMS\Core\Http\Response;
 use TYPO3\CMS\Core\Imaging\Icon;
 use TYPO3\CMS\Core\Imaging\IconFactory;
 use TYPO3\CMS\Core\Utility\GeneralUtility;
 use TYPO3\CMS\Core\Versioning\VersionState;
 use TYPO3\CMS\Core\Page\PageRenderer;
 use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
 use PITS\AiTranslate\Service\DeeplService;
 use PITS\AiTranslate\Service\GoogleTranslateService;
 use PITS\AiTranslate\Service\OpenAiService;
 use PITS\AiTranslate\Service\GeminiTranslateService;
 use PITS\AiTranslate\Service\ClaudeTranslateService;

/**
 * LocalizationController handles the AJAX requests for record localization
 *
 * @internal
 */
class LocalizationController extends \TYPO3\CMS\Backend\Controller\Page\LocalizationController
{

    /**
     * @var string
     */
    const ACTION_LOCALIZEDEEPL = 'localizedeepl';

    /**
     * @var string
     */

    const ACTION_LOCALIZEDEEPL_AUTO = 'localizedeeplauto';

    /**
     * @var string
     */

    const ACTION_LOCALIZEGOOGLE = 'localizegoogle';

    /**
     * @var string
     */
    const ACTION_LOCALIZEGOOGLE_AUTO = 'localizegoogleauto';

    
    /**
     * @var string
     */
    const ACTION_LOCALIZEOPENAI = 'localizeopenai';

    /**
     * @var string
     */
    const ACTION_LOCALIZEGEMINIAI = 'localizegeminiai';

    /**
     * @var string
     */
    const ACTION_LOCALIZECLAUDEAI = 'localizeclaudeai';

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */

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
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected $pageRenderer;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->deeplService = GeneralUtility::makeInstance(DeeplService::class);
        $this->googleService = GeneralUtility::makeInstance(GoogleTranslateService::class);
        $this->openAiService = GeneralUtility::makeInstance(OpenAiService::class);
        $this->geminiAiService = GeneralUtility::makeInstance(GeminiTranslateService::class);
        $this->claudeAiService = GeneralUtility::makeInstance(ClaudeTranslateService::class);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:ai_translate/Resources/Private/Language/locallang.xlf');
    }


    /**
     * Get used languages in a page
     */
    public function getUsedLanguagesInPage(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['languageId'])) {
            return new JsonResponse(null, 400);
        }

        $pageId = (int)$params['pageId'];
        $languageId = (int)$params['languageId'];
        $mode       = $params['mode'] ?? null;
        $translationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $systemLanguages = $translationProvider->getSystemLanguages($pageId);

        $availableLanguages = [];

        // First check whether column has localized records
        $elementsInColumnCount = $this->localizationRepository->getLocalizedRecordCount($pageId, $languageId);
        $result = [];
        if ($elementsInColumnCount !== 0) {
            // check elements in column - empty if source records do not exist anymore
            $result = $this->localizationRepository->fetchOriginLanguage($pageId, $languageId);
            if ($result !== []) {
                $availableLanguages[] = $systemLanguages[$result['sys_language_uid']];
            }
        }
        if ($elementsInColumnCount === 0 || $result === []) {
            $fetchedAvailableLanguages = $this->localizationRepository->fetchAvailableLanguages($pageId, $languageId);
            foreach ($fetchedAvailableLanguages as $language) {
                if (isset($systemLanguages[$language['sys_language_uid']])) {
                    $availableLanguages[] = $systemLanguages[$language['sys_language_uid']];
                }
            }
        }
        // Language "All" should not appear as a source of translations (see bug 92757) and keys should be sequential
        $availableLanguages = array_values(
            array_filter($availableLanguages, static function (array $languageRecord): bool {
                return (int)$languageRecord['uid'] !== -1;
            })
        );

         //for deepl and google auto modes
         if (!empty($systemLanguages)) {
            if ($mode == 'localizedeeplauto' || $mode == 'localizegoogleauto') {
                $availableLanguages = null;
                //V12 upgrade: inseatd of adding 'auto', default language is used.
                $availableLanguages[] = $systemLanguages[0];
            }
        }

        // Pre-render all flag icons
        foreach ($availableLanguages as &$language) {
            if ($language['flagIcon'] === 'empty-empty') {
                $language['flagIcon'] = '';
            } else {
                $language['flagIcon'] = $this->iconFactory->getIcon($language['flagIcon'], Icon::SIZE_SMALL)->render();
            }
        }
        return new JsonResponse($availableLanguages);
    }

    
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function localizeRecords(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['srcLanguageId'], $params['destLanguageId'], $params['action'], $params['uidList'])) {
            return new JsonResponse(null, 400);
        }

        if ($params['action'] !== static::ACTION_COPY && $params['action'] !== static::ACTION_LOCALIZE && $params['action'] !== static::ACTION_LOCALIZEDEEPL 
        && $params['action'] !== static::ACTION_LOCALIZEDEEPL_AUTO && $params['action'] !== static::ACTION_LOCALIZEGOOGLE 
        && $params['action'] !== static::ACTION_LOCALIZEGOOGLE_AUTO && $params['action'] !== static::ACTION_LOCALIZEOPENAI
        && $params['action'] !== static::ACTION_LOCALIZEGEMINIAI  && $params['action'] !== static::ACTION_LOCALIZECLAUDEAI) {
            $response = new Response('php://temp', 400, ['Content-Type' => 'application/json; charset=utf-8']);
            $response->getBody()->write('Invalid action "' . $params['action'] . '" called.');
            return $response;
        }

        // Filter transmitted but invalid uids
        $params['uidList'] = $this->filterInvalidUids(
            (int)$params['pageId'],
            (int)$params['destLanguageId'],
            (int)$params['srcLanguageId'],
            $params['uidList']
        );

        $this->process($params);

        return new JsonResponse([]);
    }

    /**
     * Processes the localization actions
     *
     * @param array $params
     */
    protected function process($params): void
    {
        $destLanguageId = (int)$params['destLanguageId'];

        // Build command map
        $cmd = [
            'tt_content' => [],
        ];

        if (isset($params['uidList']) && is_array($params['uidList'])) {
            foreach ($params['uidList'] as $currentUid) {
                if ($params['action'] === static::ACTION_LOCALIZE || $params['action'] === static::ACTION_LOCALIZEDEEPL 
                || $params['action'] === static::ACTION_LOCALIZEDEEPL_AUTO || $params['action'] === static::ACTION_LOCALIZEGOOGLE 
                || $params['action'] === static::ACTION_LOCALIZEGOOGLE_AUTO || $params['action'] === static::ACTION_LOCALIZEOPENAI
                || $params['action'] === static::ACTION_LOCALIZEGEMINIAI || $params['action'] === static::ACTION_LOCALIZECLAUDEAI) {
                    $cmd['tt_content'][$currentUid] = [
                        'localize' => $destLanguageId,
                    ];
                    //setting mode and source language for deepl translate.
                    if ($params['action'] === static::ACTION_LOCALIZEDEEPL || $params['action'] === static::ACTION_LOCALIZEDEEPL_AUTO) {
                        $cmd['localization']['custom']['mode']          = 'deepl';
                        $cmd['localization']['custom']['srcLanguageId'] = $params['srcLanguageId'];
                    } else if ($params['action'] === static::ACTION_LOCALIZEGOOGLE || $params['action'] === static::ACTION_LOCALIZEGOOGLE_AUTO) {
                        $cmd['localization']['custom']['mode']          = 'google';
                        $cmd['localization']['custom']['srcLanguageId'] = $params['srcLanguageId'];
                    }
                    else if ($params['action'] === static::ACTION_LOCALIZEOPENAI) {
                        $cmd['localization']['custom']['mode']          = 'openai';
                        $cmd['localization']['custom']['srcLanguageId'] = $params['srcLanguageId'];
                    }
                    else if ($params['action'] === static::ACTION_LOCALIZEGEMINIAI) {
                        $cmd['localization']['custom']['mode']          = 'geminiai';
                        $cmd['localization']['custom']['srcLanguageId'] = $params['srcLanguageId'];
                    }
                    else if ($params['action'] === static::ACTION_LOCALIZECLAUDEAI) {
                        $cmd['localization']['custom']['mode']          = 'claudeai';
                        $cmd['localization']['custom']['srcLanguageId'] = $params['srcLanguageId'];
                    }           
                } else {
                    $cmd['tt_content'][$currentUid] = [
                        'copyToLanguage' => $destLanguageId,
                    ];
                }
            }
        }
         // Start session (if not already started)
         if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session variable
        $_SESSION['custommode'] = $cmd['localization']['custom']['mode'];
        $_SESSION['customsrclanguage'] = $cmd['localization']['custom']['srcLanguageId'];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
    }

     /**
     * check deepl Settings (url,apikey).
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    public function checkdeeplSettings(ServerRequestInterface $request)
    {
        $result             = [];
        $extConf            = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_translate'];
        if ($this->deeplService->apiKey != null && $this->deeplService->apiUrl != null) {
            $result = $this->deeplService->validateCredentials();
            
        } else {
            $result['status']  = false;
            $result['message'] = '';
        }
        return new JsonResponse($result);
    }

     /**
     * check google Settings (url,apikey).
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    public function checkgoogleSettings(ServerRequestInterface $request)
    {
        $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_translate'];
        if ($extConf['googleapiKey'] != null && $extConf['googleapiUrl'] != null) {
            $result = $this->googleService->validateCredentials();
        } else {
            $result['status']  = false;
            $result['message'] = '';
        }

        return new JsonResponse($result);
    }	
	
    /**
     * check openai Settings (model,apikey).
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    public function checkopenaiSettings(ServerRequestInterface $request)
    {
        $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_translate'];
        if ($extConf['openaiapiModel'] != null && $extConf['openaiapiKey'] != null) {
            $result = $this->openAiService->validateCredentials();
        } else {
            $result['status']  = false;
            $result['message'] = '';
        }

        return new JsonResponse($result);
    }	
	
    /**
     * check gemini Settings (model,apikey).
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    public function checkgeminiSettings(ServerRequestInterface $request)
    {
        $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_translate'];
        if ($extConf['opengoogleapiKey'] != null && $extConf['opengoogleapiModel'] != null) {
            $result = $this->geminiAiService->validateCredentials();
        } else {
            $result['status']  = false;
            $result['message'] = '';
        }

        return new JsonResponse($result);
    }
    
    /**
     * check claude Settings (model,apikey).
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    public function checkclaudeSettings(ServerRequestInterface $request)
    {
        $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_translate'];
        if ($extConf['openclaudeapiKey'] != null && $extConf['openclaudeapiModel'] != null) {
            $result = $this->claudeAiService->validateCredentials();
        } else {
            $result['status']  = false;
            $result['message'] = '';
        }
        
        return new JsonResponse($result);
    }

    /**
     * Return source language Id from source language string
     * @param string $srcLanguage
     * @return int
     */
    public function getSourceLanguageid($srcLanguage)
    {
        $langParam = explode('-', $srcLanguage);
        if (count($langParam) > 1) {
            return (int) $langParam[1];
        } else {
            return (int) $langParam[0];
        }
    }

   /**
     * check translation options are enabled or diabled.
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
   public function checkSettingsEnabled(ServerRequestInterface $request = null)
    {
        $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_translate'];
		$result['enableDeepl'] = $extConf['enableDeepl'];
		$result['enableGoogleTranslator'] = $extConf['enableGoogleTranslator'];
		$result['enableOpenAi'] = $extConf['enableOpenAi'];
		$result['enableGemini'] = $extConf['enableGemini'];
        $result['enableClaude'] = $extConf['enableClaude'];
        
        return new JsonResponse($result);
        
    }	

    /**
     * Get a prepared summary of records being translated
     */
    public function getRecordLocalizeSummary(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['destLanguageId'], $params['languageId'])) {
            return new JsonResponse(null, 400);
        }

        $pageId = (int)$params['pageId'];
        $destLanguageId = (int)$params['destLanguageId'];
        $languageId = (int)$params['languageId'];

        $records = [];
        $result = $this->localizationRepository->getRecordsToCopyDatabaseResult(
            $pageId,
            $destLanguageId,
            $languageId,
            '*'
        );

        $flatRecords = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
            if (!$row || VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                continue;
            }
            $colPos = $row['colPos'];
            if (!isset($records[$colPos])) {
                $records[$colPos] = [];
            }
            $records[$colPos][] = [
                'icon' => $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render(),
                'title' => $row[$GLOBALS['TCA']['tt_content']['ctrl']['label']],
                'uid' => $row['uid'],
            ];
            $flatRecords[] = $row;
        }

        $columns = $this->getPageColumns($pageId, $flatRecords, $params);
        $event = new AfterRecordSummaryForLocalizationEvent($records, $columns);
        $this->eventDispatcher->dispatch($event);

        return new JsonResponse([
            'records' => $event->getRecords(),
            'columns' => $event->getColumns(),
        ]);
    }

}
