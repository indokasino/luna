<?php

namespace App\Http\Controllers;

use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Menampilkan halaman settings.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = [
            'webhook_secret' => config('app.webhook_secret', ''),
            'openai_api_key' => config('services.openai.api_key', ''),
            'openai_model' => config('services.openai.model', 'gpt-4'),
            'fallback_model' => config('services.openai.fallback_model', 'gpt-4o'),
            'fallback_response' => config('services.openai.fallback_response', 'Maaf, saya tidak dapat memproses permintaan Anda saat ini.'),
            'max_retries' => config('services.openai.max_retries', 3),
            'rate_limit' => config('services.openai.rate_limit', 10),
            'log_retention_days' => config('app.log_retention_days', 90),
        ];
        
        return view('admin.settings', compact('settings'));
    }

    /**
     * Mengupdate settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'openai_api_key' => 'required|string',
            'openai_model' => 'required|string',
            'fallback_model' => 'required|string',
            'fallback_response' => 'required|string',
            'max_retries' => 'required|integer|min:1|max:5',
            'rate_limit' => 'required|integer|min:1|max:60',
            'log_retention_days' => 'required|integer|min:1|max:365',
        ]);
        
        // Update settings di .env file
        $this->updateEnvironmentFile([
            'OPENAI_API_KEY' => $validated['openai_api_key'],
            'OPENAI_MODEL' => $validated['openai_model'],
            'OPENAI_FALLBACK_MODEL' => $validated['fallback_model'],
            'OPENAI_FALLBACK_RESPONSE' => $validated['fallback_response'],
            'OPENAI_MAX_RETRIES' => $validated['max_retries'],
            'OPENAI_RATE_LIMIT' => $validated['rate_limit'],
            'LOG_RETENTION_DAYS' => $validated['log_retention_days'],
        ]);
        
        // Update config runtime
        config([
            'services.openai.api_key' => $validated['openai_api_key'],
            'services.openai.model' => $validated['openai_model'],
            'services.openai.fallback_model' => $validated['fallback_model'],
            'services.openai.fallback_response' => $validated['fallback_response'],
            'services.openai.max_retries' => $validated['max_retries'],
            'services.openai.rate_limit' => $validated['rate_limit'],
            'app.log_retention_days' => $validated['log_retention_days'],
        ]);
        
        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Generate webhook token baru.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateToken()
    {
        $newToken = Str::random(32);
        
        // Update webhook token di .env file
        $this->updateEnvironmentFile([
            'WEBHOOK_SECRET' => $newToken,
        ]);
        
        // Update config runtime
        config(['app.webhook_secret' => $newToken]);
        
        return redirect()->route('settings.index')
            ->with('success', 'Token webhook baru berhasil dibuat.')
            ->with('new_token', $newToken);
    }

    /**
     * Test koneksi OpenAI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testOpenAi(Request $request, OpenAiService $openAiService)
    {
        try {
            $response = $openAiService->getCompletion('Berikan respons singkat sebagai test koneksi: "Halo, tes koneksi berhasil!"');
            
            return response()->json([
                'success' => true,
                'message' => 'Koneksi ke OpenAI berhasil',
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('Test OpenAI connection failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke OpenAI gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update file .env
     *
     * @param  array  $data
     * @return bool
     */
    protected function updateEnvironmentFile(array $data)
    {
        $path = app()->environmentFilePath();

        if (!file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);

        foreach ($data as $key => $value) {
            // Escape quotes
            $value = str_replace('"', '\"', $value);
            
            // Update existing key
            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}=\"{$value}\"", $content);
            } 
            // Add new key
            else {
                $content .= "\n{$key}=\"{$value}\"";
            }
        }

        file_put_contents($path, $content);

        return true;
    }
}