<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provinsi;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Desa;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function provinsi(Request $request)
    {
        $search = trim($request->get('search', ''));

        return Provinsi::query()
            ->when($search, function ($query) use ($search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->limit(50)
            ->get([
                'id',
                'kode',
                'nama',
            ]);
    }

    public function kabupaten(Request $request)
    {
        $search = trim($request->get('search', ''));
        $provinsiId = $request->get('provinsi_id');

        return Kabupaten::query()
            ->where('provinsi_id', $provinsiId)
            ->when($search, function ($query) use ($search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->limit(50)
            ->get([
                'id',
                'provinsi_id',
                'kode',
                'nama',
            ]);
    }

    public function kecamatan(Request $request)
    {
        $search = trim($request->get('search', ''));
        $kabupatenId = $request->get('kabupaten_id');

        return Kecamatan::query()
            ->where('kabupaten_id', $kabupatenId)
            ->when($search, function ($query) use ($search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->limit(50)
            ->get([
                'id',
                'kabupaten_id',
                'kode',
                'nama',
            ]);
    }

    public function desa(Request $request)
    {
        $search = trim($request->get('search', ''));
        $kecamatanId = $request->get('kecamatan_id');

        return Desa::query()
            ->where('kecamatan_id', $kecamatanId)
            ->when($search, function ($query) use ($search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->limit(50)
            ->get([
                'id',
                'kecamatan_id',
                'kode',
                'nama',
            ]);
    }
}
