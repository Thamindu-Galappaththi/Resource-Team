<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class ReservationDocumentStore
{
    /**
     * Saves proof documents for a special approval request; nothing is stored when it is not a special request.
     *
     * @param  array<int, UploadedFile>|null  $files
     */
    public function attach(Reservation $reservation, ?array $files, User $actor): void
    {
        if (! $reservation->special_approval) {
            return;
        }

        foreach ($files ?? [] as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            ReservationDocument::query()->create([
                'reservation_id' => $reservation->id,
                'uploaded_by_user_id' => $actor->id,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'path' => $file->store('reservation-documents/'.$reservation->id, ReservationDocument::DISK),
                'mime_type' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }
    }
}
