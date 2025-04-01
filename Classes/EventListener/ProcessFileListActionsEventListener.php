<?php

namespace PITS\AiTranslate\EventListener;

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

class ProcessFileListActionsEventListener
{
    private array $aiServices;

    private array $activeAiModels;

    private array $missingTranslations;

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly TranslationConfigurationProvider $translateTools,
        private readonly ConnectionPool $connection,
        private readonly UriBuilder $uriBuilder,
        private readonly PageRenderer $pageRenderer,
    ) {
        // Mapping of the internal identifiers with the enable options in the extension configuration
        $this->aiServices = [
            'deepl' => 'enableDeepl',
            'google' => 'enableGoogleTranslator',
            'openai' => 'enableOpenAi',
            'geminiai' => 'enableGemini',
            'claudeai' => 'enableClaude',
        ];
        $this->activeAiModels = [];
        $this->missingTranslations = [];
    }

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        $actions = $event->getActionItems();
        /**
         * Validate whether the AI translation button should be rendered
         * The coinditions are:
         * - The file is a file (not a folder)
         * - At least one AI model/service is enabled
         * - The file is not already translated in all available languages for the backend user
         */
        if ($this->shouldRenderAiButton($event) && !empty($missingTranslations = $this->findMissingTranslations($event->getResource()))) {
            $actions['ai_translate'] = $this->createControlAiTranslation($event->getResource(), $missingTranslations);
            $event->setActionItems($actions);
            // Add the JavaScript module to the File List so the button can be used
            $this->pageRenderer->loadJavaScriptModule('@pits/ai-translate/file-list-ai-translate-handler.js');
            // Add translation labels for the JavaScript module
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:ai_translate/Resources/Private/Language/locallang.xlf');
        }
    }

    private function createControlAiTranslation(ResourceInterface $resource, array $missingTranslations): ?ButtonInterface
    {
        $metadata = $resource->getMetadata()->get();
        $dropdownButton = GeneralUtility::makeInstance(GenericButton::class);
        $dropdownButton->setIcon($this->iconFactory->getIcon('actions-translate', Icon::SIZE_SMALL));
        $dropdownButton->setLabel($GLOBALS['LANG']->sL('LLL:EXT:ai_translate/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'));
        $dropdownButton->setAttributes([
            'type' => 'button',
            'data-action' => 'ai_translate',
            'data-ai-models' => implode(',', $this->activeAiModels),
            'data-translate-urls' => json_encode($this->buildAiTranslateUrls($metadata['uid'], $missingTranslations, $resource->getParentFolder()->getCombinedIdentifier())),
            'data-languages' => json_encode($missingTranslations),
        ]);
        return $dropdownButton;
    }

    /**
     * Checks whether the AI translation button should be rendered
     * The conditions are:
     * - The file is a file (not a folder)
     * - At least one AI model/service is enabled
     * @param \TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent $event
     * @return bool
     */
    private function shouldRenderAiButton(ProcessFileListActionsEvent $event): bool
    {
        return $event->isFile() && $this->hasActiveAiModels();
    }

    /**
     * Finds the missing translations languages for the given resource
     * The languages are filtered to the translation ones and to the ones the backend user has access to
     * @param \TYPO3\CMS\Core\Resource\ResourceInterface $resource
     * @return array
     */
    private function findMissingTranslations(ResourceInterface $resource): array
    {
        $missingTranslations = [];
        $languageField = $GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'];
        $backendUser = $this->getBackendUser();
        $languages = [];
        foreach ($this->translateTools->getSystemLanguages() as $language) {
            if ($language['uid'] > 0 && $backendUser->checkLanguageAccess($language['uid'])) {
                array_push($languages, $language);
            }
        }
        $queryBuilder = $this->connection->getQueryBuilderForTable('sys_file_metadata');
        $availableLanguages = $queryBuilder
            ->select($languageField)
            ->distinct()
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq(
                    'file',
                    $queryBuilder->createNamedParameter($resource->getUid(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    $languageField,
                    array_map(
                        fn(array $language): int => $language['uid'],
                        $languages
                    )
                )
            )
            ->orderBy($languageField, QueryInterface::ORDER_ASCENDING)
            ->executeQuery()
            ->fetchFirstColumn();
        foreach ($languages as $language) {
            if (!in_array($language['uid'], $availableLanguages)) {
                $missingTranslations[] = $language;
            }
        }
        return $missingTranslations;
    }

    /**
     * Builds the URLs for the AI translation services and languages
     * @param int $uid
     * @param array $missingTranslations
     * @param string $identifier
     * @return array
     */
    private function buildAiTranslateUrls(int $uid, array $missingTranslations, string $identifier): array
    {
        $urls = [];
        foreach ($this->activeAiModels as $model) {
            foreach ($missingTranslations as $language) {
                $urls[$model][$language['uid']] = (string) $this->uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        // cmd to the DataHandler/TCEMain to create the translation using one of the AI models/services
                        'cmd' => [
                            'sys_file_metadata' => [
                                $uid => [
                                    'localize' => $language['uid'],
                                ]
                            ],
                            'localization' => [
                                'custom' => [
                                    'mode' => $model,
                                    'srcLanguageId' => 0
                                ]
                            ]
                        ],
                        // Redirect to the record edit form after the translation is created
                        'redirect' => (string) $this->uriBuilder->buildUriFromRoute(
                            'record_edit',
                            [
                                'justLocalized' => 'sys_file_metadata:' . $uid . ':' . $language['uid'],
                                'returnUrl' => (string) $this->uriBuilder->buildUriFromRoute(
                                    'media_management',
                                    [
                                        'id' => $identifier,
                                    ]
                                ),
                            ]
                        ),
                    ]
                );

            }
        }
        return $urls;
    }

    /**
     * Checks whether at least one AI model/service is enabled
     * @return bool
     */
    private function hasActiveAiModels(): bool
    {
        $this->activeAiModels = array_keys(array_filter($this->aiServices, fn(string $model): bool => $this->extensionConfiguration->get('ai_translate', $model)));
        return !empty($this->activeAiModels);
    }

    /**
     * Checks whether the given AI model/service is enabled
     * @param string $model
     * @return bool
     */
    private function hasActiveAiModel(string $model): bool
    {
        return boolval($this->extensionConfiguration->get('ai_translate', $model));
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

}
