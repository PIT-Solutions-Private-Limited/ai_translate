<?php
namespace PITS\AiTranslate\Service;

use GuzzleHttp\Exception\ClientException;
use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MistralTranslateService
{
    protected $apiKey;
    protected $apiModel;
    protected $apiUrl;
    protected $requestFactory;
    protected  $prompt = 'Translate the following text from %s language to %s language (keeping HTML unchanged). Do not use asterisks or any Markdown emphasis in your output: ';

    public function __construct()
    {
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        // Load configuration from TYPO3
        $extConf = $GLOBALS["TYPO3_CONF_VARS"]["EXTENSIONS"]["ai_translate"];
        $this->apiModel = ($extConf["openmistralapiModel"]) ??  null;
        $this->apiKey = ($extConf["openmistralapiKey"]) ??  null;
		$this->apiUrl = 'https://api.mistral.ai/v1/chat/completions';
    }

    /**
     * Mistral API Call for retrieving translation.
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
				$result = $this->translateMistralRequest($chunk, $targetLanguage, $sourceLanguage);
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

	public function translateMistralRequest($content, $targetLanguage, $sourceLanguage)
	{
		$finalPrompt = sprintf($this->prompt, $sourceLanguage, $targetLanguage) . $content;
		$requestPayload = [
			'model' => $this->apiModel,
			'max_tokens' => 4000,
			'temperature' => 0.3,
			'messages' => [
				[
					'role' => 'system',
					'content' => 'You are a translation engine. Always translate the text you receive, even if it is a short title, headline or single phrase. Never return the source text unchanged. Only return the translated text without any explanations, prefixes, or additional content. Never wrap words in asterisks (*) or underscores (_) and never add Markdown formatting of any kind, unless that exact formatting character was already present in the source text.'
				],
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
					'Authorization: Bearer ' . $this->apiKey,
					'Content-Type: application/json'
				],
			]);

			$response = curl_exec($curl);

			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			curl_close($curl);

			// Decode and assign the response
			$responseArray = json_decode($response, true);
			$generatedText = '';
            if(isset($responseArray['choices'][0]['message']['content'])){
                $generatedText = $responseArray['choices'][0]['message']['content'];
            }
			else{
				$result['status']  = false;
				if($httpCode === 429){
					$result['message'] = 'Mistral API rate limit or quota exceeded. Please upgrade your Mistral plan or wait before retrying.';
				}
				else{
					$result['message'] = ($responseArray['message']) ?? 'Invalid api key or url';
					if(is_array($result['message'])){
						$result['message'] = json_encode($result['message']);
					}
				}

				return $result;
			}

			return $generatedText;
		} catch (Exception $e) {
			// Handle exceptions
            throw new \Exception($e->getMessage());
		}
	}

    public function validateCredentials() {
        $response = $this->translateRequest('Test','de', 'en',);
		if(!is_array($response)){
            $result['status']  = true;
            return $result;
        }
        else{
            return $response;
        }

    }

}
