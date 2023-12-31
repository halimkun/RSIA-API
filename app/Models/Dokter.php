<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;

    protected $table = 'dokter';

    // custom function
    public static function getSpesialis($kd_dokter) 
    {
        $dokter = Dokter::select('spesialis.kd_sps', 'spesialis.nm_sps')
            ->join('spesialis', 'spesialis.kd_sps', '=', 'dokter.kd_sps')
            ->where('dokter.kd_dokter', $kd_dokter)
            ->first();

        return $dokter;
    }


    public function regPeriksa()
    {
        return $this->hasMany(RegPeriksa::class, 'kd_dokter', 'kd_dokter');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'kd_dokter', 'nik');
    }

    function booking_operasi()
    {
        return $this->hasMany(BookingOperasi::class, 'kd_dokter', 'kd_dokter');
    }

    public function spesialis()
    {
        return $this->belongsTo(Spesialis::class, 'kd_sps', 'kd_sps');
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class, 'kd_dokter', 'kd_dokter');
    }

    // penilaian_medis_ranap
    public function penilaianMedisRanap()
    {
        return $this->hasMany(PenilaianMedisRanap::class, 'kd_dokter', 'kd_dokter');
    }

    // penilaian_medis_ranap_kandungan
    public function penilaianMedisRanapKandungan()
    {
        return $this->hasMany(PenilaianMedisRanapKandungan::class, 'kd_dokter', 'kd_dokter');
    }


    // --------------- 

    // jumlah penilaian_medis_ranap hasManyThrough 
    public function jPenilaianMedisRanap()
    {
        return $this->hasManyThrough(
            PenilaianMedisRanap::class,
            RegPeriksa::class,
            'kd_dokter',
            'no_rawat',
            'kd_dokter',
            'no_rawat'
        );
    }

    // jumlah penilaian_medis_ranap_kandungan hasManyThrough
    public function jPenilaianMedisRanapKandungan()
    {
        return $this->hasManyThrough(
            PenilaianMedisRanapKandungan::class,
            RegPeriksa::class,
            'kd_dokter',
            'no_rawat',
            'kd_dokter',
            'no_rawat'
        );
    }

    // jumlah pemeriksaan_ranap
    public function jPemeriksaanRanap()
    {
        return $this->hasManyThrough(
            PemeriksaanRanap::class,
            RegPeriksa::class,
            'kd_dokter',
            'no_rawat',
            'kd_dokter',
            'no_rawat'
        );
    }

    // jumlah veridikasi_pemeriksaan_ranap
    public function jVerifikasiPemeriksaanRanap()
    {
        return $this->hasManyThrough(
            RsiaVerifPemeriksaanRanap::class,
            RegPeriksa::class,
            'kd_dokter',
            'no_rawat',
            'kd_dokter',
            'no_rawat'
        );
    }

    // jumlah resume pasien ranap
    public function jResumePasienRanap()
    {
        return $this->hasManyThrough(
            ResumePasienRanap::class,
            RegPeriksa::class,
            'kd_dokter',
            'no_rawat',
            'kd_dokter',
            'no_rawat'
        );
    }

    // jumlah verifikasi resume pasien ranap
    public function jVerifikasiResumePasienRanap()
    {
        return $this->hasManyThrough(
            RsiaVerifResumeRanap::class,
            RegPeriksa::class,
            'kd_dokter',
            'no_rawat',
            'kd_dokter',
            'no_rawat'
        );
    }
}