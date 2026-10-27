<?php

namespace App\Http\Controllers;

use App\Models\DatabaseNotification;
use App\Services\NotificationPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (DatabaseNotification $notification) => NotificationPresenter::toArray($notification))
            ->values();

        return $this->uncachedJson([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true, 'unread_count' => 0]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

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

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $item = $this->notificationForUser($request, $notification);
        $item->delete();

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
