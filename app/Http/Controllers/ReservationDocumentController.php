<?php

namespace App\Http\Controllers;

use App\Enums\ReservationType;
use App\Models\Reservation;
use App\Models\ReservationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationDocumentController extends Controller
{
    public function show(Request $request, Reservation $reservation, ReservationDocument $document): StreamedResponse
    {
        abort_unless($document->reservation_id === $reservation->id, 404);

        $user = $request->user();
        $ability = $reservation->type === ReservationType::HOSTEL->value ? 'viewHostel' : 'view';
        abort_unless(
            $user->canSpecialApprove($reservation) || Gate::forUser($user)->allows($ability, $reservation),
            403,
        );

        $disk = Storage::disk(ReservationDocument::DISK);
        abort_unless($disk->exists($document->path), 404);

        $inline = in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true);

        return $inline
            ? $disk->response($document->path, $document->original_name, ['Content-Type' => $document->mime_type])
            : $disk->download($document->path, $document->original_name);
    }
}
