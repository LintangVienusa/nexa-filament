<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BastProject;
use App\Models\PoleDetail;
use App\Models\ODPDetail;
use App\Models\ODCDetail;
use App\Models\FeederDetail;
use App\Models\RBSDetail;
use App\Models\HomeConnect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        // $validated = $request->validate([
        //     'village_name' => 'required|string',
            
        // ]);


        // $village_name  = $validated['village_name'];s

        $basts = BastProject::where('status', '!=', 'completed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('village_name'); 

        $data = $basts->map(function ($items, $village_name) {
            return [
                'village_name' => $village_name,
                'total_bast' => $items->count(),
                'details' => $items->map(function ($item) {
                    return [
                        'bast_id' => $item->bast_id,
                        'province_name' => $item->province_name,
                        'regency_name' => $item->regency_name,
                        'village_name' => $item->village_name,
                        'project_name' => $item->project_name,
                        'notes' => $item->notes,
                        'pass' => $item->pass,
                        'PIC' => $item->pic,
                        'status' => $item->status,
                        'progress_percentage' => $item->progress_percentage,
                        'presentase_tian' => 0,
                        'presentase_rbs' => 0,
                        'presentase_odc' => 0,
                        'presentase_odp' => 0,
                        'presentase_feeder' => 0,
                        'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    public function listsite(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $query = BastProject::select('village_name', DB::raw('COUNT(bast_id) as total_bast'))
                ->where('status', '!=', 'completed')
                ->groupBy('village_name')
                ->orderBy('village_name', 'asc')
                ->get();

        
        // $basts = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'total' => $query->count(),
            'data' => $query,
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

    public function listpole(Request $request)
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
        ]);

        $bastId = $validated['bast_id'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = PoleDetail::where('bast_id', $bastId)->pluck('pole_sn')
                ->toArray();
        
        

        return response()->json([
            'status' => 'success',
            'message' => 'List Pole',
            'list_pole' => $query,
        ]);
        
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

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

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
                    $folder = public_path('storage/poles');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    $tempImage = imagecreatefromstring($decoded);
                    if ($tempImage === false) {
                        return response()->json(['error' => 'Failed to create image from data'], 400);
                    }

                    $width = imagesx($tempImage);
                    $height = imagesy($tempImage);

                    $maxWidth = 800;
                    $maxHeight = 800;
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                    imagedestroy($tempImage);
                    imagedestroy($compressedImage);


                    
                    $poleDetail->$field = $path;
                } else {
                    $poleDetail->$field = $filePhoto;
                }
            }
        }

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

        if ($bast) {
            $bast->info_pole = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pole data updated successfully',
            'data' => $poleDetail,
        ]);
        
    }

    public function detailpole(Request $request)
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
            
        ]);

        $bastId = $validated['bast_id'];
        $poleSn = $validated['pole_sn'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = PoleDetail::where('bast_id', $bastId);
        if ($poleSn) {
             $query->where('pole_sn', $poleSn);

            $record = $query->first();

            $fileKeys = [
                'digging',
                'instalasi',
                'coran',
                'tiang_berdiri',
                'labeling_tiang',
                'aksesoris_tiang',
            ];

            $base64Files = [];

            foreach ($fileKeys as $key) {
                $file = $record->$key ?? null;

                if (!empty($file)) {
                    $filePath = storage_path('app/public/' . $file);
                    $base64Files[$key] = file_exists($filePath)
                        ? 'data:image/' . pathinfo($filePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($filePath))
                        : null;
                } else {
                    $base64Files[$key] = null;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $base64Files,
            ]);
        }else{
            $poleDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }

    public function listodp(Request $request)
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
        ]);

        $bastId = $validated['bast_id'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = ODPDetail::where('bast_id', $bastId)->pluck('odp_name')
                ->toArray();
        
        

        return response()->json([
            'status' => 'success',
            'message' => 'List ODP',
            'list_odp' => $query,
        ]);
        
    }

    public function updateodp(Request $request)
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
            'odp_name' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
        ]);

        $bastId = $validated['bast_id'];
        $odp_name = $validated['odp_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = ODPDetail::where('bast_id', $bastId);
        if ($odp_name) {
            $query->where('odp_name', $odp_name);
        }
        $odpDetail = $query->first();

         

        if (! $odpDetail) {
            $odpDetail = new ODPDetail();
            $odpDetail->bast_id = $bastId;
            if ($odp_name) {
                $odpDetail->odp_name = $odp_name;
            }
            $odpDetail->created_by = $user->email;
        }

    
        $photoFields = [
            'instalasi',
            'odp_terbuka',
            'odp_tertutup',
            'hasil_ukur_opm',
            'labeling_odp',
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
                    $path = 'odp/' . $fileName;
                    $folder = public_path('storage/odp');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    $tempImage = imagecreatefromstring($decoded);
                    if ($tempImage === false) {
                        return response()->json(['error' => 'Failed to create image from data'], 400);
                    }

                    $width = imagesx($tempImage);
                    $height = imagesy($tempImage);

                    $maxWidth = 800;
                    $maxHeight = 800;
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                    imagedestroy($tempImage);
                    imagedestroy($compressedImage);


                    
                    $odpDetail->$field = $path;
                } else {
                    $odpDetail->$field = $filePhoto;
                }
            }
        }

        if ($request->filled('latitude')) {
            $odpDetail->latitude = $validated['latitude'] ?? 0;
        }
        // else{
        //     $pole->latitude = 0;
        // }

        if ($request->filled('longitude')) {
            $odpDetail->longitude = $validated['longitude']  ?? 0;
        }
        // else{
            
        //     $pole->longitude = 0;
        // }

        $odpDetail->updated_by = $user->email ?? null;
        $odpDetail->save();

        if ($bast) {
            $bast->info_odp = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ODP data updated successfully',
            'data' => $odpDetail,
        ]);
        
    }

    public function detailodp(Request $request)
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
            'odp_name' => 'nullable|string',
            
        ]);

        $bastId = $validated['bast_id'];
        $odp_name = $validated['odp_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = ODPDetail::where('bast_id', $bastId);
        if ($odp_name) {
             $query->where('odp_name', $odp_name);

            $record = $query->first();

            $fileKeys = [
                'instalasi',
                'odp_terbuka',
                'odp_tertutup',
                'hasil_ukur_opm',
                'labeling_odp',
            ];

            $base64Files = [];

            foreach ($fileKeys as $key) {
                $file = $record->$key ?? null;

                if (!empty($file)) {
                    $filePath = storage_path('app/public/' . $file);
                    $base64Files[$key] = file_exists($filePath)
                        ? 'data:image/' . pathinfo($filePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($filePath))
                        : null;
                } else {
                    $base64Files[$key] = null;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $base64Files,
            ]);
        }else{
            $poleDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }


    public function listodc(Request $request)
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
        ]);

        $bastId = $validated['bast_id'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = ODCDetail::where('bast_id', $bastId)->pluck('odc_name')
                ->toArray();
        
        

        return response()->json([
            'status' => 'success',
            'message' => 'List ODC',
            'list_odc' => $query,
        ]);
        
    }

    public function detailodc(Request $request)
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
            'odc_name' => 'nullable|string',
            
        ]);

        $bastId = $validated['bast_id'];
        $odc_name = $validated['odc_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = ODCDetail::where('bast_id', $bastId);
        if ($odc_name) {
             $query->where('odc_name', $odc_name);

            $record = $query->first();

            $fileKeys = [
                'instalasi',
                'odc_terbuka',
                'odc_tertutup',
                'hasil_ukur_opm',
                'labeling_odc',
            ];

            $base64Files = [];

            foreach ($fileKeys as $key) {
                $file = $record->$key ?? null;

                if (!empty($file)) {
                    $filePath = storage_path('app/public/' . $file);
                    $base64Files[$key] = file_exists($filePath)
                        ? 'data:image/' . pathinfo($filePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($filePath))
                        : null;
                } else {
                    $base64Files[$key] = null;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $base64Files,
            ]);
        }else{
            $poleDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }

    public function updateodc(Request $request)
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
            'odc_name' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
        ]);

        $bastId = $validated['bast_id'];
        $odc_name = $validated['odc_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = ODCDetail::where('bast_id', $bastId);
        if ($odc_name) {
            $query->where('odc_name', $odc_name);
        }
        $odcDetail = $query->first();

         

        if (! $odcDetail) {
            $odcDetail = new ODCDetail();
            $odcDetail->bast_id = $bastId;
            if ($odp_name) {
                $odcDetail->odc_name = $odc_name;
            }
            $odcDetail->created_by = $user->email;
        }

    
        $photoFields = [
            'instalasi',
            'odc_terbuka',
            'odc_tertutup',
            'hasil_ukur_opm',
            'labeling_odc',
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
                    $path = 'odc/' . $fileName;
                    $folder = public_path('storage/odc');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    $tempImage = imagecreatefromstring($decoded);
                    if ($tempImage === false) {
                        return response()->json(['error' => 'Failed to create image from data'], 400);
                    }

                    $width = imagesx($tempImage);
                    $height = imagesy($tempImage);

                    $maxWidth = 800;
                    $maxHeight = 800;
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                    imagedestroy($tempImage);
                    imagedestroy($compressedImage);


                    
                    $odcDetail->$field = $path;
                } else {
                    $odcDetail->$field = $filePhoto;
                }
            }
        }

        if ($request->filled('latitude')) {
            $odcDetail->latitude = $validated['latitude'] ?? 0;
        }
        // else{
        //     $pole->latitude = 0;
        // }

        if ($request->filled('longitude')) {
            $odcDetail->longitude = $validated['longitude']  ?? 0;
        }
        // else{
            
        //     $pole->longitude = 0;
        // }

        $odcDetail->updated_by = $user->email ?? null;
        $odcDetail->save();

        if ($bast) {
            $bast->info_odc = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ODC data updated successfully',
            'data' => $odcDetail,
        ]);
        
    }

    public function detailfeeder(Request $request)
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
            'feeder_name' => 'nullable|string',
            
        ]);

        $bastId = $validated['bast_id'];
        $feeder_name = $validated['feeder_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = FeederDetail::where('bast_id', $bastId);
        if ($feeder_name) {
             $query->where('feeder_name', $feeder_name);

            $record = $query->first();

            $fileKeys = [
                'foto_utara',
                'foto_barat',
                'foto_selatan',
                'foto_timur',
            ];

            $base64Files = [];

            foreach ($fileKeys as $key) {
                $file = $record->$key ?? null;

                if (!empty($file)) {
                    $filePath = storage_path('app/public/' . $file);
                    $base64Files[$key] = file_exists($filePath)
                        ? 'data:image/' . pathinfo($filePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($filePath))
                        : null;
                } else {
                    $base64Files[$key] = null;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $base64Files,
            ]);
        }else{
            $poleDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }

    public function listfeeder(Request $request)
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
        ]);

        $bastId = $validated['bast_id'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = FeederDetail::where('bast_id', $bastId)
                ->pluck('feeder_name')
                ->toArray();

        // $query = ODPDetail::where('bast_id', $bastId)
        //         ->selectRaw("CONCAT(odp_name, ' - ONT') as full_name")
        //         ->pluck('full_name')
        //         ->toArray();

        return response()->json([
            'status' => 'success',
            'message' => 'List Feeder',
            'list_feeder' => $query
        ]);
        
    }

    public function updatefeeder(Request $request)
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
            'feeder_name' => 'nullable|string',
            
        ]);

        $bastId = $validated['bast_id'];
        $feeder_name = $validated['feeder_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = FeederDetail::where('bast_id', $bastId);
        if ($feeder_name) {
            $query->where('feeder_name', $feeder_name);
        }
        $feederDetail = $query->first();

         

        if (! $feederDetail) {
            $feederDetail = new FeederDetail();
            $feederDetail->bast_id = $bastId;
            if ($feeder_name) {
                $feederDetail->feeder_name = $feeder_name;
            }
            $feederDetail->created_by = $user->email;
        }

    
        $photoFields = [
            'foto_utara',
            'foto_barat',
            'foto_selatan',
            'foto_timur',
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
                    $path = 'feeder/' . $fileName;
                    $folder = public_path('storage/feeder');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    $tempImage = imagecreatefromstring($decoded);
                    if ($tempImage === false) {
                        return response()->json(['error' => 'Failed to create image from data'], 400);
                    }

                    $width = imagesx($tempImage);
                    $height = imagesy($tempImage);

                    $maxWidth = 800;
                    $maxHeight = 800;
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                    imagedestroy($tempImage);
                    imagedestroy($compressedImage);


                    
                    $feederDetail->$field = $path;
                } else {
                    $feederDetail->$field = $filePhoto;
                }
            }
        }

        if ($request->filled('latitude')) {
            $feederDetail->latitude = $validated['latitude'] ?? 0;
        }
        // else{
        //     $pole->latitude = 0;
        // }

        if ($request->filled('longitude')) {
            $feederDetail->longitude = $validated['longitude']  ?? 0;
        }
        // else{
            
        //     $pole->longitude = 0;
        // }

        $feederDetail->updated_by = $user->email ?? null;
        $feederDetail->save();

        if ($bast) {
            $bast->info_feeder = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Feeder data updated successfully',
            'data' => $feederDetail,
        ]);
        
    }

    public function listrbs(Request $request)
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
        ]);

        $bastId = $validated['bast_id'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $queryodc = ODCDetail::where('bast_id', $bastId)
                ->selectRaw("CONCAT('OLT - ', odc_name) as full_name")
                ->pluck('full_name')
                ->toArray();
        $queryodp = ODPDetail::where('bast_id', $bastId)
                ->selectRaw("CONCAT(odc_name, ' - ', odp_name) as full_name")
                ->pluck('full_name')
                ->toArray();

        // $query = ODPDetail::where('bast_id', $bastId)
        //         ->selectRaw("CONCAT(odp_name, ' - ONT') as full_name")
        //         ->pluck('full_name')
        //         ->toArray();
        $listRbs = array_merge($queryodc, $queryodp);

        return response()->json([
            'status' => 'success',
            'message' => 'List RBS',
            'list_rbs' => $listRbs
        ]);
        
    }

    public function updaterbs(Request $request)
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
            'rbs_name' => 'nullable|string',
            
        ]);

        $bastId = $validated['bast_id'];
        $rbs_name = $validated['rbs_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = RBSDetail::where('bast_id', $bastId);
        if ($rbs_name) {
            $query->where('rbs_name', $rbs_name);
        }
        $RBSDetail = $query->first();

         

        if (! $RBSDetail) {
            $RBSDetail = new RBSDetail();
            $RBSDetail->bast_id = $bastId;
            if ($rbs_name) {
                $RBSDetail->rbs_name = $rbs_name;
            }
            $RBSDetail->created_by = $user->email;
        }

    
        $photoFields = [
            'hasil_ukur_otdr',
            'pengambungan_core',
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
                    $path = 'rbs/' . $fileName;
                    $folder = public_path('storage/rbs');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    $tempImage = imagecreatefromstring($decoded);
                    if ($tempImage === false) {
                        return response()->json(['error' => 'Failed to create image from data'], 400);
                    }

                    $width = imagesx($tempImage);
                    $height = imagesy($tempImage);

                    $maxWidth = 800;
                    $maxHeight = 800;
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                    imagedestroy($tempImage);
                    imagedestroy($compressedImage);


                    
                    $RBSDetail->$field = $path;
                } else {
                    $RBSDetail->$field = $filePhoto;
                }
            }
        }

        if ($request->filled('latitude')) {
            $RBSDetail->latitude = $validated['latitude'] ?? 0;
        }
        // else{
        //     $pole->latitude = 0;
        // }

        if ($request->filled('longitude')) {
            $RBSDetail->longitude = $validated['longitude']  ?? 0;
        }
        // else{
            
        //     $pole->longitude = 0;
        // }

        $RBSDetail->updated_by = $user->email ?? null;
        $RBSDetail->save();

        if ($bast) {
            $bast->info_rbs = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'RBS data updated successfully',
            'data' => $RBSDetail,
        ]);
        
    }

    public function detailrbs(Request $request)
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
            'rbs_name' => 'nullable|string',
            
        ]);

        $bastId = $validated['bast_id'];
        $rbs_name = $validated['rbs_name'] ?? null;

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = RBSDetail::where('bast_id', $bastId);
        if ($rbs_name) {
             $query->where('rbs_name', $rbs_name);

            $record = $query->first();

            $fileKeys = [
                'hasil_otdr',
                'penyambungan_core',
            ];

            $base64Files = [];

            foreach ($fileKeys as $key) {
                $file = $record->$key ?? null;

                if (!empty($file)) {
                    $filePath = storage_path('app/public/' . $file);
                    $base64Files[$key] = file_exists($filePath)
                        ? 'data:image/' . pathinfo($filePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($filePath))
                        : null;
                } else {
                    $base64Files[$key] = null;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $base64Files,
            ]);
        }else{
            $poleDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }

    public function updatehomeconnect(Request $request)
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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
        ]);

        $bastId = $validated['bast_id'];

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->where('pass', 'HOMECONNECT')->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = HomeConnect::where('bast_id', $bastId);
        $HomeConnect = $query->first();

         

        if (! $HomeConnect) {
            $HomeConnect = new HomeConnect();
            $HomeConnect->bast_id = $bastId;
            $HomeConnect->created_by = $user->email;
        }

    
        $photoFields = [
            'foto_label_odp',
            'foto_hasil_ukur_odp',
            'foto_penarikan_outdoor',
            'foto_aksesoris_ikr',
            'foto_sn_ont',
            'foto_depan_rumah',
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
                    $path = 'homeconnect/' . $fileName;
                    $folder = public_path('storage/homeconnect');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    $tempImage = imagecreatefromstring($decoded);
                    if ($tempImage === false) {
                        return response()->json(['error' => 'Failed to create image from data'], 400);
                    }

                    $width = imagesx($tempImage);
                    $height = imagesy($tempImage);

                    $maxWidth = 800;
                    $maxHeight = 800;
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                    imagedestroy($tempImage);
                    imagedestroy($compressedImage);


                    
                    $HomeConnect->$field = $path;
                } else {
                    $HomeConnect->$field = $filePhoto;
                }
            }
        }

        if ($request->filled('latitude')) {
            $HomeConnect->latitude = $validated['latitude'] ?? 0;
        }
        // else{
        //     $pole->latitude = 0;
        // }

        if ($request->filled('longitude')) {
            $HomeConnect->longitude = $validated['longitude']  ?? 0;
        }
        // else{
            
        //     $pole->longitude = 0;
        // }

        $HomeConnect->updated_by = $user->email ?? null;
        $HomeConnect->save();

        if ($bast) {
            $bast->info_pole = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pole data updated successfully',
            'data' => $HomeConnect,
        ]);
        
    }

    public function detailhomeconnect(Request $request)
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
            
        ]);

        $bastId = $validated['bast_id'];

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = HomeConnect::where('bast_id', $bastId);
        if ($bastId) {
             
            $record = $query->first();

            $fileKeys = [
                'foto_label_odp',
                'foto_hasil_ukur_odp',
                'foto_penarikan_outdoor',
                'foto_aksesoris_ikr',
                'foto_sn_ont',
                'foto_depan_rumah',
            ];

            $base64Files = [];

            foreach ($fileKeys as $key) {
                $file = $record->$key ?? null;

                if (!empty($file)) {
                    $filePath = storage_path('app/public/' . $file);
                    $base64Files[$key] = file_exists($filePath)
                        ? 'data:image/' . pathinfo($filePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($filePath))
                        : null;
                } else {
                    $base64Files[$key] = null;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $base64Files,
            ]);
        }else{
            $poleDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }

    
}
