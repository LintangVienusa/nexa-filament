<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManagerStatic as Image;


class RotatePhotoController extends Controller
{
    public function rotate(Request $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return response()->json(['success' => false, 'error' => 'Path kosong']);
        }

        $fullPath = Storage::disk('public')->path($path);

        if (!file_exists($fullPath)) {
            return response()->json(['success' => false, 'error' => 'File tidak ditemukan']);
        }

        // $image = Image::make($fullPath);
        $manager = new ImageManager(new Driver());
        $image = $manager->read($fullPath);
        $image->rotate(-90);
        $image->save($fullPath);

        return response()->json(['success' => true]);
    }
    
}
