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
        private readonly UriBuilder $uriBuilder
    ) {
        $this->aiServices = [
            'deepl' => 'enableDeepl',
            'google' => 'enableGoogleTranslator',
            'openai' => 'enableOpenAi',
            'geminiai' => 'enableGemini',
            'claudeai' => 'enableClaude',
        ];
        $this->activeAiModels = [];
    }

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        $actions = $event->getActionItems();
        if ($this->shouldRenderAiButton($event)) {
            $actions['ai_translate'] = $this->createControlAiTranslation($event->getResource());
            $event->setActionItems($actions);
            GeneralUtility::makeInstance(PageRenderer::class)
                ->loadJavaScriptModule('@pits/ai-translate/file-list-ai-translate-handler.js');
        }
    }

    private function createControlAiTranslation(ResourceInterface $resource): ?ButtonInterface
    {
        $metadata = $resource->getMetadata()->get();
        $dropdownButton = GeneralUtility::makeInstance(GenericButton::class);
        $dropdownButton->setIcon($this->iconFactory->getIcon('actions-translate', Icon::SIZE_SMALL));
        $dropdownButton->setLabel($GLOBALS['LANG']->sL('LLL:EXT:ai_translate/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'));
        $dropdownButton->setAttributes([
            'type' => 'button',
            'data-action' => 'ai_translate',
            'data-uid' => $metadata['uid'],
            'data-missing-translations' => implode(',', $this->missingTranslations),
            'data-models' => implode(',', $this->activeAiModels),
        ]);
        return $dropdownButton;
    }

    private function shouldRenderAiButton(ProcessFileListActionsEvent $event): bool
    {
        return $event->isFile() && $this->hasActiveAiModels() && !$this->recordHasAllTranslationsGenerated($event->getResource());
    }

    private function recordHasAllTranslationsGenerated(ResourceInterface $resource): bool
    {
        $languageField = $GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'];
        $backendUser = $this->getBackendUser();
        $languages = [];
        foreach ($this->translateTools->getSystemLanguages() as $language) {
            if ($language['uid'] > 0 && $backendUser->checkLanguageAccess($language['uid'])) {
                array_push($languages, $language['uid']);
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
                    $languages
                )
            )
            ->orderBy($languageField, QueryInterface::ORDER_ASCENDING)
            ->executeQuery()
            ->fetchFirstColumn();
        $this->missingTranslations = array_diff($languages, $availableLanguages);
        return empty($this->missingTranslations);
    }

    private function hasActiveAiModels(): bool
    {
        $this->activeAiModels = array_filter($this->aiServices, fn(string $model): bool => $this->extensionConfiguration->get('ai_translate', $model));
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
