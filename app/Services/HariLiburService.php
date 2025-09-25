<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HariLiburService
{
    public function getLiburTahun($year)
    {
        $response = Http::get("https://api-harilibur.vercel.app/api?year={$year}");

        if ($response->ok()) {
            $data = $response->json();
            return collect($data)->pluck('date')->toArray();
        }

        return [];
    }
}