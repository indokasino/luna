<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question',
        'answer',
        'source',
        'is_manual',
        'confidence_score',
        'ip_address',
        'user_agent',
        'request_data',
        'response_data',
    ];

    /**
     * Atribut yang harus dikonversi.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_manual' => 'boolean',
        'confidence_score' => 'float',
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    /**
     * Scope untuk filter berdasarkan sumber
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $source
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSource($query, $source)
    {
        if (!empty($source) && $source !== 'all') {
            return $query->where('source', $source);
        }
        
        return $query;
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $dateFrom
     * @param string $dateTo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $dateFrom, $dateTo)
    {
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        return $query;
    }

    /**
     * Scope untuk pencarian
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where('question', 'like', "%{$search}%")
                         ->orWhere('answer', 'like', "%{$search}%");
        }
        
        return $query;
    }

    /**
     * Metode untuk membersihkan log lama
     *
     * @param int $days
     * @return int
     */
    public static function cleanOldLogs($days = 90)
    {
        return self::where('created_at', '<', now()->subDays($days))->delete();
    }
}