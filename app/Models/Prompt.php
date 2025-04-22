<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'content',
        'is_active',
        'description',
    ];

    /**
     * Atribut yang harus dikonversi.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Mendapatkan prompt yang aktif saat ini
     *
     * @return static|null
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Mengaktifkan prompt ini dan menonaktifkan semua yang lain
     *
     * @return bool
     */
    public function activate()
    {
        // Nonaktifkan semua prompt
        self::query()->update(['is_active' => false]);
        
        // Aktifkan prompt ini
        $this->is_active = true;
        
        return $this->save();
    }

    /**
     * Dapatkan konten prompt atau gunakan default jika tidak ada
     *
     * @return string
     */
    public static function getContent()
    {
        $activePrompt = self::getActive();
        
        if ($activePrompt) {
            return $activePrompt->content;
        }
        
        // Default prompt jika tidak ada yang aktif
        return 'Anda adalah asisten AI yang membantu menjawab pertanyaan tentang produk dan layanan kami.';
    }
}