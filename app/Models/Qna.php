<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qna extends Model
{
    use HasFactory;

    /**
     * Tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'qnas';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question',
        'answer',
        'tags',
        'confidence_score',
        'status',
    ];

    /**
     * Atribut yang harus dikonversi.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'confidence_score' => 'float',
    ];

    /**
     * Scope untuk pencarian pertanyaan
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where('question', 'like', "%{$search}%")
                         ->orWhere('answer', 'like', "%{$search}%")
                         ->orWhere('tags', 'like', "%{$search}%");
        }
        
        return $query;
    }

    /**
     * Scope untuk filter berdasarkan status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        if (!empty($status) && $status !== 'all') {
            return $query->where('status', $status);
        }
        
        return $query;
    }

    /**
     * Scope untuk filter berdasarkan tag
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tag
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTag($query, $tag)
    {
        if (!empty($tag)) {
            return $query->where('tags', 'like', "%{$tag}%");
        }
        
        return $query;
    }

    /**
     * Cari pertanyaan yang cocok dengan pertanyaan pengguna
     *
     * @param string $question
     * @return \App\Models\Qna|null
     */
    public static function findMatch($question)
    {
        // Coba cari kecocokan persis (case insensitive)
        $match = self::where('status', 'active')
                     ->whereRaw('LOWER(question) = ?', [strtolower(trim($question))])
                     ->orderBy('confidence_score', 'desc')
                     ->first();
        
        if ($match) {
            return $match;
        }
        
        // Jika tidak ada kecocokan persis, coba cari dengan kata kunci
        return self::where('status', 'active')
                   ->whereRaw('MATCH(question) AGAINST(? IN NATURAL LANGUAGE MODE)', [$question])
                   ->orderBy('confidence_score', 'desc')
                   ->first();
    }
}