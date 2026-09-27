<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $request->string('filtre')->toString() ?: 'toutes';

        $notifications = $request->user()
            ->notifications()
            ->when($filtres === 'non_lues', fn ($q) => $q->whereNull('read_at'))
            ->when($filtres === 'lues', fn ($q) => $q->whereNotNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filtres' => $filtres,
            'nonLues' => $request->user()->unreadNotifications()->count(),
            'lues' => $request->user()->readNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('notifications.index');

        return redirect()->to($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('success', 'La notification a été supprimée.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return back()->with('success', 'Toutes les notifications ont été supprimées.');
    }
}
