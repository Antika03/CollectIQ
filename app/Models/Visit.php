<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'collect_id',
        'customer_id',
        'ar_agent_id',
        'tanggal_input',
        'hasil_visit',
        'kategori_visit',
        'keterangan_visit',
        'foto_url',
        'no_hp_snapshot',
        'tipe_hunian_snapshot',
        'is_ptp',
    ];

    protected $casts = [
        'tanggal_input' => 'date',
        'is_ptp' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function arAgent()
    {
        return $this->belongsTo(ArAgent::class);
    }

    public function promiseToPays()
    {
        return $this->hasMany(PromiseToPay::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Google Drive Photo
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil Google Drive File ID dari foto_url.
     */
    public function getDriveFileIdAttribute()
    {
        $url = $this->foto_url;

        if (!$url) {
            return null;
        }

        /*
        | Format:
        | https://drive.google.com/file/d/FILE_ID/view
        */
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        /*
        | Format:
        | https://drive.google.com/open?id=FILE_ID
        */
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        /*
        | Format langsung:
        | FILE_ID
        */
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * URL menuju route Laravel untuk menampilkan foto.
     */
    public function getPhotoPreviewAttribute()
    {
        if (!$this->drive_file_id) {
            return null;
        }

        return route('visit.photo', [
            'visit' => $this->id
        ]);
    }

    /**
     * URL Google Drive untuk membuka file secara langsung.
     */
    public function getDriveUrlAttribute()
    {
        if (!$this->drive_file_id) {
            return null;
        }

        return 'https://drive.google.com/file/d/'
            . $this->drive_file_id
            . '/view';
    }

    protected $appends = [
        'drive_file_id',
        'photo_preview',
        'drive_url',
    ];
}