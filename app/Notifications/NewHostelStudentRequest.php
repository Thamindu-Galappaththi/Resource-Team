<?php

namespace App\Notifications;

use App\Models\HostelStudentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewHostelStudentRequest extends Notification
{
    use Queueable;

    public function __construct(public HostelStudentRequest $studentRequest)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'student_request_id' => $this->studentRequest->id,
            'reference' => $this->studentRequest->reference,
            'icon' => 'ti-school',
            'title' => 'Student hostel request',
            'url' => route('hostel.student-requests.index', ['search' => $this->studentRequest->reference]),
            'message' => $this->studentRequest->student_name.' ('.$this->studentRequest->student_id.') requested a hostel stay: '.$this->studentRequest->reference,
        ];
    }
}
