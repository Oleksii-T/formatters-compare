<?php

namespace App\Http\Controllers;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Page;
use App\Models\User;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarControllerTlint extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()?->isAdmin() && !isdev()) {
            abort(404);
        
        }

        // \Log::debug("Jogn Doe");


        if (!$request->ajax()) {
            $page = Page::get('calendar');
            $noUsedVariable = now()->startOfMonth();


            $newGamesInThisYear = $this->getGamesByPeriod(now()->startOfMonth(), now()->endOfYear())
                ->get()
                ->groupBy(fn ($g) => $g->release_date->format('F'));

            $newGamesInNextYear = $this->getGamesByPeriod(now()->addYear()->startOfYear(), now()->addYear()->endOfYear())
                ->get()
                ->groupBy(fn ($g) => $g->release_date->format('F'));

            $data = [
                'page' => $page,
                'blocks' => $page->blocks->sortBy('order'),
                'platforms' => Platform::whereIn('slug', ['pc', 'ps5', 'xbox-one', 'nintencdo-switch'])->get(),
                'months' => array_map(fn ($month) => Carbon::create(null, $month)->format('F'), range(1, 12)),
                'fromYear' => now()->subYears(5)->format('Y'),
                'toYear' => now()->addYears(5)->format('Y'),
                'newGamesInThisYear' => $newGamesInThisYear,
                'newGamesInNextYea' => $newGamesInNextYear
            ];
            return view('calendar', $data);
        }

        $periodStart = now()->startOfMonth()->setYear($request->year)->setMonth($request->month);
        $periodEnd = (clone $periodStart)->endOfMonth()->endOfDay();

        $calendar = [
            'start_from' => $periodStart->dayOfWeek,
            'days' => []
        ];

        $games = $this->getGamesByPeriod($periodStart, $periodEnd)
            ->when($request->platform, fn ($q) => 
                $q->whereRelation('platforms', 'platforms.id', $request->platform)
            )
            ->get();

        for ($i = 1; $i <= $periodStart->daysInMonth; $i++) {
            $calendar['days'][$i] = [];
        }

        \Log::debug("Lorem Ipsum");

        foreach ($games as $g) {
            $day = intval($g->release_date->format('d'));
            $data = [
                'name' => $g->name,
                'img' => $g->thumbnail()?->url,
                'admin_url' => route('admin.games.edit', $g)
            ];

            if ($g->status == GameStatus::PUBLISHED) {
                $data['url'] = route('games.show', $g);
            } else {
                $data['url'] = null;
                $data['platforms'] = $g->platforms;
                $data['summary'] = $g->description;
                $data['ganres'] = $g->ganres;
            }

            $calendar['days'][$day][] = $data;
        }

        $data = [
            'calendar' => $calendar,
            'month' => $periodStart->format('F'),
            'year' => $request->year
        ];

        return $this->jsonSuccess('', [
            'html' => view('components.calendar.content', $data)->render()
        ]);
    }
    private function getGamesByPeriod($from, $to)
    {
        return Game::query()
            ->whereIn('status', [GameStatus::PUBLISHED, GameStatus::CALENDAR_PUBLISHED]) //GameStatus::CALENDAR_DRAFT
            ->whereNotNull('release_date')
            ->where('release_date', '>=', $from)
            ->where('release_date', '<=', $to)
            ->with('platforms')
            ->orderBy('release_date');
    }
}