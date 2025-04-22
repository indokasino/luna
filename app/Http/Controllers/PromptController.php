<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromptController extends Controller
{
    /**
     * Menampilkan daftar prompt.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $prompts = Prompt::latest()->get();
        
        return view('admin.prompts.index', compact('prompts'));
    }

    /**
     * Menampilkan form untuk membuat prompt baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.prompts.edit');
    }

    /**
     * Menyimpan prompt baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'content' => 'required|string',
            'description' => 'nullable|string',
        ]);
        
        $prompt = Prompt::create($validated);
        
        // Activate if requested
        if ($request->has('activate') && $request->activate) {
            $prompt->activate();
        }
        
        return redirect()->route('prompts.index')
            ->with('success', 'Prompt berhasil dibuat.');
    }

    /**
     * Menampilkan form untuk mengedit prompt.
     *
     * @param  \App\Models\Prompt  $prompt
     * @return \Illuminate\View\View
     */
    public function edit(Prompt $prompt)
    {
        return view('admin.prompts.edit', compact('prompt'));
    }

    /**
     * Mengupdate prompt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Prompt  $prompt
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Prompt $prompt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'content' => 'required|string',
            'description' => 'nullable|string',
        ]);
        
        $prompt->update($validated);
        
        // Activate if requested
        if ($request->has('activate') && $request->activate) {
            $prompt->activate();
        }
        
        return redirect()->route('prompts.index')
            ->with('success', 'Prompt berhasil diperbarui.');
    }

    /**
     * Menghapus prompt.
     *
     * @param  \App\Models\Prompt  $prompt
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Prompt $prompt)
    {
        // Jangan hapus jika ini satu-satunya prompt yang aktif
        if ($prompt->is_active && Prompt::count() === 1) {
            return redirect()->route('prompts.index')
                ->with('error', 'Tidak dapat menghapus satu-satunya prompt yang aktif.');
        }
        
        // Jika menghapus prompt yang aktif, aktifkan prompt lain
        if ($prompt->is_active) {
            $otherPrompt = Prompt::where('id', '!=', $prompt->id)->first();
            if ($otherPrompt) {
                $otherPrompt->activate();
            }
        }
        
        $prompt->delete();
        
        return redirect()->route('prompts.index')
            ->with('success', 'Prompt berhasil dihapus.');
    }

    /**
     * Mengaktifkan prompt.
     *
     * @param  \App\Models\Prompt  $prompt
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate(Prompt $prompt)
    {
        $prompt->activate();
        
        return redirect()->route('prompts.index')
            ->with('success', 'Prompt berhasil diaktifkan.');
    }
}