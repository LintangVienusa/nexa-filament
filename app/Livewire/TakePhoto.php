<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TakePhoto extends Component
{
    // Counter
    public $count = 0;

    // Webcam Photo
    public $photo;     // base64 preview
    public $photoPath; // path untuk DB

    public function increment()
    {
        $this->count++;
        $this->dispatch('counterUpdated', $this->count);
    }
    protected $listeners = ['savePhoto']; // Listener untuk JS emit

    public function savePhoto($photoData)
    {

        if (!$photoData) return;
       
        // Pastikan folder ada
        if (!Storage::disk('public')->exists('attendances')) {
            Storage::disk('public')->makeDirectory('attendances');
        }

        // Decode base64
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photoData));
        $filename = 'attendances/checkin_' . uniqid() . '.png';
        Storage::disk('public')->put($filename, $imageData);

        $this->photoPath = $filename;

        // Kirim path ke hidden input
        $this->dispatchBrowserEvent('photoSaved', ['path' => $filename]);

        $this->photo = null; // reset preview
    }

    public function render()
    {
        return view('livewire.take-photo');
    }
}
