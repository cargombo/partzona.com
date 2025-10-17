<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAITranslationService
{
    private static string $apiKey;
    private static string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private static string $model = 'gpt-4o-mini'; // Cost-effective model for translations

    /**
     * Initialize API key from environment
     */
    private static function init()
    {
        self::$apiKey = env('OPENAI_API_KEY');

        if (!self::$apiKey) {
            throw new \Exception('OpenAI API key not configured in .env file');
        }
    }

    /**
     * Translate a single text from Chinese to English
     *
     * @param string $text Chinese text to translate
     * @return string Translated English text
     */
    public static function translateToEnglish(string $text): string
    {
        try {
            self::init();

            // If text is already in English (no Chinese characters), return as is
            if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $text)) {
                return $text;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(self::$apiUrl, [
                'model' => self::$model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional translator specializing in automotive parts and accessories. Translate Chinese product titles to English accurately and naturally. Keep brand names and model names as they are. Focus on clarity and readability.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Translate this Chinese product title to English:\n\n{$text}"
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 150,
            ]);

            if ($response->failed()) {
                Log::error('OpenAI translation API failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return $text; // Return original text on failure
            }

            $result = $response->json();

            if (isset($result['choices'][0]['message']['content'])) {
                $translation = trim($result['choices'][0]['message']['content']);

                Log::info('OpenAI translation', [
                    'original' => $text,
                    'translated' => $translation,
                    'tokens_used' => $result['usage']['total_tokens'] ?? null
                ]);

                return $translation;
            }

            return $text;

        } catch (\Exception $e) {
            Log::error('OpenAI translation error', [
                'text' => $text,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return $text; // Return original text on error
        }
    }

    /**
     * Translate multiple texts to multiple languages in batch
     *
     * @param array $texts Array of Chinese texts to translate
     * @param array $targetLanguages Array of target language codes ['az', 'ru', 'en']
     * @return array Associative array with language codes as keys, each containing array of translations
     */
    public static function translateBatchMultiLanguage(array $texts, array $targetLanguages = ['az', 'ru', 'en']): array
    {
        try {
            self::init();

            // Filter out texts that don't contain Chinese characters
            $textsToTranslate = [];
            $indexMap = [];

            foreach ($texts as $index => $text) {
                if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $text)) {
                    $textsToTranslate[] = $text;
                    $indexMap[] = $index;
                }
            }

            // If no Chinese texts, return original array for all languages
            if (empty($textsToTranslate)) {
                $result = [];
                foreach ($targetLanguages as $lang) {
                    $result[$lang] = $texts;
                }
                return $result;
            }

            // Create numbered list for batch translation
            $numberedTexts = '';
            foreach ($textsToTranslate as $i => $text) {
                $numberedTexts .= ($i + 1) . ". {$text}\n";
            }

            $languageNames = [
                'az' => 'Azerbaijani',
                'ru' => 'Russian',
                'en' => 'English'
            ];

            $languagesString = implode(', ', array_map(fn($code) => $languageNames[$code] ?? $code, $targetLanguages));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90)->post(self::$apiUrl, [
                'model' => self::$model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a professional translator specializing in automotive parts and accessories. Translate each numbered Chinese product title to {$languagesString}. Return the translations in the following format for EACH item:

1. [AZ] Azerbaijani translation
   [RU] Russian translation
   [EN] English translation

2. [AZ] Azerbaijani translation
   [RU] Russian translation
   [EN] English translation

And so on. Keep brand names and model names as they are. Focus on clarity and readability."
                    ],
                    [
                        'role' => 'user',
                        'content' => "Translate these Chinese product titles:\n\n{$numberedTexts}"
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 3000,
            ]);

            if ($response->failed()) {
                Log::error('OpenAI multi-language translation API failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                // Return original texts for all languages on failure
                $result = [];
                foreach ($targetLanguages as $lang) {
                    $result[$lang] = $texts;
                }
                return $result;
            }

            $result = $response->json();

            if (isset($result['choices'][0]['message']['content'])) {
                $translatedText = trim($result['choices'][0]['message']['content']);

                // Parse multi-language translations
                $lines = explode("\n", $translatedText);
                $translations = [];
                foreach ($targetLanguages as $lang) {
                    $translations[$lang] = [];
                }

                $currentItem = -1;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    // Check if this is a new item number
                    if (preg_match('/^(\d+)\.\s*\[([A-Z]{2})\]\s*(.+)$/', $line, $matches)) {
                        $currentItem = intval($matches[1]) - 1;
                        $lang = strtolower($matches[2]);
                        $translation = trim($matches[3]);
                        if (in_array($lang, $targetLanguages)) {
                            $translations[$lang][$currentItem] = $translation;
                        }
                    }
                    // Check if this is a continuation line with language tag
                    elseif (preg_match('/^\[([A-Z]{2})\]\s*(.+)$/', $line, $matches)) {
                        $lang = strtolower($matches[1]);
                        $translation = trim($matches[2]);
                        if ($currentItem >= 0 && in_array($lang, $targetLanguages)) {
                            $translations[$lang][$currentItem] = $translation;
                        }
                    }
                }

                // Map translations back to original array positions
                $finalResult = [];
                foreach ($targetLanguages as $lang) {
                    $finalResult[$lang] = $texts; // Start with originals
                    foreach ($indexMap as $i => $originalIndex) {
                        if (isset($translations[$lang][$i])) {
                            $finalResult[$lang][$originalIndex] = $translations[$lang][$i];
                        }
                    }
                }

                Log::info('OpenAI multi-language batch translation', [
                    'count' => count($textsToTranslate),
                    'languages' => $targetLanguages,
                    'tokens_used' => $result['usage']['total_tokens'] ?? null
                ]);

                return $finalResult;
            }

            // Fallback: return original texts for all languages
            $fallback = [];
            foreach ($targetLanguages as $lang) {
                $fallback[$lang] = $texts;
            }
            return $fallback;

        } catch (\Exception $e) {
            Log::error('OpenAI multi-language batch translation error', [
                'count' => count($texts),
                'languages' => $targetLanguages,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            // Return original texts for all languages on error
            $fallback = [];
            foreach ($targetLanguages as $lang) {
                $fallback[$lang] = $texts;
            }
            return $fallback;
        }
    }

    /**
     * Translate multiple texts in batch (more efficient) - DEPRECATED, use translateBatchMultiLanguage
     *
     * @param array $texts Array of Chinese texts to translate
     * @return array Array of translated English texts (same order)
     */
    public static function translateBatch(array $texts): array
    {
        try {
            self::init();

            // Filter out texts that don't contain Chinese characters
            $textsToTranslate = [];
            $indexMap = [];

            foreach ($texts as $index => $text) {
                if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $text)) {
                    $textsToTranslate[] = $text;
                    $indexMap[] = $index;
                }
            }

            // If no Chinese texts, return original array
            if (empty($textsToTranslate)) {
                return $texts;
            }

            // Create numbered list for batch translation
            $numberedTexts = '';
            foreach ($textsToTranslate as $i => $text) {
                $numberedTexts .= ($i + 1) . ". {$text}\n";
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post(self::$apiUrl, [
                'model' => self::$model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional translator specializing in automotive parts and accessories. Translate each numbered Chinese product title to English accurately. Return ONLY the numbered translations in the same format, one per line. Keep brand names and model names as they are.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Translate these Chinese product titles to English:\n\n{$numberedTexts}"
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]);

            if ($response->failed()) {
                Log::error('OpenAI batch translation API failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return $texts; // Return original texts on failure
            }

            $result = $response->json();

            if (isset($result['choices'][0]['message']['content'])) {
                $translatedText = trim($result['choices'][0]['message']['content']);

                // Parse numbered translations
                $lines = explode("\n", $translatedText);
                $translations = [];

                foreach ($lines as $line) {
                    // Match pattern: "1. Translation" or "1) Translation"
                    if (preg_match('/^\d+[\.\)]\s*(.+)$/', trim($line), $matches)) {
                        $translations[] = trim($matches[1]);
                    }
                }

                // Map translations back to original array
                $result = $texts;
                foreach ($indexMap as $i => $originalIndex) {
                    if (isset($translations[$i])) {
                        $result[$originalIndex] = $translations[$i];
                    }
                }

                Log::info('OpenAI batch translation', [
                    'count' => count($textsToTranslate),
                    'tokens_used' => $result['usage']['total_tokens'] ?? null
                ]);

                return $result;
            }

            return $texts;

        } catch (\Exception $e) {
            Log::error('OpenAI batch translation error', [
                'count' => count($texts),
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            return $texts; // Return original texts on error
        }
    }
}
