<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BastProject;
use App\Models\PoleDetail;
use Illuminate\Support\Facades\Auth;

class BastProjectController extends Controller
{

    public function index(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Ambil parameter filter (semua opsional)
        $provinceName = $request->query('province_name');
        $regencyName  = $request->query('regency_name');
        $villageName  = $request->query('village_name');
        $projectName  = $request->query('project_name');

        // Query dasar
        $query = BastProject::query();

        // Filter dinamis berdasarkan input
        if ($provinceName) {
            $query->where('province_name', 'like', "%{$provinceName}%");
        }
        if ($regencyName) {
            $query->where('regency_name', 'like', "%{$regencyName}%");
        }
        if ($villageName) {
            $query->where('village_name', 'like', "%{$villageName}%");
        }
        if ($projectName) {
            $query->where('project_name', 'like', "%{$projectName}%");
        }

        // Urutkan berdasarkan waktu terbaru
        $basts = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'total' => $basts->count(),
            'data' => $basts,
        ]);
    }

    public function create(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'site' => 'nullable|string|max:255',
            'PIC' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
         $bastId = "HP".date('YmdH') . rand(1000, 9999);

        $bastId        = $bastId;
        $projectName   = $validated['project_name'];
        $site          = $validated['site'] ?? null;
        $pic           = $validated['PIC'] ?? null;
        $technici      = $validated['email'] ?? null;
        $email         = $validated['email'] ?? null;
        $notes         = $validated['notes'] ?? null;
        $createdBy     = $email ?? '';
        $updatedBy     = '';


        $bast = BastProject::create([
            'bast_id'             => $bastId,
            'project_name'        => $projectName,
            'site'                => $site,
            'PIC'                 => $pic,
            'technici'            => $technici,
            'status'              => 'not started',
            'progress_percentage' => 0,
            'notes'               => $notes,
            'bast_date'           => now()->format('Y-m-d'),
            'created_by'          => $createdBy,
            'updated_by'          => $updatedBy,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'BAST project created successfully',
            'data' => $bast,
        ], 201);
        
    }

    public function updatepole(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'bast_id' => 'required|string|exists:mysql_inventory.BastProject,bast_id',
            'pole_sn' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
        ]);

        $bastId = $validated['bast_id'];
        $poleSn = $validated['pole_sn'] ?? null;

        $query = PoleDetail::where('bast_id', $bastId);
        if ($poleSn) {
            $query->where('pole_sn', $poleSn);
        }
        $poleDetail = $query->first();

         

        if (! $poleDetail) {
            $poleDetail = new PoleDetail();
            $poleDetail->bast_id = $bastId;
            if ($poleSn) {
                $poleDetail->pole_sn = $poleSn;
            }
            $poleDetail->created_by = $user->email;
        }

        

        // Daftar kolom foto
        $photoFields = [
            'digging',
            'instalasi',
            'coran',
            'tiang_berdiri',
            'labeling_tiang',
            'aksesoris_tiang',
        ];

        foreach ($photoFields as $field) {
            $filePhoto = $request->input($field);

            if (!empty($filePhoto)) {
                $filePhoto = "data:image/png;base64," . $filePhoto;
                if (preg_match('/^data:image\/(\w+);base64,/', $filePhoto, $type)) {
                    $filePhoto = substr($filePhoto, strpos($filePhoto, ',') + 1);
                    $type = strtolower($type[1]);
                    $decoded = base64_decode($filePhoto);

                    if ($decoded === false) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Invalid base64 format for {$field}",
                        ], 400);
                    }

                    // $fileName = $field . '_' . time() . '.' . $type;
                    // Storage::disk('public')->put($path, $decoded);
                    $fileName = $field . '_' . time() . '.' . $type;
                    $path = 'poles/' . $fileName;
                    // $folder = public_path('storage/poles');

                    // ✅ fix: gunakan $poleDetail, bukan $pole
                    $poleDetail->$field = $path;
                } else {
                    // Kalau bukan base64 (misal path lama)
                    $poleDetail->$field = $filePhoto;
                }
            }
        }

        // Update lokasi jika dikirim
        if ($request->filled('latitude')) {
            $poleDetail->latitude = $validated['latitude'] ?? 0;
        }
        // else{
        //     $pole->latitude = 0;
        // }

        if ($request->filled('longitude')) {
            $poleDetail->longitude = $validated['longitude']  ?? 0;
        }
        // else{
            
        //     $pole->longitude = 0;
        // }

        $poleDetail->updated_by = $user->email ?? null;
        $poleDetail->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Pole data updated successfully',
            'data' => $poleDetail,
        ]);
        
    }
}
