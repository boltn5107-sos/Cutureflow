<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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

    /**
     * Point d'état interrogé périodiquement par le navigateur.
     *
     * Les notifications sont habituellement rendues à l'ouverture de la
     * page : sans cet appel, le navigateur n'a aucun moyen de savoir qu'une
     * notification est arrivée entre deux pages, et ne peut donc ni mettre à
     * jour la pastille ni déclencher le son.
     *
     * La lecture est strictement celle du compte connecté, et l'appel ne
     * marque rien comme lu : il ne fait que constat. La notification la
     * plus récente est renvoyée entière pour que le son et la bannière
     * puissent s'afficher sans second aller-retour.
     */
    public function etat(Request $request): JsonResponse
    {
        $dernier = $request->user()->notifications()->latest()->first();

        return response()->json([
            'nonLues' => $request->user()->unreadNotifications()->count(),
            'dernier' => $dernier ? [
                'id' => $dernier->id,
                'titre' => $dernier->data['title'] ?? 'Notification',
                'message' => $dernier->data['message'] ?? '',
                'icone' => $dernier->data['icon'] ?? 'fa-regular fa-bell',
                'ton' => $dernier->data['tone'] ?? 'bg-brand-100 text-brand-700 dark:bg-white/10 dark:text-brand-200',
                'url' => $dernier->data['url'] ?? route('notifications.index'),
                'quand' => $dernier->created_at->diffForHumans(),
            ] : null,
        ]);
    }
}
