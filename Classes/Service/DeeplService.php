<?php
namespace PITS\AiTranslate\Service;

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
use GuzzleHttp\Exception\ClientException;
use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use GuzzleHttp\Client;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class DeeplService
{
    /**
     * @var type
     */
    protected $curlHandle;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $apiUrl;

    /**
     * Default supported languages
     * @var array
     */
    public $apiSupportedLanguages = ['EN', 'DE', 'FR', 'ES', 'IT', 'NL', 'PL' ,'RU', 'PT','BG','CS','HU','RO','SV','TR','UK','ZH'];

    /**
     * @var RequestFactory
     */
    public $requestFactory;

    /**
     * @var \PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository
     */
    protected $deeplSettingsRepository;

    /**
     * Description
     * @return type
     */
    public function __construct()
    {

        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ai_translate');
        $this->deeplSettingsRepository = GeneralUtility::makeInstance(DeeplSettingsRepository::class);
        $this->requestFactory          = GeneralUtility::makeInstance(RequestFactory::class);

        $this->apiUrl                  = $extConf['apiUrl'];
        $this->apiKey                  = $extConf['apiKey'];
        $this->apiSupportedLanguages   = $this->deeplSettingsRepository->getSupportedLanguages($this->apiSupportedLanguages);
        
    }

    /**
     * Deepl Api Call for retrieving translation.
     * @return type
     */
    public function translateRequest($content, $targetLanguage, $sourceLanguage)
    {
        $postFieldString='';
        $postFields = [
            'auth_key'     => $this->apiKey,
            'text'         => $content,
            'source_lang'  => urlencode($sourceLanguage),
            'target_lang'  => urlencode($targetLanguage),
            'tag_handling' => urlencode('xml'),
        ];
        //url-ify the data to get content length
        foreach ($postFields as $key => $value) {
            $postFieldString .= $key . '=' . $value . '&';
        }
        rtrim($postFieldString, '&');
        $contentLength = mb_strlen($postFieldString, '8bit');

        try {
            $response = $this->requestFactory->request($this->apiUrl, 'POST', [
                'form_params' => $postFields,
                'headers'     => ['Content-Type: application/x-www-form-urlencoded', 'Content-Length:' . $contentLength],
            ]);
        } catch (ClientException $e) {
            $result            = [];
            $result['status']  = false;
            if($e->getResponse()->getStatusCode()!='403') {
                $result['message'] = $e->getMessage();
            }
            else {
                $result['message'] = 'Invalid api key or url';  
            }
            return $result;
        }
        return json_decode($response->getBody()->getContents());
    }

    public function validateCredentials() {
        $response = $this->translateRequest('Test', 'de', 'en');
        if (!empty($response) && isset($response->translations)) {
            $result['status']  = true;
        }
        else{
            $result = $response;
        }
        
       return $result;
    }

}
