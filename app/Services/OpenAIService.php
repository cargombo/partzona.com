<?php

namespace App\Services;

use OpenAI;
use Exception;

class OpenAIService
{
    private $client;

    public function __construct()
    {
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    public function translate($keyword)
    {
        try {
            $prompt = <<<PROMPT
You're an expert in automotive parts with fluent Chinese. Follow these steps:

1. Analyze "{$keyword}":
   - Correct any obvious misspellings (like "tidda"→"Tiida", "amarizator"→"amortizator")
   - Determine if it's a car part (be lenient - 90% confidence threshold)

2. If it's a car part:
   - Provide the MOST COMMON Chinese translation used in auto parts markets
   - Use simplified Chinese (Mainland China standard)

Return response in this exact JSON format:
{
  "keyword": "translated_keyword",
  "status": boolean
}

Only return valid JSON, no additional text or explanations.
PROMPT;

            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',  // Fixed model name (assuming you meant gpt-4 instead of gpt-4o-mini)
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that analyzes keywords and translates them. Always return valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            $result = json_decode($content, true);

            // Validate the response structure
            if (!isset($result['keyword']) || !isset($result['status'])) {
                throw new \Exception("Invalid response format from AI");
            }

            return $result;

        } catch (\Exception $e) {
            error_log("Translation Error: " . $e->getMessage());
            return [
                'keyword' => $keyword,
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }



}
