# AI Translate

This extension provides option to translate content elements and tca record fields to desired language(supported by deepl). As a fallback, Google,openai,gemini translate option is also provided as they provide support for many languages that deepl isn't providing.
For both Deepl translate and Google translate, there are two modes-normal and autodetect, where the later autodetects source language and translates it to the desired language.
For openai and gemini autodetects source language and translates it to the desired language.

## Installation

1. Install the "ai_translate" extension in the standard TYPO3 way.
2. Once the extension is installed, it will be accessible in the left menu in the TYPO3 backend.
3. Click on the "AI Translate" option in the left menu to access languages settings.

## How to use:

Watch our instruction video to get an overview of the extension and how to use it.

[![TYPO3 AI Translate](https://img.youtube.com/vi/sjmd4zHjXwY/0.jpg)](http://www.youtube.com/watch?v=Yy0p5FH1P_M "AI Translate for TYPO3"

## Requirements

- TYPO3 11 (2.1.0)
- TYPO3 12 (3.1.0)

## Extension Configuartion

Once you installed the extension, you have to set the  API Key under extension configuration section


## Translating content elements

Once the extension is installed and Api key provided we are good to go for translating content elements.On translating content element,There appears additional six options apart from normal tranlate and copy.

- Deepl Translate(auto detect).
- Deepl Translate.
- Google Translate(auto detect).
- Google Translate.
- OpenAi Translate
- Gemini Translate

## Translating TCA records

AiTranslate supports translation of specific fields of TCA records.It understands fields which need to be translated, only if their ``` l10n_mode ``` is set to ``` prefixLangTitle ```.

For example if you need translation of fields of tx_news (teaser and bodytext),You need to override those fields like follows:

Add it to TCA/Overrides: 
Example : ``` typo3conf/ext/theme/Configuration/TCA/Overrides/tx_news_domain_model_news.php ```

```
<?php

$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['bodytext']['l10n_mode'] = 'prefixLangTitle';
$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['teaser']['l10n_mode'] = 'prefixLangTitle';

```

## AI Translate Module Settings
The settings module helps to assign the sytem languages to either deepl supported languages or Google,OpenAi,Gemini languages.

For example you can assign German under Austrian German sys language if you wish. For assigning a language to a sys language you must enter it’s isocode(ISO 639-1).

## Changelog

- 3.1.0: initial release
