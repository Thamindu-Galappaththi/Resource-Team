<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true, 'unread_count' => 0]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $item = $this->notificationForUser($request, $notification);
        $item->markAsRead();

        return $this->statusResponse($request);
    }

    public function markUnread(Request $request, string $notification): JsonResponse
    {
        $item = $this->notificationForUser($request, $notification);
        $item->markAsUnread();

        return $this->statusResponse($request);
    }

    private function notificationForUser(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }

    private function statusResponse(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
