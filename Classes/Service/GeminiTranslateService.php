<?php
namespace PITS\AiTranslate\Service;

use GuzzleHttp\Exception\ClientException;
use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeminiTranslateService
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
        $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_translate'];
        $this->apiModel = isset($extConf["opengoogleapiModel"]) ? $extConf["opengoogleapiModel"] : '';
        $this->apiKey = isset($extConf["opengoogleapiKey"]) ? $extConf["opengoogleapiKey"] : '';
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/'.$this->apiModel.':generateContent?key=' . $this->apiKey;
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
				$result = $this->translateGeminiRequest($chunk, $targetLanguage, $sourceLanguage);
				if(!is_array($result)){
					$results[] = $result;
				}
				else{
					return $result;
				}
				
			}
        }
        // Merge the results and return
        return implode('', $results);
    }	 

	public function translateGeminiRequest($content, $targetLanguage, $sourceLanguage)
	{
		$finalPrompt = sprintf($this->prompt, $targetLanguage) . $content;    
		// Request payload
		$requestPayload = [
			"contents" => [
				"parts" => [
					["text" => $finalPrompt]
				]
			],
			"generationConfig" => [
				"temperature" => 0,
				"maxOutputTokens" => 8192,
				"stopSequences" => [],
			],
			"safetySettings" => [
				[
					"category" => "HARM_CATEGORY_HARASSMENT",
					"threshold" => "BLOCK_NONE",
				],
				[
					"category" => "HARM_CATEGORY_HATE_SPEECH",
					"threshold" => "BLOCK_NONE",
				],
				[
					"category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
					"threshold" => "BLOCK_NONE",
				],
				[
					"category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
					"threshold" => "BLOCK_NONE",
				],
			],
		];     

		// Convert request payload to JSON
		$jsonPayload = json_encode($requestPayload);

		try {
			// Set up cURL
			$ch = curl_init($this->apiUrl);

			// Set cURL options
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

			// Execute cURL session
			$response = curl_exec($ch);

			// Check for cURL errors
			if (curl_errno($ch)) {
				throw new Exception('Curl error: ' . curl_error($ch));
			}

			// Close cURL session
			curl_close($ch);

			// Decode and assign the response
			$responseArray = json_decode($response, true);
            $generatedText = '';
			
            if(isset($responseArray['candidates'])){ 
                $generatedText = isset($responseArray['candidates'][0]['content']) ? $responseArray['candidates'][0]['content']['parts'][0]['text']: '';
			}
			else{
				$result['status']  = false;
				$result['message'] = ($responseArray['error']['message']) ?? 'Invalid api key or url';

				return $result;
			}

			return $generatedText;
		} catch (Exception $e) {
			// Handle exceptions
            throw new \Exception($e->getMessage());
        }
    }


	public function validateCredentials() {
        $response = $this->translateRequest('Test','DE', 'EN',);
		if(!is_array($response)){
            $result['status']  = true;
            return $result;
        }
        else{
            return $response;
        }
    }     
}