<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class BastInstalasiController extends Controller
{
    public function BastSave(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'serial_number' => 'required|string',
            'bast_photo' => 'nullable|string',     
            'instalasi_photo' => 'nullable|string',
            'coran_photo' => 'nullable|string',
            'tiang_photo' => 'nullable|string',
            'label_photo' => 'nullable|string',
            'aksesoris_photo' => 'nullable|string',
            'koordinat_photo' => 'nullable|string',
        ]);
        $data = $request->all();


        foreach(['bast_photo','instalasi_photo','coran_photo','tiang_photo','label_photo','aksesoris_photo','koordinat_photo'] as $photo) {
            if (!empty($request->$photo)) {
                $data[$photo] = $this->saveBase64Image($request->$photo, $photo);
            }
        }

        $doc = Documentation::create($data);

        return response()->json($doc, 201);
    }

    private function saveBase64Image($base64Image, $folder)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $extension = strtolower($type[1]);
            if (!in_array($extension, ['png','jpg','jpeg'])) {
                $extension = 'png';
            }
        } else {
            $extension = 'png';
        }

        $base64Image = str_replace(' ', '+', $base64Image);
        $imageData = base64_decode($base64Image);
        if ($imageData === false) {
            throw new \Exception('Base64 decode failed');
        }

        $image = Image::make($imageData);

        $maxBytes = 2 * 1024 * 1024; 
        $quality = 90; 
        $tempPath = sys_get_temp_dir().'/temp_image.'.$extension;

        do {
            $image->encode($extension, $quality);
            file_put_contents($tempPath, $image);
            $size = filesize($tempPath);
            $quality -= 5;
        } while ($size > $maxBytes && $quality > 10);

        $fileName = $folder.'_'.time().'_'.uniqid().'.'.$extension;
        $filePath = 'documentation_photos/'.$fileName;
        Storage::disk('public')->put($filePath, file_get_contents($tempPath));

        @unlink($tempPath);

        return $filePath;
    }
}
