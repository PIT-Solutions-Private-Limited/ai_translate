<?php
/**
 * Definitions for routes provided by EXT:deepl
 * Contains all AJAX-based routes for entry points
 *
 * Currently the "access" property is only used so no token creation + validation is made
 * but will be extended further.
 */
return [
    // Localize the records
    'records_localizedeepl' => [
        'path' => '/records/localizedeepl',
        'target' => PITS\AiTranslate\Override\LocalizationController::class . '::checkdeeplSettings'
    ],
    'records_localizegoogle' => [
        'path' => '/records/localizegooglel',
        'target' => PITS\AiTranslate\Override\LocalizationController::class . '::checkgoogleSettings'
    ],
    'records_localizeopenai' => [
        'path' => '/records/localizeopenai',
        'target' => PITS\AiTranslate\Override\LocalizationController::class . '::checkopenaiSettings'
    ],	
    'records_localizegemini' => [
        'path' => '/records/localizegemini',
        'target' => PITS\AiTranslate\Override\LocalizationController::class . '::checkgeminiSettings'
    ],		
    // Check Deepl enabled 
    'records_settingsenabled' => [
        'path' => '/records/settingsenabled',
        'target' => PITS\AiTranslate\Override\LocalizationController::class . '::checkSettingsEnabled'
    ]	
];
