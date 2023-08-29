<?php

namespace App\Http\Controllers;

use App\Http\Requests\JurusanCreateRequest;
use App\Http\Requests\JurusanReadRequest;
use App\Http\Requests\JurusanUpdateRequest;
use App\Http\Resources\JurusanResource;
use App\Models\JurusanModel;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JurusanController extends Controller
{
    public function get(JurusanReadRequest $request): JsonResponse
    {
        // Validasi data
        $data = $request->validated();

        $query = JurusanModel::select(
            'id_jurusan',
            'nama_jurusan',
            'singkatan',
            'status_data');

        $totalRecords = JurusanModel::count();

        if (!empty($data['id_jurusan'])) {
            $query = $query->where('id_jurusan', $data['id_jurusan']);
        }

        // Pembatasan data jurusan yang akan dikirim
        if (!empty($data['start']) && !empty($data['length'])) {
            $query = $query->offset($data['start'])
                ->limit($data['length']);
        }

        // Penyortiran (Ordering) berdasarkan kolom yang dipilih
        if (!empty($data['order'])) {
            $query = $query->orderBy($data['order']);
        }

        // Pencarian berdasarkan nama$orderBy, $orderByDir_jurusan
        if (!empty($data['search'])) {
            $query = $query->where('nama_jurusan', 'LIKE', '%' . $data['search'] . '%');
        }

        $filteredRecords = $query->count();
        $jurusan = $query->get();
        
        $response = [
            'draw' => intval($request->input('draw')), // Pastikan draw disertakan
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => JurusanResource::collection($jurusan),
        ];
        
        return response()->json($response)->setStatusCode(200);
    }

    public function create(JurusanCreateRequest $request): JsonResponse
    {
        // Validasi data
        $data = $request->validated();

        // Check apakah data jurusan sudah digunakan
        $existingJurusan = JurusanModel::where('nama_jurusan', $data['nama_jurusan'])->first();
        if ($existingJurusan) {
            return response()->json([
                'errors' => [
                    'message' => [
                        'Error nama Jurusan sudah pernah digunakan!'
                    ]
                ]
            ], 409);
        }

        // Check apakah data Jurusan sudah ada di sampah
        $deletedJurusan = JurusanModel::onlyTrashed()->where('nama_jurusan', $data['nama_jurusan'])->first();
        if ($deletedJurusan) {
            return (new JurusanResource([
                'errors' => [
                    'message' => [
                        'Data dengan nama jurusan serupa sudah ada di tempat sampah! Pulihkan?'
                    ]
                ],
                'id_jurusan' => $deletedJurusan->id_jurusan,
            ]))->response()->setStatusCode(201);
        }

        // Membuat id secara otomatis
        $banyakData = JurusanModel::withTrashed()->count();
        $data['id_jurusan'] = "J-" . str_pad(($banyakData + 1), 3, '0', STR_PAD_LEFT);

        // Insert data ke tabel
        $jurusan = new JurusanModel($data);
        $jurusan->save();

        // Jika status data tidak aktif, set deleted_at agar tidak null (soft delete)
        if ($jurusan->status_data == "Tidak Aktif") {
            (JurusanModel::find($data['id_jurusan']))
                ->delete();
        }

        return (new JurusanResource([
            'success' => [
                'message' => [
                    "Jurusan $jurusan->nama_jurusan berhasil ditambahkan"
                ]
            ]
        ]))->response()->setStatusCode(201);
    }

    public function update (JurusanUpdateRequest $request): JsonResponse
    {
        // Periksa validasi data
        $data = $request->validated();

        // Ambil data jurusan serupa
        $jurusan = JurusanModel::where('id_jurusan', $data['id_jurusan'])->first();

        // Memeriksa apakah nama jurusan sudah pernah digunakan
        $existingJurusan = JurusanModel::withTrashed()
            ->where('nama_jurusan', $data['nama_jurusan'])
            ->exists();

        // Jika sudah, kembalikan respons error
        if ($existingJurusan && $jurusan['nama_jurusan'] != $data['nama_jurusan']) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'Nama jurusan sudah digunakan'
                    ]
                ]
            ])->setStatusCode(400));
        }

        // Update data jurusan
        $jurusan->update($data);

        // Jika status data tidak aktif, set deleted_at agar tidak null (soft delete)
        if ($jurusan->status_data == "Tidak Aktif") {
            (JurusanModel::find($data['id_jurusan']))
                ->delete();
        }

        return (new JurusanResource([
            'success' => [
                'message' => [
                    "Jurusan $jurusan->nama_jurusan berhasil diubah"
                ]
            ]
        ]))->response()->setStatusCode(201);
    }

    public function delete(JurusanReadRequest $request): JsonResponse
    {
        $data = $request->validated();

        $jurusan = JurusanModel::where('id_jurusan', $data['id_jurusan'])->first();
        if (!$jurusan) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'Data jurusan tidak dapat ditemukan'
                    ]
                ]
            ])->setStatusCode(404));
        }

        $jurusan->update(['status_data' => 'Tidak Aktif']);
        $jurusan->delete(); // Perform soft delete

        return (new JurusanResource([
            'success' => [
                'message' => [
                    "Jurusan $jurusan->nama_jurusan berhasil dihapus"
                ]
            ]
        ]))->response()->setStatusCode(201);
    }

    public function restore(JurusanReadRequest $request): JsonResponse
    {
        $data = $request->validated();
        $jurusan = JurusanModel::onlyTrashed()
            ->where('id_jurusan', $data['id_jurusan'])
            ->first(); // Ambil data yang sudah dihapus

        if (!$jurusan) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => [
                        'Data jurusan tidak dapat ditemukan'
                    ]
                ]
            ])->setStatusCode(404));
        }    

        $jurusan->update(['status_data' => 'Aktif']);
        $jurusan->restore(); // Memulihkan data
        
        return (new JurusanResource([
            'success' => [
                'message' => [
                    "Jurusan $jurusan->nama_jurusan berhasil dipulihkan"
                ]
            ]
        ]))->response()->setStatusCode(201);
    }
}
