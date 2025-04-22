<?php

namespace App\Http\Controllers;

use App\Models\Qna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QnaController extends Controller
{
    /**
     * Menampilkan daftar pertanyaan dan jawaban.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $tag = $request->input('tag');
        
        $qnas = Qna::search($search)
                   ->status($status)
                   ->tag($tag)
                   ->latest()
                   ->paginate(30);
        
        return view('admin.qna.index', compact('qnas', 'search', 'status', 'tag'));
    }

    /**
     * Menampilkan formulir untuk membuat entri baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.qna.edit');
    }

    /**
     * Menyimpan entri baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $this->validateQna($request);
        
        Qna::create($validated);
        
        return redirect()->route('qna.index')
                         ->with('success', 'Data berhasil ditambahkan.');
    }

    /**
     * Menampilkan formulir untuk mengedit entri yang ada.
     *
     * @param  \App\Models\Qna  $qna
     * @return \Illuminate\View\View
     */
    public function edit(Qna $qna)
    {
        return view('admin.qna.edit', compact('qna'));
    }

    /**
     * Memperbarui entri di database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Qna  $qna
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Qna $qna)
    {
        $validated = $this->validateQna($request);
        
        $qna->update($validated);
        
        return redirect()->route('qna.index')
                         ->with('success', 'Data berhasil diperbarui.');
    }

    /**
     * Menghapus entri dari database.
     *
     * @param  \App\Models\Qna  $qna
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Qna $qna)
    {
        $qna->delete();
        
        return redirect()->route('qna.index')
                         ->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Menampilkan API JSON publik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function api(Request $request)
    {
        $status = $request->input('status', 'active');
        $tag = $request->input('tag');
        
        $qnas = Qna::status($status)
                   ->tag($tag)
                   ->get(['id', 'question', 'answer', 'tags', 'status', 'confidence_score', 'created_at', 'updated_at']);
        
        return response()->json($qnas);
    }

    /**
     * Validasi input QnA.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function validateQna(Request $request)
    {
        return $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'tags' => 'nullable|string',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'status' => 'required|in:active,inactive,draft',
        ]);
    }
}