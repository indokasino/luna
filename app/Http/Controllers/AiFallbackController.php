<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Prompt;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;

class AiFallbackController extends Controller
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
     * Menangani fallback ke OpenAI GPT
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
        ]);
        
        try {
            // Dapatkan pertanyaan dari request
            $question = $request->input('question');
            
            // Panggil OpenAI untuk mendapatkan respons
            $response = $this->openAiService->getCompletion($question);
            
            // Catat interaksi di log
            Log::create([
                'question' => $question,
                'answer' => $response['content'] ?? 'Error: No content in response',
                'source' => 'gpt-4',
                'is_manual' => false,
                'confidence_score' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $request->all(),
                'response_data' => $response
            ]);
            
            return response()->json([
                'success' => true,
                'answer' => $response['content'] ?? 'Error: No content in response',
                'source' => 'gpt-4'
            ]);
            
        } catch (\Exception $e) {
            // Log error
            LaravelLog::error('AI Fallback error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            // Kirim respons error
            return response()->json([
                'success' => false,
                'message' => 'Error processing AI fallback: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan prompt yang aktif saat ini
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrompt()
    {
        $prompt = Prompt::getActive();
        
        if (!$prompt) {
            return response()->json([
                'success' => false,
                'message' => 'No active prompt found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'prompt' => $prompt
        ]);
    }

    /**
     * Mengatur prompt aktif
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPrompt(Request $request, $id)
    {
        $prompt = Prompt::findOrFail($id);
        
        if ($prompt->activate()) {
            return response()->json([
                'success' => true,
                'message' => 'Prompt activated successfully',
                'prompt' => $prompt
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to activate prompt'
        ], 500);
    }
}