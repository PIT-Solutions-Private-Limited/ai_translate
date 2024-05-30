<?php
namespace PITS\AiTranslate\Service;

use GuzzleHttp\Exception\ClientException;
use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OpenAiService
{

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiModel;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    protected  $prompt = 'Translate the following text from %s language to %s language (keeping HTML unchanged): ';

    private const GPT4_ENDPOINT = "https://api.openai.com/v1/chat/completions";

    /** 
     * Description
     * @return type
     */
    public function __construct()
    {
        $extConf = $GLOBALS["TYPO3_CONF_VARS"]["EXTENSIONS"]["ai_translate"];
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

        $this->apiModel = $extConf["openaiapiModel"];
        $this->apiKey = $extConf["openaiapiKey"];
    }

     /**
     * Openai Api Call for retrieving translation.
     * @return type
     */
    public function translateRequest($content, $targetLanguage, $sourceLanguage)
    {
        $results = [];
        if($content!='') {
            // Split content into chunks of 4000 characters
            $chunks = str_split($content, 4000);

            // Translate each chunk separately
            foreach ($chunks as $chunk) {
                $result = $this->translateGptFourRequest($chunk, $targetLanguage, $sourceLanguage);
                $results[] = $result;
            }
        }
        if(isset($results[0]['status']) && $results[0]['status'] == false){
            return $results[0];
        }
        // Merge the results and return
        return implode('', $results);
    }

    public function translateGptFourRequest($content, $targetLanguage, $sourceLanguage)
    {
        try {
            $maxTokens = ($this->apiModel=='gpt-4') ? 7000 : 4095;
            $finalPrompt = sprintf($this->prompt, $sourceLanguage, $targetLanguage) . $content;
            $jsonContent = [
                "model" => $this->apiModel,
                "messages" => [
                    ["role" => "user", "content" => "Gpt4"],
                    ["role" => "assistant", "content" => $finalPrompt],
                ],
                "max_tokens" => $maxTokens,
            ];
            $response = $this->makeRequest(self::GPT4_ENDPOINT, $jsonContent);
        } catch (ClientException $e) {
            $result            = [];
            $result['status']  = false;
            if($e->getResponse()->getStatusCode()!='401') {
                $result['message'] = $e->getMessage();
            }
            else {
                $result['message'] = 'Invalid api key or url';  
            }
            return $result;
        }

        $result = json_decode($response->getBody()->getContents(), true);
        return $result['choices'][0]['message']['content'];
    }

    
    private function makeRequest($endpoint, $jsonContent)
    {
        return $this->requestFactory->request(
            $endpoint,
            "POST",
            [
                "headers" => [
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . $this->apiKey,
                ],
                "json" => $jsonContent,
            ]
        );
    }

    public function validateCredentials() {
        $response = $this->translateRequest('Check the values given','DE', 'EN');
        if(!is_array($response)){
            $result['status']  = true;
            return $result;
        }
        else{
            return $response;
        }
    }
}
