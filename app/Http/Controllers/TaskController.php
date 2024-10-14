<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\LeadAppointment;
use App\Models\Sale;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class TaskController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index(Request $request)
    {
        $this->authorize('manage-tasks');

        if (auth()->user()->director_id === null) {
            return false;
        }
        $tasks = Task::with(['client:id,surname,name,birthdate,phone,email', 'userSender:id,name'])
            ->where('director_id', auth()->user()->director_id)
            ->orderBy('task_date')
            ->paginate(50);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-tasks');

        $today = now()->toDateString();

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'user_sender_id' => 'required|exists:users,id',
            'task_date' => [
                'required',
                'date',
                'after_or_equal:' . $today,
            ],
            'task_description' => 'required|string',
        ]);

        Task::create($validated);

        return redirect()->back();
    }

    public function show($client_id)
    {
        $this->authorize('manage-tasks');

        $tasks = Task::with('userSender:id,name')
            ->where('client_id', $client_id)
            ->where('director_id', auth()->user()->director_id)
            ->orderBy('task_date', 'asc')
            ->get();

        return response()->json($tasks);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('manage-tasks');

        $today = now()->toDateString();

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'user_sender_id' => 'required|exists:users,id',
            'task_date' => [
                'required',
                'date',
                'after_or_equal:' . $today,
            ],
            'task_description' => 'required|string',
        ]);

        if ($validated['director_id'] != auth()->user()->director_id) {
            return redirect()->back()->withErrors(['director_id' => 'У вас нет прав на редактирование этой задачи.']);
        }

        $task->update($validated);

        return redirect()->back()->with('success', 'Задача успешно обновлена!');
    }

    public function destroy(Task $task)
    {
        $this->authorize('manage-tasks');

        if ($task->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление этой задачи.']);
        }
        $task->delete();

        return redirect()->back()->with('success', "Задача успешно удалена");
    }

    public function noShowLeads(Request $request)
    {
        $this->authorize('manage-tasks');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $noShowLeads = LeadAppointment::with(['client:id,surname,name,birthdate,phone,email'])
            ->where('director_id', auth()->user()->director_id)
            ->where('status', 'no_show')
            ->orderBy('training_date')
            ->paginate(50);

        return Inertia::render('Tasks/NoShowLeads', [
            'noShowLeads' => $noShowLeads,
        ]);
    }

    public function trialsLessThanMonth(Request $request)
    {
        $this->authorize('manage-tasks');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $currentDate = now();
        $oneMonthAgo = $currentDate->subMonth();
        $directorId = auth()->user()->director_id;

        // Получаем все пробные тренировки, которые были менее месяца назад и относятся к текущему director_id
        $trialsLessThanMonth = Sale::where('sale_date', '>=', $oneMonthAgo)
            ->where('service_type', '=', 'trial')
            ->where('director_id', $directorId)
            ->get();

        // Получаем уникальные client_id из этих пробных тренировок
        $clientIdsLessThanMonth = $trialsLessThanMonth->pluck('client_id')->unique();

        // Получаем клиентов, у которых нет активного абонемента и относятся к текущему director_id
        $trialClientsLessThanMonth = Client::whereIn('id', $clientIdsLessThanMonth)
            ->where('director_id', $directorId)
            ->whereDoesntHave('sales', function ($query) use ($currentDate, $directorId) {
                $query->where('subscription_end_date', '>', $currentDate)
                    ->where('director_id', $directorId);
            })
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->paginate(50);

        // Получаем training_date для каждого клиента
        $trialClientsLessThanMonth->each(function ($client) use ($trialsLessThanMonth) {
            $client->training_date = $trialsLessThanMonth->where('client_id', $client->id)->first()->sale_date ?? null;
        });
        return Inertia::render('Tasks/TrialsLessThanMonth', [
            'trialsLessThanMonth' => $trialClientsLessThanMonth,
        ]);
    }

    public function renewals(Request $request)
    {
        $this->authorize('manage-tasks');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $currentDate = now();
        $filter = $request->input('filter', 'expired'); // По умолчанию фильтр
        $date = $request->input('date', 'asc'); // По умолчанию сортировка

        // Получаем все продажи с групповыми абонементами от 1 месяца и больше
        $salesQuery = Sale::whereHas('client', function ($query) {
            $query->where('director_id', auth()->user()->director_id)
                ->where('is_lead', false);
        })
            ->whereIn('service_type', ['group', 'minigroup'])
            ->where('subscription_duration', '>=', 1)
            ->where(function ($query) use ($currentDate, $filter) {
                if ($filter === 'upcoming') {
                    $query->where('subscription_end_date', '<=', $currentDate->copy()->addDays(7))
                        ->where('subscription_end_date', '>', $currentDate);
                } elseif ($filter === 'expired') {
                    $query->whereBetween('subscription_end_date', [
                        $currentDate->copy()->subMonth()->startOfDay(),
                        $currentDate->copy()->startOfDay()
                    ]);
                }
            })
            ->orderBy('subscription_end_date', $date) // Применяем сортировку
            ->with('client:id,surname,name,birthdate,phone,email');

        $sales = $salesQuery->get()
            ->groupBy('client.id')
            ->map(function ($sales) {
                return $sales->first();
            })
            ->values();

        // Фильтруем продажи по условиям
        $salesToRenewal = $sales->filter(function ($sale) use ($currentDate, $filter) {
            $subscriptionEndDate = $sale->subscription_end_date ? Carbon::parse($sale->subscription_end_date) : null;

            if ($subscriptionEndDate === null) {
                return false;
            }

            // Проверяем, есть ли у клиента хотя бы один действующий абонемент только для фильтра 'expired'
            if ($filter === 'expired') {
                $hasActiveSubscription = Sale::where('client_id', $sale->client->id)
                    ->where('subscription_end_date', '>', $currentDate)
                    ->exists();
                if ($hasActiveSubscription) {
                    return false;
                }
            }

            // Продажи, у которых заканчивается абонемент в течение недели
            if ($filter === 'upcoming' && $subscriptionEndDate->lte($currentDate->copy()->addDays(7)) && $subscriptionEndDate->gte($currentDate)) {
                return true;
            }

            // Продажи, у которых абонемент закончился в течение последнего месяца
            if ($filter === 'expired' && $subscriptionEndDate->gte($currentDate->copy()->subMonth()->startOfDay()) && $subscriptionEndDate->lt($currentDate->copy()->startOfDay())) {
                return true;
            }

            return false;
        });

        // Преобразуем данные для удобства использования в представлении
        $clientsToRenewal = $salesToRenewal->map(function ($sale) {
            return [
                'id' => $sale->client->id,
                'surname' => $sale->client->surname,
                'name' => $sale->client->name,
                'birthdate' => $sale->client->birthdate,
                'phone' => $sale->client->phone,
                'email' => $sale->client->email,
                'subscription_end_date' => $sale->subscription_end_date,
                'service_type' => $sale->service_type,
                'subscription_duration' => $sale->subscription_duration,
            ];
        });

        // Пагинация на стороне сервера
        $paginatedRenewals = $this->serverPaginate($clientsToRenewal);

        return Inertia::render('Tasks/Renewals', [
            'clientsToRenewal' => $paginatedRenewals,
            'filter' => $filter,
            'date' => $date,
        ]);
    }

    private function serverPaginate($items)
    {
        $perPage = 50;
        $currentPage = request()->input('page', 1);
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
