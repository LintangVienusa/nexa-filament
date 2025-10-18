<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;

class TakePhoto extends Component
{

    use WithFileUploads;

    public $name;
    public $photoBase64;
    public $check_in_evidence;

    public function submit()
    {
        // Validasi form
        $this->validate([
            'name' => 'required|string|max:255',
            'photoBase64' => 'required|string',
        ]);

        // Simpan foto ke storage/public/photos
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $this->photoBase64));
        $fileName = 'photo_' . time() . '.png';
        file_put_contents(public_path('photos/' . $fileName), $data);

        session()->flash('message', 'Form berhasil dikirim dan foto tersimpan!');
        
        // Reset form
        $this->name = '';
        $this->photoBase64 = null;
    }

    public function render()
    {
        return view('livewire.take-photo');
    }
}
