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
        $this->apiModel = ($extConf["opencohereapiModel"] ?? '') ?: 'command-a-03-2025';
        $this->apiKey = $extConf["opencohereapiKey"];
        // Cohere removed the Generate API on 2025-09-15, the Chat API v2 replaces it
        $this->apiUrl = 'https://api.cohere.com/v2/chat';
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

    public function translateCohereRequest($content, $targetLanguage, $sourceLanguage)
    {

        if (!$this->containsHtmlTags($content)) {
            // Remove the part "(keeping HTML unchanged)" from the prompt
            $this->prompt = preg_replace('/ \(keeping HTML unchanged\)/', '', $this->prompt);
        }
        $finalPrompt = sprintf($this->prompt, $targetLanguage) . $content;

        // Data to be sent in the POST request
        $requestPayload = [
            "model" => $this->apiModel,
            "temperature" => 0.3,
            "messages" => [
                [
                    "role" => "system",
                    "content" => 'You are a translation engine. Always translate the text you receive, even if it is a short title, headline or single phrase. Never return the source text unchanged. Only return the translated text without any explanations, prefixes, or additional content. Do not add Markdown formatting (no **bold**, _italic_, bullet points, etc.) unless that exact formatting was already present in the source text.'
                ],
                [
                    "role" => "user",
                    "content" => $finalPrompt
                ]
            ]
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

        $translatedText = '';
        if (isset($responseArray['message']['content'][0]['text'])) {
            $translatedText = $responseArray['message']['content'][0]['text'];
        }
        else {
            $result['status']  = false;
            $result['message'] = ($responseArray['message']) ?? 'Invalid api key or url';
            if(is_array($result['message'])){
                $result['message'] = json_encode($result['message']);
            }

            return $result;
        }

        return $translatedText;

        } catch (Exception $e) {
            // Handle exceptions
            throw new \Exception($e->getMessage());
        }
    }

    public function validateCredentials() {
        $response = $this->translateRequest('Test','de', 'en',);
        if(is_array($response)) {
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
