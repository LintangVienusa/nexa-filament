<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Storage;

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

    public function savePhoto($imageData)
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData));
        $filename = 'attendances/' . uniqid('checkin_') . '.png';
        Storage::disk('public')->put($filename, $imageData);

        $this->photoPath = $filename;

        // Kirim event ke browser untuk hidden input
        $this->dispatchBrowserEvent('photoTaken', ['path' => $filename]);
    }

    public function render()
    {
        return view('livewire.take-photo');
    }
}
