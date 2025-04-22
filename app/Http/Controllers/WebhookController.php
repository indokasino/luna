<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Qna;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;

class WebhookController extends Controller
{
    protected $openAiService;

    /**
     * Membuat instance controller baru.
     */
    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    /**
     * Menangani webhook dari Chatbot.com
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Log request untuk debugging
        LaravelLog::info('Webhook request received', ['payload' => $request->all()]);
        
        try {
            // Handle Chatbot.com challenge verification
            if ($request->has('challenge')) {
                return response()->json($request->challenge);
            }
            
            // Ekstrak pertanyaan dari berbagai format payload yang mungkin
            $question = $this->extractQuestion($request);
            
            // Jika tidak ada pertanyaan, kirim respons default
            if (empty($question)) {
                return response()->json([
                    'responses' => [
                        [
                            'type' => 'text',
                            'message' => 'Maaf, saya tidak dapat memahami pertanyaan Anda. Mohon coba lagi.'
                        ]
                    ]
                ]);
            }
            
            // Cari jawaban dari database
            $qna = Qna::findMatch($question);
            
            // Jika ada jawaban di database, kirim itu
            if ($qna) {
                $answer = $qna->answer;
                $source = 'manual';
                $isManual = true;
                $confidenceScore = $qna->confidence_score;
            } else {
                // Jika tidak ada jawaban di database, gunakan OpenAI
                $response = $this->openAiService->getCompletion($question);
                
                $answer = $response['content'] ?? 'Maaf, saya tidak dapat memproses permintaan Anda saat ini.';
                $source = 'gpt-4';
                $isManual = false;
                $confidenceScore = null;
            }
            
            // Catat interaksi di log
            Log::create([
                'question' => $question,
                'answer' => $answer,
                'source' => $source,
                'is_manual' => $isManual,
                'confidence_score' => $confidenceScore,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $request->all(),
                'response_data' => [
                    'type' => 'text',
                    'message' => $answer
                ]
            ]);
            
            // Format respons sesuai dengan Chatbot.com
            return response()->json([
                'responses' => [
                    [
                        'type' => 'text',
                        'message' => $answer
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            // Log error
            LaravelLog::error('Webhook error: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all()
            ]);
            
            // Kirim respons error
            return response()->json([
                'responses' => [
                    [
                        'type' => 'text',
                        'message' => 'Maaf, terjadi kesalahan. Silakan coba lagi nanti.'
                    ]
                ]
            ]);
        }
    }
    
    /**
     * Ekstrak pertanyaan dari berbagai format payload
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function extractQuestion(Request $request)
    {
        $question = null;
        
        // Coba ekstrak dari format responses array
        if ($request->has('responses') && is_array($request->responses)) {
            foreach ($request->responses as $response) {
                if (isset($response['type']) && $response['type'] === 'INPUT_MESSAGE' && isset($response['value'])) {
                    $question = $response['value'];
                    break;
                }
            }
        }
        
        // Coba lokasi potensial lainnya
        if (empty($question)) {
            if ($request->has('message')) {
                $question = $request->message;
            } elseif ($request->has('text')) {
                $question = $request->text;
            } elseif ($request->has('question')) {
                $question = $request->question;
            }
        }
        
        return $question;
    }
}