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
        if ($this->shouldRenderAiButton($event) && !empty($missingTranslations = $this->findMissingTranslations($event->getResource()))) {
            $actions['ai_translate'] = $this->createControlAiTranslation($event->getResource(), $missingTranslations);
            $event->setActionItems($actions);
            $this->pageRenderer->loadJavaScriptModule('@pits/ai-translate/file-list-ai-translate-handler.js');
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

    private function shouldRenderAiButton(ProcessFileListActionsEvent $event): bool
    {
        return $event->isFile() && $this->hasActiveAiModels();
    }

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

    private function buildAiTranslateUrls(int $uid, array $missingTranslations, string $identifier): array
    {
        $urls = [];
        foreach ($this->activeAiModels as $model) {
            foreach ($missingTranslations as $language) {
                $urls[$model][$language['uid']] = (string) $this->uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
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

    private function hasActiveAiModels(): bool
    {
        $this->activeAiModels = array_keys(array_filter($this->aiServices, fn(string $model): bool => $this->extensionConfiguration->get('ai_translate', $model)));
        return !empty($this->activeAiModels);
    }

    private function hasActiveAiModel(string $model): bool
    {
        return boolval($this->extensionConfiguration->get('ai_translate', $model));
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

}
