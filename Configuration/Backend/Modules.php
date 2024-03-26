<?php
use PITS\AiTranslate\Controller\SettingsController;

return [
    'deepltranslate'=>[
        'labels' => 'LLL:EXT:ai_translate/Resources/Private/Language/locallang.xlf',
        'iconIdentifier' => 'deepl-main-menu-icon',
        'extensionName' => 'AiTranslate',
    ],

    'deepltranslate_deepltranslateSettings'=>[
            'parent' => 'deepltranslate',
            'access' => 'user,group',
            'iconIdentifier' => 'deepl-settings-menu-icon',
            'labels' => 'LLL:EXT:ai_translate/Resources/Private/Language/locallang_module_settings.xlf',
            'extensionName' => 'AiTranslate',
            'controllerActions' => [
                SettingsController::class => [
                    'index',
                    'saveSettings'
                ],

        ],
    ],
];
