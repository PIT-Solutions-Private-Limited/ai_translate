<?php

return [
    'dependencies' => [
        'core',
        'backend',
    ],
    'imports' => [
    //    '@pits/AiTranslate/' => [ 
    //        'path' =>'EXT:ai_translate/Resources/Public/JavaScript/',
    //        'exclude' => [
    //            'EXT:ai_translate/Resources/Public/JavaScript/Overrides/',
    //        ],
    //],
     // Overriding a file from another package
    '@typo3/backend/localization.js' => 'EXT:ai_translate/Resources/Public/JavaScript/Overrides/localization.js',
],
];