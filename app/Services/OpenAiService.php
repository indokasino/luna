<?php

namespace App\Services;

use App\Models\Prompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    protected $apiKey;
    protected $model;
    protected $maxRetries;
    protected $fallbackModel;
    protected $fallbackResponse;

    /**
     * Membuat instance service baru.
     */
    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4');
        $this->maxRetries = config('services.openai.max_retries', 3);
        $this->fallbackModel = config('services.openai.fallback_model', 'gpt-4o');
        $this->fallbackResponse = config('services.openai.fallback_response', 'Maaf, saya tidak dapat memproses permintaan Anda saat ini.');
    }

    /**
     * Mendapatkan completion dari OpenAI.
     *
     * @param  string  $question
     * @return array
     */
    public function getCompletion(string $question): array
    {
        $systemPrompt = Prompt::getContent();
        
        // Map model jika perlu
        $useModel = $this->mapModel($this->model);
        
        // Coba panggil API
        try {
            $response = $this->callOpenAi($useModel, $systemPrompt, $question);
            
            if (isset($response['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'model' => $this->model,
                    'content' => $response['choices'][0]['message']['content']
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            
            // Coba dengan model fallback
            if ($this->fallbackModel && $this->fallbackModel !== $this->model) {
                try {
                    $fallbackModel = $this->mapModel($this->fallbackModel);
                    $response = $this->callOpenAi($fallbackModel, $systemPrompt, $question);
                    
                    if (isset($response['choices'][0]['message']['content'])) {
                        return [
                            'success' => true,
                            'model' => $this->fallbackModel,
                            'content' => $response['choices'][0]['message']['content']
                        ];
                    }
                } catch (\Exception $fallbackError) {
                    Log::error('OpenAI fallback API error: ' . $fallbackError->getMessage());
                }
            }
        }
        
        // Jika tidak ada yang berhasil, kembalikan respons fallback
        return [
            'success' => false,
            'model' => 'fallback',
            'content' => $this->fallbackResponse
        ];
    }

    /**
     * Memanggil API OpenAI.
     *
     * @param  string  $model
     * @param  string  $systemPrompt
     * @param  string  $question
     * @return array
     */
    protected function callOpenAi(string $model, string $systemPrompt, string $question): array
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $retries = 0;
        $lastError = null;
        
        while ($retries < $this->maxRetries) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->post($url, [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $question]
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 1000
                    ]);
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                // Log error details for debugging
                Log::warning('OpenAI API error response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'retry' => $retries + 1,
                ]);
                
                $lastError = new \Exception('API returned status code: ' . $response->status());
            } catch (\Exception $e) {
                Log::warning('OpenAI API exception', [
                    'message' => $e->getMessage(),
                    'retry' => $retries + 1,
                ]);
                
                $lastError = $e;
            }
            
            $retries++;
            
            // Add exponential backoff delay
            if ($retries < $this->maxRetries) {
                $delay = (2 ** $retries) * 100; // milliseconds
                usleep($delay * 1000); // convert to microseconds
            }
        }
        
        // Throw the last error if all retries failed
        throw $lastError ?? new \Exception('Unknown error when calling OpenAI API');
    }

    /**
     * Map model name jika diperlukan.
     *
     * @param  string  $model
     * @return string
     */
    protected function mapModel(string $model): string
    {
        $mappings = [
            'gpt-4.1' => 'gpt-4-turbo', // Sesuaikan jika diperlukan
        ];
        
        return $mappings[$model] ?? $model;
    }
}