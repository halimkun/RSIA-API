<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @group Kunjungan Dokter
 * */
class KunjunganController extends Controller
{
    public function index()
    {
        $payload = auth()->payload();
        $kd_dokter = $payload->get('sub');
        $kunjungan = \App\Models\RegPeriksa::where('kd_dokter', $kd_dokter)
            ->orderBy('tgl_registrasi', 'desc')
            ->orderBy('jam_reg', 'desc')
            ->paginate(env('PER_PAGE', 20));

        return isSuccess($kunjungan, 'Data berhasil dimuat');
    }

    public function now()
    {
        $payload = auth()->payload();
        $kd_dokter = $payload->get('sub');
        $kunjungan = \App\Models\RegPeriksa::where('kd_dokter', $kd_dokter)
            ->where('tgl_registrasi', date('Y-m-d'))
            ->paginate(env('PER_PAGE', 20));

        return isSuccess($kunjungan, 'Data berhasil dimuat');
    }

    private function getTotal($source)
    {
        $dataMap = ["UMUM", "BPJS", "TOTAL"];
        $data    = $source->pluck('penjab')->countBy(
            function ($item, $key) {
                return str_contains($item['png_jawab'], "BPJS") ? 'BPJS' : $item['png_jawab'];
            }
        );

        foreach ($dataMap as $key => $value) {
            if (!isset($data[$value])) {
                $data[$value] = 0;
            }

            if ($value == "TOTAL") {
                $data[$value] = $data['UMUM'] + $data['BPJS'];
            }
        }

        return $data;
    }

    function rekap(Request $request)
    {
        $payload = auth()->payload();

        if (!$request->isMethod('post')) {
            return isFail('Method not allowed');
        }

        $pasien = \App\Models\RegPeriksa::with(['pasien', 'penjab'])
            ->where('kd_dokter', $payload->get('sub'))
            ->orderBy('tgl_registrasi', 'DESC')
            ->orderBy('jam_reg', 'DESC');

        $operasi = \App\Models\RegPeriksa::with(['pasien', 'penjab'])
            ->where('kd_dokter', $payload->get('sub'))
            ->whereHas('operasi')
            ->orderBy('tgl_registrasi', 'DESC')
            ->orderBy('jam_reg', 'DESC');

        $start = date('Y-m-01');
        $end   = date('Y-m-t');

        if ($request->tgl_registrasi) {
            $start = \Illuminate\Support\Carbon::parse($request->tgl_registrasi['start'])->format('Y-m-d');
            $end   = \Illuminate\Support\Carbon::parse($request->tgl_registrasi['end'])->format('Y-m-d');
        }

        $pasien->whereBetween('tgl_registrasi', [$start, $end]);
        $operasi->whereBetween('tgl_registrasi', [$start, $end]);

        $data            = $pasien->get()->groupBy('status_lanjut')->map(function ($item, $key) {
            return $this->getTotal(collect($item));
        });
        $data['Operasi'] = $this->getTotal($operasi->get());

        return isSuccess($this->checkData($data), 'Data berhasil dimuat');
    }
    
