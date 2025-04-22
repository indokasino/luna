<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Qna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    /**
     * Menampilkan daftar log interaksi.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $source = $request->input('source', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        
        $logs = Log::search($search)
                   ->source($source)
                   ->dateRange($dateFrom, $dateTo)
                   ->latest()
                   ->paginate(30);
        
        return view('admin.logs.index', compact('logs', 'search', 'source', 'dateFrom', 'dateTo'));
    }

    /**
     * Menampilkan detail log.
     *
     * @param  \App\Models\Log  $log
     * @return \Illuminate\View\View
     */
    public function show(Log $log)
    {
        return view('admin.logs.show', compact('log'));
    }

    /**
     * Menambahkan interaksi log ke database QnA.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Log  $log
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addToQna(Request $request, Log $log)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'tags' => 'nullable|string',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'status' => 'required|in:active,inactive,draft',
        ]);
        
        // Cek apakah pertanyaan sudah ada di QnA
        $existingQna = Qna::whereRaw('LOWER(question) = ?', [strtolower($validated['question'])])
                         ->first();
        
        if ($existingQna) {
            // Update existing record
            $existingQna->update($validated);
            $message = 'Data QnA yang sudah ada berhasil diperbarui.';
        } else {
            // Create new record
            Qna::create($validated);
            $message = 'Data berhasil ditambahkan ke QnA.';
        }
        
        return redirect()->route('logs.index')
                         ->with('success', $message);
    }

    /**
     * Membersihkan log lama.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clean(Request $request)
    {
        $days = $request->input('days', 90);
        
        $count = Log::cleanOldLogs($days);
        
        return redirect()->route('logs.index')
                         ->with('success', "Berhasil menghapus {$count} log lama.");
    }

    /**
     * Export logs ke CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $search = $request->input('search');
        $source = $request->input('source', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        
        $logs = Log::search($search)
                   ->source($source)
                   ->dateRange($dateFrom, $dateTo)
                   ->latest()
                   ->get();
        
        // Buat nama file
        $filename = 'logs_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Buat file CSV 
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, ['ID', 'Question', 'Answer', 'Source', 'Is Manual', 'Confidence Score', 'IP Address', 'Created At']);
            
            // Data CSV
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->question,
                    $log->answer,
                    $log->source,
                    $log->is_manual ? 'Yes' : 'No',
                    $log->confidence_score,
                    $log->ip_address,
                    $log->created_at
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}