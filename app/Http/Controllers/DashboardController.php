<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $service = DashboardService::for($request->user());

        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : null;

        return view('dashboard.index', [
            'indicateurs' => $service->indicateurs(
                $from?->toDateString(),
                $to?->toDateString(),
            ),
            'dernieresCommandes' => $service->dernieresCommandes(),
            'prochainesLivraisons' => $service->commandesALivrerProchainement(),
            'commandesEnRetard' => $service->commandesEnRetard(),
            'prochainsRendezVous' => $service->prochainsRendezVous(),
            'evolution' => $service->evolutionFinanciere(),
            'activite' => $service->activiteRecente(),
            'topClients' => $service->topClients(),
            'filtres' => $request->only('from', 'to'),
        ]);
    }
}
