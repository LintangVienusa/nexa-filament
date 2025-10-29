<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use App\Models\TimeSheet;

class EventTimesheet extends Model implements Eventable
{
    public function toCalendarEvent(): CalendarEvent
    {
        // For eloquent models, make sure to pass the model to the constructor
        return TimeSheet::make($this)
            ->title($this->job_description)
            ->start($this->created_at)
            ->end($this->updated_at);
    }
}