    function rekapUmum(Request $request)
    {
        $payload = auth()->payload();

        if (!$request->isMethod('post')) {
            return isFail('Method not allowed');
        }

        if ($request->tgl_registrasi) {
            $start = \Illuminate\Support\Carbon::parse($request->tgl_registrasi['start'])->format('Y-m-d');
            $end   = \Illuminate\Support\Carbon::parse($request->tgl_registrasi['end'])->format('Y-m-d');
        } else {
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
        }

        $pasien = \App\Models\RegPeriksa::with(['pasien', 'penjab'])
            ->whereHas('ranapDokter', function ($query) use ($start, $end, $payload) {
                $query->where('kd_dokter', $payload->get('sub'))->whereBetween('tgl_perawatan', [$start, $end]);
            })
            ->orWhereHas('ralanDokter', function ($query) use ($start, $end, $payload) {
                $query->where('kd_dokter', $payload->get('sub'))->whereBetween('tgl_perawatan', [$start, $end]);
            })
            ->orWhereHas('ranapGabungan', function ($query) use ($start, $end, $payload) {
                $query->where('kd_dokter', $payload->get('sub'))->whereBetween('tgl_perawatan', [$start, $end]);
            })
            ->orWhereHas('ralanGabungan', function ($query) use ($start, $end, $payload) {
                $query->where('kd_dokter', $payload->get('sub'))->whereBetween('tgl_perawatan', [$start, $end]);
            })
            ->orderBy('tgl_registrasi', 'DESC')
            ->orderBy('jam_reg', 'DESC');

        $data = $pasien->get()->groupBy('status_lanjut')->map(function ($item, $key) {
            return $this->getTotal(collect($item));
        });

        return isSuccess($this->checkData($data), "Data rekap pasen dokter umum pada $start - $end berhasil dimuat");
    }

    function rekapRadiologi(Request $request)
    {
        $kd_dokter = $request->payload->get('sub');
        $pasien = \App\Models\PeriksaRadiologi::where('kd_dokter', $kd_dokter);

        if ($request->tgl) {
            $start = \Illuminate\Support\Carbon::parse($request->tgl['start'])->format('Y-m-d');
            $end   = \Illuminate\Support\Carbon::parse($request->tgl['end'])->format('Y-m-d');
        } else {
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
        }

        $pasien->whereBetween('tgl_periksa', [$start, $end])->whereHas('hasil', function ($query) {
            $query->where('hasil', '!=', '')->where('hasil', '!=', ' ')->where('hasil', '!=', '-')->where('hasil', '!=', '0');
        })->with(['regPeriksa' => function($q) {
            $q->select('no_rawat', 'kd_pj')->with(['penjab' => function ($qq) {
                $qq->select('kd_pj', 'png_jawab');
            }]);
        }])->orderBy('tgl_periksa', 'DESC')->orderBy('jam', 'DESC');

        $data = $pasien->get()->groupBy('status')->map(function ($item, $key) {
            return $this->getTotal(collect($item)->pluck('regPeriksa'));
        });

        return isSuccess($data, "Data rekap pasen dokter radiologi pada $start - $end berhasil dimuat");
    }

    function byDate($tahun = null, $bulan = null, $tanggal = null)
    {
        $payload = auth()->payload();
        
        $kunjungan = \App\Models\RegPeriksa::where('kd_dokter', $payload->get('sub'));
        if ($tahun !== null) {
            $kunjungan->whereYear('tgl_registrasi', $tahun);
        }

        if ($tahun !== null && $bulan !== null) {
            $kunjungan->whereYear('tgl_registrasi', $tahun)->whereMonth('tgl_registrasi', $bulan);
        }

        if ($tahun !== null && $bulan !== null && $tanggal !== null) {
            $fullDate = $tahun . '-' . $bulan . '-' . $tanggal;
            $kunjungan->where('tgl_registrasi', $fullDate);
        }

        $kunjungan = $kunjungan->orderBy('tgl_registrasi', 'desc')
            ->orderBy('jam_reg', 'desc')->paginate(env('PER_PAGE', 20));

        return isSuccess($kunjungan, 'Data berhasil dimuat');
    }

    private function checkData($data, $isUmum = false)
    {
        $dataVal = ["UMUM", "BPJS", "TOTAL"];
        $dataKey = ["Ranap", "Ralan", "Operasi"];

        // jika dataKey didalam data tidak adan maka tambahkan dataKey dengan isi dataVal
        foreach ($dataKey as $key => $value) {
            if (!isset($data[$value])) {
                $data[$value] = array_fill_keys($dataVal, 0);
            }
        }

        $data = collect($data)->sortBy(function ($item, $key) use ($dataKey) {
            return array_search($key, $dataKey);
        })->toArray();

        return $data;
    }
}