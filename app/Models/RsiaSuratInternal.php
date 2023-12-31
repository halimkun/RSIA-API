<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsiaSuratInternal extends Model
{
    use HasFactory;

    protected $table = 'rsia_surat_internal';

    protected $guarded = [];

    protected $primaryKey = 'no_surat';

    public $timestamps = false;

    
    
    protected $casts = [
        'no_surat' => 'string'
    ];

    public function pj_detail()
    {
        return $this->hasOne(Petugas::class, 'nip', 'pj');
    }

    public function penerima()
    {
        return $this->hasMany(RsiaSuratInternalPenerima::class, 'no_surat', 'no_surat');
    }
}
