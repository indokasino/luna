<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Qna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard dengan statistik.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Total QnA
        $totalQna = Qna::count();
        $activeQna = Qna::where('status', 'active')->count();
        
        // Total interaksi
        $totalInteractions = Log::count();
        $manualResponses = Log::where('is_manual', true)->count();
        $aiResponses = Log::where('is_manual', false)->count();
        
        // Persentase jawaban manual
        $manualPercentage = $totalInteractions > 0 ? round(($manualResponses / $totalInteractions) * 100) : 0;
        
        // Interaksi per hari (7 hari terakhir)
        $dailyInteractions = Log::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        // Format untuk chart
        $chartLabels = $dailyInteractions->pluck('date')->map(function($date) {
            return date('d M', strtotime($date));
        })->toJson();
        
        $chartData = $dailyInteractions->pluck('count')->toJson();
        
        // Interaksi terbaru
        $recentInteractions = Log::latest()->limit(10)->get();
        
        return view('admin.dashboard', compact(
            'totalQna',
            'activeQna',
            'totalInteractions',
            'manualResponses',
            'aiResponses',
            'manualPercentage',
            'chartLabels',
            'chartData',
            'recentInteractions'
        ));
    }
}