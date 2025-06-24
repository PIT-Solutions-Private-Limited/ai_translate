<?php
namespace PITS\AiTranslate\Service;

use GuzzleHttp\Exception\ClientException;
use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CohereTranslateService
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
        $this->apiModel = 'command';
        $this->apiKey = $extConf["opencohereapiKey"];
		$this->apiUrl = 'https://api.cohere.com/v1/generate';
    } 

     /**
     * Cohere Api Call for retrieving translation.
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
				$result = $this->translateCohereRequest($chunk, $targetLanguage, $sourceLanguage);
				$results[] = $result;
			}
        }
        // Merge the results and return
        return implode('', $results);
    }	

	public function translateCohereRequest($content, $targetLanguage, $sourceLanguage)
	{

        if (!$this->containsHtmlTags($content)) {
            // Remove the part "(keeping HTML unchanged)" from the prompt
            $this->prompt = preg_replace('/ \(keeping HTML unchanged\)/', '', $this->prompt);
        }
		$finalPrompt = sprintf($this->prompt, $targetLanguage) . $content;   

        // Data to be sent in the POST request
        $requestPayload = [
            "model" => "command",
            "prompt" => $finalPrompt,
            "max_tokens" => 5000,
            "temperature" => 0.9,
            "k" => 0,
            "stop_sequences" => [],
            "return_likelihoods" => "NONE"
        ];

        $jsonPayload = json_encode($requestPayload);
        
        try {

        // Initialize cURL
        $ch = curl_init($this->apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);

        // Execute cURL request
        $response = curl_exec($ch);
        curl_close($ch);
        $responseArray = json_decode($response, true);
        // Check if 'generations' is set and not empty
        
        $translatedText = '';
        if (isset($responseArray['generations']) && !empty($responseArray['generations'])) {
            // Extract the first generation's text
            $translatedText = $responseArray['generations'][0]['text'];
        } 
        else if(!is_array($responseArray) || ($responseArray['meesage']=='invalid api token')) {
            $translatedText = 400;
        } 
        else {
            $translatedText = $content;
        }
 
        return $translatedText;

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
    // Function to check if a string contains HTML tags
    public function containsHtmlTags($content) {
        return preg_match('/<[^<]+>/', $content);
    }


}