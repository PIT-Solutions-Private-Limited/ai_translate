<?php
namespace PITS\AiTranslate\Service;

use GuzzleHttp\Exception\ClientException;
use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClaudeTranslateService
{
    protected $apiKey;
    protected $apiModel;
    protected $apiUrl;
    protected $requestFactory;
    protected  $prompt = 'Translate the following text to %s language (keeping HTML unchanged): ';

    public function __construct()
    {
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        // Load configuration from TYPO3
        $extConf = $GLOBALS["TYPO3_CONF_VARS"]["EXTENSIONS"]["ai_translate"];
        $this->apiModel = $extConf["openclaudeapiModel"];
        $this->apiKey = $extConf["openclaudeapiKey"];
		$this->apiUrl = 'https://api.anthropic.com/v1/messages';
    }   
    
    /**
     * Google Api Call for retrieving translation.
     * @return type
     */
    public function translateRequest($content, $targetLanguage, $sourceLanguage)
    {
        // Split content into chunks of 5000 characters
        
        $results = [];
    	if($content!='') {
			$chunks = str_split($content, 5000);
			// Translate each chunk separately
			foreach ($chunks as $chunk) {
				$result = $this->translateClaudeRequest($chunk, $targetLanguage, $sourceLanguage);
				$results[] = $result;
			}
        }
        // Merge the results and return
        return implode('', $results);
    }	 

	public function translateClaudeRequest($content, $targetLanguage, $sourceLanguage)
	{
		$finalPrompt = sprintf($this->prompt, $targetLanguage) . $content;   
		
		$requestPayload = [
			'model' => $this->apiModel,
			'max_tokens' => 4000,
			'temperature' => 0.6,
			'messages' => [
				[
					'role' => 'user',
					'content' => $finalPrompt
				]
			]
		];

		// Convert request payload to JSON
		$jsonPayload = json_encode($requestPayload);

		try {

			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_URL => $this->apiUrl,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => $jsonPayload,
				CURLOPT_HTTPHEADER => [
					'x-api-key: ' . $this->apiKey,
					'anthropic-version: 2023-06-01',
					'content-type: application/json'
				],
			]);
			
			$response = curl_exec($curl);
			
			curl_close($curl);

			// Decode and assign the response
			$responseArray = json_decode($response, true);  
			$generatedText = '';
            if(isset($responseArray['content'][0]['text'])){
                $generatedText = isset($responseArray['content'][0]['text']) ? $responseArray['content'][0]['text']: '';
            }
			else if(isset($responseArray['error']['code']) && $responseArray['error']['code']==400) {
				$generatedText = $responseArray['error']['code'];
			}

			return $generatedText;
		} catch (Exception $e) {
			// Handle exceptions
            throw new \Exception($e->getMessage());
		}
	} 

    public function validateCredentials() {
        $response = $this->translateRequest('Test','de', 'en',);
		if($response==400) {
			$result            = [];
            $result['status']  = 'false';
			$result['message'] = 'Please give proper api key and url';  
            $result = json_encode($result);
            echo $result;
            exit;			
		}

    }     
    
}