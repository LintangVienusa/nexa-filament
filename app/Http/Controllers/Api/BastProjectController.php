<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BastProject;
use App\Models\CableDetail;
use App\Models\PoleDetail;
use App\Models\ODPDetail;
use App\Models\ODCDetail;
use App\Models\FeederDetail;
use App\Models\RBSDetail;
use App\Models\HomeConnect;
use App\Models\MappingHomepass;
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

        $validated = $request->validate([
            'pass' => 'required|string',
            'station_name' => 'nullable|string',
            
        ]);


        $station_name  = $validated['station_name'];
        $pass  = $validated['pass'];
        if($pass != '' && $station_name != ''){
            $basts = BastProject::where('status', '!=', 'completed')
            ->where('pass', $pass)
            ->where('station_name', $station_name)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('station_name'); 
        }else{
            $basts = BastProject::where('status', '!=', 'completed')
            ->where('pass', $pass)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('station_name'); 
        }

        

        $data = $basts->map(function ($items, $station_name) {
            return [
                'station_name' => $station_name,
                'total_bast' => $items->count(),
                'details' => $items->map(function ($item) {
                    return [
                        'bast_id' => $item->bast_id,
                        'province_name' => $item->province_name,
                        'regency_name' => $item->regency_name,
                        'village_name' => $item->village_name,
                        'project_name' => $item->project_name,
                        'station_name' => $item->station_name,
                        'notes' => $item->notes,
                        'pass' => $item->pass,
                        'PIC' => $item->pic,
                        'status' => $item->status,
                        'progress_percentage' => $item->progress_percentage,
                        // 'presentase_tian' => 0,
                        // 'presentase_rbs' => 0,
                        // 'presentase_odc' => 0,
                        // 'presentase_odp' => 0,
                        // 'presentase_feeder' => 0,
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

    #POLE
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
            'station_name' => 'required|string',
            'per_page'     => 'nullable|integer|min:1|max:200',
            'page'         => 'nullable|integer|min:1',
        ]);

        // $bastId = $validated['bast_id'];
        $station_name = $validated['station_name'];
        $perPage = $request->per_page ?? 20;

        $poles = PoleDetail::on('mysql_inventory')
            ->join('BastProject', 'PoleDetail.bast_id', '=', 'BastProject.bast_id')
            ->where('BastProject.station_name', $station_name)
            ->select('PoleDetail.bast_id','PoleDetail.pole_sn')
            ->distinct()
            ->paginate($perPage);

        if ($poles->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => "Tidak ada Pole untuk station {$station_name}",
            ], 404);
        }
        
        

        return response()->json([
            'status' => 'success',
            'message' => "List Pole — {$station_name}",
            'data' => $poles,
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
            'notes' => 'nullable|string',
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
        $p=0;
        $percentage =0;
        $poleDetail->progress_percentage = 0;

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

                    $p = $p+1;
                    
                    $poleDetail->$field = $path;
                } else {
                    
                    $p = $p+1;
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
            $p = $p+1;
            $poleDetail->longitude = $validated['longitude']  ?? 0;
        }
        if ($request->filled('notes')) {
            $poleDetail->notes = $validated['notes']  ?? '';
        }
        // else{
            
        //     $pole->longitude = 0;
        // }
        $percentage = ($p/7)*100;
        $poleDetail->progress_percentage = $percentage;

        $poleDetail->updated_by = $user->email ?? null;
        $poleDetail->save();

        if ($bast) {
            $bast->info_pole = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }
        
        $this->updateBastProgress($bastId);

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


    #CABLE
    public function listcable(Request $request)
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
            'bast_id' => 'required|string'
        ]);

        $bastId = $validated['bast_id'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = CableDetail::where('bast_id', $bastId)->pluck('pole_sn')
                ->toArray();
        
        

        return response()->json([
            'status' => 'success',
            'message' => 'List Cable Pole',
            'list_Cable_pole' => $query,
        ]);
        
    }


    public function updatecable(Request $request)
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
            'notes' => 'nullable|string',
            
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

        $query = CableDetail::where('bast_id', $bastId);
        if ($poleSn) {
            $query->where('pole_sn', $poleSn);
        }
        $CableDetail = $query->first();

         

        if (! $CableDetail) {
            $CableDetail = new CableDetail();
            $CableDetail->bast_id = $bastId;
            if ($poleSn) {
                $CableDetail->pole_sn = $poleSn;
            }
            $CableDetail->created_by = $user->email;
        }

    
        $photoFields = [
            'pulling_cable',
            'instalasi',
        ];
        $p=0;
        $percentage =0;
        $CableDetail->progress_percentage = 0;

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
                    $path = 'cable/' . $fileName;
                    $folder = public_path('storage/cable');

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

                    $p = $p+1;
                    
                    $CableDetail->$field = $path;
                } else {
                    
                    $p = $p+1;
                    $CableDetail->$field = $filePhoto;
                }
            }
        }

       
        if ($request->filled('notes')) {
            $CableDetail->notes = $validated['notes']  ?? '';
        }
        // else{
            
        //     $pole->longitude = 0;
        // }
        $percentage = ($p/2)*100;
        $CableDetail->progress_percentage = $percentage;

        $CableDetail->updated_by = $user->email ?? null;
        $CableDetail->save();

        if ($bast) {
            $bast->info_pole = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }
        
        $this->updateBastProgress($bastId);

        return response()->json([
            'status' => 'success',
            'message' => 'Cable data updated successfully',
            'data' => $CableDetail,
        ]);
        
    }

    public function detailcable(Request $request)
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

        $query = CableDetail::where('bast_id', $bastId);
        if ($poleSn) {
             $query->where('pole_sn', $poleSn);

            $record = $query->first();

            $fileKeys = [
                'pulling_cable',
                'instalasi',
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
            $CableDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }



    #ODP
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
            'bast_id' => 'required|string',
            'odc_name' => 'required|string',
        ]);

        $bastId = $validated['bast_id'];
        $odc_name = $validated['odc_name'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        $bast2 = BastProject::on('mysql_inventory')
            ->where('bast_id', $bastId)
            ->whereHas('ODCDetail', function($q) use ($odc_name) {
                $q->where('odc_name', $odc_name);
            })
            ->with(['ODCDetail' => function($q) use ($odc_name) {
                $q->where('odc_name', $odc_name);
            }])
            ->first();

        if (!$bast2) {
            return response()->json([
                'status' => 'error',
                'message' => "ODC dengan {$odc_name} tidak ditemukan",
            ], 404);
        }

        $query = ODPDetail::where('odc_name', $odc_name)->pluck('odp_name')
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
            'power_optic_odc',
        ];

        $po = 0;
        $percentage = 0;

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


                    $po = $po+1;
                    $odpDetail->$field = $path;
                } else {
                    $po = $po+1;
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
            $po = $po+1;
            $odpDetail->longitude = $validated['longitude']  ?? 0;
        }
        if ($request->filled('notes')) {
            $odpDetail->notes = $validated['notes']  ?? '';
        }
        // else{
            
        //     $pole->longitude = 0;
        // }
        $percentage = ($po/5)*100;
        $odpDetail->progress_percentage = $percentage;
        $odpDetail->updated_by = $user->email ?? null;
        $odpDetail->save();

        if ($bast) {
            $bast->info_odp = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }
        
        $this->updateBastProgress($bastId);

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
                'power_optic_odc',
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
            // 'pass' => 'required|string',
            'station_name' => 'required|string',
        ]);

        $station_name = $validated['station_name'];
        

        $bastList = BastProject::on('mysql_inventory')->where('station_name', $station_name)->get(['bast_id', 'site']);;

        // if (!$bast) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => "SITE di Stasiun {$station_name} tidak ditemukan",
        //     ], 404);
        // }

        if ($bastList->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => "Tidak ada BAST pada site {$station_name}",
            ], 404);
        }

        // $query = ODCDetail::where('bast_id', $bastId)->pluck('odc_name')
        //         ->toArray();
        $odcQuery = ODCDetail::query();

        foreach ($bastList as $bast) {
            $odcQuery->orWhere(function($q) use ($bast) {
                $q->where('bast_id', $bast->bast_id)
                ->where('site', $bast->site);
            });
        }

        $odcData = $odcQuery->get(['odc_name', 'bast_id', 'site']);

        // --- GROUP BY ODC NAME ---
        $grouped = $odcData->groupBy('odc_name')->map(function ($items, $odcName) {
            return [
                'odc_name' => $odcName,
                'detail' => $items->map(function ($item) {
                    return [
                        'bast_id' => $item->bast_id,
                        'site'    => $item->site
                    ];
                })->values()
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $grouped
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
                'power_optic_olt',
                'odc_terbuka',
                'odc_tertutup',
                'flexing_conduit',
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
            'power_optic_olt',
            'odc_terbuka',
            'odc_tertutup',
            'flexing_conduit',
        ];
        
        $po = 0;
        $percentage=0;

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

                    
                    $po = $po+1;
                    
                    $odcDetail->$field = $path;
                } else {
                    
                    $po = $po+1;
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
            $po = $po+1;
            $odcDetail->longitude = $validated['longitude']  ?? 0;
        }

        if ($request->filled('notes')) {
            $odcDetail->notes = $validated['notes']  ?? '';
        }
        // else{
            
        //     $pole->longitude = 0;
        // }
        $percentage = ($po/6)*100;
        $odcDetail->progress_percentage = $percentage;
        $odcDetail->updated_by = $user->email ?? null;
        $odcDetail->save();

        if ($bast) {
            $bast->info_odc = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }
        $this->updateBastProgress($bastId);

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
                'pulling_cable',
                'instalasi',
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
            'bast_id' => 'required|string',
            'odc_name' => 'required|string',
        ]);

        $bastId = $validated['bast_id'];
        $odc_name = $validated['odc_name'];
        

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $MappingHomepass = MappingHomepass::on('mysql_inventory')
                    ->where('ODC', $odc_name)->first();

        if (! $MappingHomepass) {
            return response()->json([
                'status' => 'error',
                'message' => "ODC dengan {$odc_name} tidak ditemukan",
            ], 404);
        }

        $query = MappingHomepass::where('ODC', $odc_name)
                ->select('feeder_name')
                ->groupBy('feeder_name')
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
            'pulling_cable',
            'instalasi'
        ];
        
            $po= 0;
            $percentage =0;

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


                    
                    $po= $po+1;
                    $feederDetail->$field = $path;
                } else {
                    
                    $po= $po+1;
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
            $po= $po+1;
            $feederDetail->longitude = $validated['longitude']  ?? 0;
        }

        if ($request->filled('notes')) {
            $feederDetail->notes = $validated['notes']  ?? '';
        }
        // else{
            
        //     $pole->longitude = 0;
        // }
        $percentage = ($po/2)*100;
        $feederDetail->progress_percentage = $percentage;
        $feederDetail->updated_by = $user->email ?? null;
        $feederDetail->save();

        if ($bast) {
            $bast->info_feeder = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }
        
        $this->updateBastProgress($bastId);

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
        $po=0;
        $percentage=0;

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


                    
                    $po=$po+1;
                    $RBSDetail->$field = $path;
                } else {
                    
                    $po=$po+1;
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
            $po=$po+1;
            $RBSDetail->longitude = $validated['longitude']  ?? 0;
        }

        if ($request->filled('notes')) {
            $RBSDetail->notes = $validated['notes']  ?? '';
        }
        // else{
            
        //     $pole->longitude = 0;
        // }
        $percentage = ($po/3)*100;
        $RBSDetail->progress_percentage = $percentage;

        $RBSDetail->updated_by = $user->email ?? null;
        $RBSDetail->save();

        if ($bast) {
            $bast->info_rbs = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }
        
        $this->updateBastProgress($bastId);

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

    public function listodphc(Request $request)
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
            'station_name' => 'nullable|string',
        ]);

        $station_name = $validated['station_name'];
        
        if($station_name!=''){
            $odpNames = ODPDetail::join('BastProject as bp', 'bp.bast_id', '=', 'ODPDetail.bast_id')
                ->where('bp.station_name', $station_name)
                ->groupBy('ODPDetail.odp_name')
                ->pluck('ODPDetail.odp_name')
                ->toArray();
        }else{
            $odpNames = ODPDetail::join('BastProject as bp', 'bp.bast_id', '=', 'ODPDetail.bast_id')
                ->groupBy('ODPDetail.odp_name')
                ->pluck('ODPDetail.odp_name')
                ->toArray();
        }
        

       if (empty($odpNames)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ODP tidak ditemukan',
            ], 404);
        }

        
        

        return response()->json([
            'status' => 'success',
            'message' => 'List ODP',
            'list_odp' => $odpNames,
        ]);
        
    }

    public function listodp_porthc(Request $request)
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
            'odp_name' => 'required|string',
        ]);

        $odp_name = $validated['odp_name'];
        
             $HomeConnect = HomeConnect::on('mysql_inventory')
                            ->select('odp_name','port_odp','status_port', 'bast_id')
                            ->where('odp_name', $odp_name)
                            ->where('status_port', 'idle')
                            ->get()
                            ->toArray();;

            if (!$HomeConnect) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Tidak ada PORT IDLE untuk ODP {$odp_name}",
                ], 404);
            }
        

        return response()->json([
            'status' => 'success',
            'message' => 'List ODP PORT' ,
            'list_odp_port' => $HomeConnect,
        ]);
        
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
            'bast_id' => 'required|string',
            'id_pelanggan' => 'required|string',
            'name_pelanggan' => 'required|string',
            'odp_name' => 'required|string',
            'port_odp' => 'nullable|string',
            'merk_ont' => 'required|string',
            'sn_ont' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
        ]);

        $bastId = $validated['bast_id'];
        $id_pelanggan = $validated['id_pelanggan'];
        $name_pelanggan = $validated['name_pelanggan'];
        $odp_name = $validated['odp_name'];
        $port_odp = $validated['port_odp'];
        $merk_ont = $validated['merk_ont'];
        $sn_ont = $validated['sn_ont'];

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "SITE dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = HomeConnect::where('bast_id', $bastId)->where('odp_name', $odp_name)->where('port_odp', $port_odp);
        $HomeConnect = $query->first();
        // $id_pelanggan = $HomeConnect->id_pelanggan;
        // $name_pelanggan = $HomeConnect->name_pelanggan;
        // $odp_name = $HomeConnect->odp_name;
        // $port_odp = $HomeConnect->port_odp;
        // $merk_ont = $HomeConnect->merk_ont;
        // $sn_ont = $HomeConnect->sn_ont;
         

        if (!$HomeConnect && $HomeConnect->odp_name != '') {
            return response()->json([
                'status' => 'error',
                'message' => "ODP dengan {$odp_name} tidak ditemukan",
            ], 404);
        //     $HomeConnect = new HomeConnect();
        //     $HomeConnect->bast_id = $bastId;
        //     $HomeConnect->id_pelanggan = $id_pelanggan;
        //     $HomeConnect->name_pelanggan = $name_pelanggan;
        //     $HomeConnect->odp_name = $odp_name;
        //     $HomeConnect->port_odp = $port_odp;
        //     $HomeConnect->merk_ont = $merk_ont;
        //     $HomeConnect->sn_ont = $sn_ont;
        //     $HomeConnect->created_by = $user->email;
        // }else{
            
           
            // $HomeConnect->bast_id = $bastId;
            // $HomeConnect->odp_name = $request->input('odp_name', $HomeConnect->odp_name);
            // $HomeConnect->port_odp = $request->input('port_odp', $HomeConnect->port_odp);
        }

    
        $photoFields = [
            // 'foto_label_odp',
            // 'foto_hasil_ukur_odp',
            // 'foto_penarikan_outdoor',
            // 'foto_aksesoris_ikr',
            // 'foto_sn_ont',
            // 'foto_depan_rumah',
            'foto_label_id_plg',
            'foto_qr',
        ];
        
            $po = 0;
            $percentage = 0;

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


                    
                    $po = $po+1;
                    $HomeConnect->$field = $path;
                } else {
                    
                    $po = $po+1;
                    $HomeConnect->$field = $filePhoto;
                }
            }
        }
        
        $HomeConnect->id_pelanggan = $request->input('id_pelanggan', $HomeConnect->id_pelanggan);
        $HomeConnect->name_pelanggan = $request->input('name_pelanggan', $HomeConnect->name_pelanggan);
        $HomeConnect->merk_ont = $request->input('merk_ont', $HomeConnect->merk_ont);
        $HomeConnect->sn_ont = $request->input('sn_ont', $HomeConnect->sn_ont);

        if ($request->filled('latitude')) {
            $HomeConnect->latitude = $validated['latitude'] ?? 0;
        }
        // else{
        //     $pole->latitude = 0;
        // }

        if ($request->filled('longitude')) {
            $po = $po+1;
            $HomeConnect->longitude = $validated['longitude']  ?? 0;
        }

        if ($request->filled('notes')) {
            $HomeConnect->notes = $validated['notes']  ?? '';
        }
        // else{
            
        //     $pole->longitude = 0;
        // }
        
        $percentage = ($po/3)*100;
        $HomeConnect->progress_percentage = $percentage;
        $HomeConnect->status_port = "used";
        $HomeConnect->updated_by = $user->email ?? null;
        $HomeConnect->save();

        if ($bast) {
            $bast->info_pole = 1;
            $bast->status = 'in progress';
            $bast->updated_by = $user->email;
            $bast->save();
        }
        
        $this->updateBastProgress($bastId);

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
            'bast_id' => 'required|string',
            'odp_name' => 'required|string',
            'port_odp' => 'required|string',
            
        ]);

        $bastId = $validated['bast_id'];
        $odp_name = $validated['odp_name'];
        $port_odp = $validated['port_odp'];

        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();

        if (! $bast) {
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }

        $query = HomeConnect::where('bast_id', $bastId)->where('odp_name', $odp_name)->where('port_odp', $port_odp);
        if ($bastId) {
             
            $record = $query->first();

            $fileKeys = [
                // 'foto_label_odp',
                // 'foto_hasil_ukur_odp',
                // 'foto_penarikan_outdoor',
                // 'foto_aksesoris_ikr',
                // 'foto_sn_ont',
                // 'foto_depan_rumah',
                'foto_label_id_plg',
                'foto_qr',
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
            $detail=[
                'id_pelanggan'=> $record->id_pelanggan,
                'name_pelanggan'=> $record->name_pelanggan,
                'odp_name'=> $record->odp_name,
                'port_odp'=> $record->port_odp,
                'merk_ont'=> $record->merk_ont,
                'sn_ont'=> $record->sn_ont,
            ];

            return response()->json([
                'status' => 'success',
                'data' => array_merge($base64Files, $detail),
            ]);
        }else{
            $poleDetail = $query->first();
            return response()->json([
                'status' => 'error',
                'message' => "BAST dengan ID {$bastId} tidak ditemukan",
            ], 404);
        }
        
        
    }

    private function updateBastProgress($bastId)
    {
        $bast = BastProject::on('mysql_inventory')->where('bast_id', $bastId)->first();
        if($bast->pass==="HOMEPASS"){
            $poleProgress = PoleDetail::where('bast_id', $bastId)
                    ->select(
                        DB::raw('(
                            (
                                (CASE WHEN digging IS NOT NULL AND digging <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN instalasi IS NOT NULL AND instalasi <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN coran IS NOT NULL AND coran <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN tiang_berdiri IS NOT NULL AND tiang_berdiri <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN labeling_tiang IS NOT NULL AND labeling_tiang <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN aksesoris_tiang IS NOT NULL AND aksesoris_tiang <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN latitude IS NOT NULL AND latitude <> "" AND longitude IS NOT NULL AND longitude <> "" THEN 1 ELSE 0 END)
                            ) 
                        ) as jml'))
                    ->value('jml') ?? 0;
            $odcProgress = ODCDetail::where('bast_id', $bastId)
                    ->select(
                        'ODCDetail.*',
                        DB::raw('(
                            (
                                (CASE WHEN instalasi IS NOT NULL AND instalasi <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN odc_terbuka IS NOT NULL AND odc_terbuka <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN odc_tertutup IS NOT NULL AND odc_tertutup <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN power_optic_olt IS NOT NULL AND power_optic_olt <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN flexing_conduit IS NOT NULL AND flexing_conduit <> "" THEN 1 ELSE 0 END) +
                                (CASE WHEN latitude IS NOT NULL AND latitude <> "" AND longitude IS NOT NULL AND longitude <> "" THEN 1 ELSE 0 END)
                            ) 
                        ) as jml'))
                    ->value('jml') ?? 0;
            $odpProgress = ODPDetail::where('bast_id', $bastId)
                        ->select(
                                'ODPDetail.*',
                                DB::raw('(
                                    (
                                        (CASE WHEN instalasi IS NOT NULL AND instalasi <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN odp_terbuka IS NOT NULL AND odp_terbuka <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN odp_tertutup IS NOT NULL AND odp_tertutup <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN power_optic_odc IS NOT NULL AND power_optic_odc <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN latitude IS NOT NULL AND latitude <> "" AND longitude IS NOT NULL AND longitude <> "" THEN 1 ELSE 0 END)
                                    ) 
                                ) as jml'))
                    ->value('jml') ?? 0;
            $rbsProgress = RBSDetail::where('bast_id', $bastId)
                        ->select(
                                'RBSDetail.*',
                                DB::raw('(
                                    (
                                        (CASE WHEN hasil_otdr IS NOT NULL AND hasil_otdr <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN penyambungan_core IS NOT NULL AND penyambungan_core <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN latitude IS NOT NULL AND latitude <> "" AND longitude IS NOT NULL AND longitude <> "" THEN 1 ELSE 0 END)
                                    ) 
                                ) as jml'))
                    ->value('jml') ?? 0;
            $feederProgress = FeederDetail::where('bast_id', $bastId)
                        ->select(
                                'FeederDetail.*',
                                DB::raw('(
                                    (
                                        (CASE WHEN pulling_cable IS NOT NULL AND pulling_cable <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN instalasi IS NOT NULL AND instalasi <> "" THEN 1 ELSE 0 END) 
                                    ) 
                                ) as jml'))
                    ->value('jml') ?? 0;
                            
            $jml_all = $poleProgress + $odcProgress + $odpProgress + $rbsProgress + $feederProgress;
            $presen = ($jml_all/23)*100;
        }else{
            $HomeConnectprog = HomeConnect::where('bast_id', $bastId)
                        ->select(
                                'HomeConnect.*',
                                DB::raw('(
                                    (
                                        (CASE WHEN foto_label_odp IS NOT NULL AND foto_label_odp <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN foto_hasil_ukur_odp IS NOT NULL AND foto_hasil_ukur_odp <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN foto_penarikan_outdoor IS NOT NULL AND foto_penarikan_outdoor <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN foto_aksesoris_ikr IS NOT NULL AND foto_aksesoris_ikr <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN foto_sn_ont IS NOT NULL AND foto_sn_ont <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN foto_depan_rumah IS NOT NULL AND foto_depan_rumah <> "" THEN 1 ELSE 0 END) +
                                        (CASE WHEN latitude IS NOT NULL AND latitude <> "" AND longitude IS NOT NULL AND longitude <> "" THEN 1 ELSE 0 END)
                                    ) 
                                ) as jml'))
                    ->value('jml') ?? 0;
                            
            $jml_all = $HomeConnectprog;
            $presen = ($jml_all/3)*100;
        }
        

        
        if ($bast) {
            $bast->progress_percentage = $presen;
            $bast->updated_at = now();
            $bast->save();
        }
    }

    
}
