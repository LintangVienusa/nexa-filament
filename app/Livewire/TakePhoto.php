<?php

namespace App\Http\Livewire;

use Livewire\Component;

class TakePhoto extends Component
{
    public $photo;
    
    public $check_in_evidence;

    public function savePhotoDirectly()
    {
        $base64 = $this->check_in_evidence; // sudah terisi dari hidden input
        if (!$base64) return;

        $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        $fileName = 'photo_' . time() . '.jpg';
        file_put_contents(public_path('photos/' . $fileName), $imgData);

        // update form state lagi dengan nama file
        $this->form->fill(['check_in_evidence' => $fileName]);
    }


    public function render()
    {
        return view('livewire.take-photo');
    }
}
