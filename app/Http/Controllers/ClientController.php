<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Category;
use App\Models\Client;
use App\Models\ClientStatus;
use App\Models\LeadAppointment;
use App\Models\Sale;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class ClientController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index(Request $request, FilterController $filterController)
    {
        $this->authorize('manage-sales');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $routeName = Route::currentRouteName();

        // Основной запрос
        $query = Client::where('director_id', auth()->user()->director_id)
            ->where('is_lead', false)
            ->orderBy('created_at', 'desc')
            ->select('id', 'surname', 'name', 'patronymic', 'birthdate', 'phone', 'email');

        // Применяем фильтры через FilterController
        $filterController->applyFilters($query, $request, $routeName);

        $clients = $query->paginate(50, ['*'], 'page', $request->input('page', 1));

        // Получаем источники
        $source_options = Category::where('director_id', auth()->user()->director_id)
            ->where('type', 'ad_source')
            ->get();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'source_options' => $source_options,
            'filter' => $request->all(),
        ]);
    }

    public function store(Request $request, $callId = null)
    {
        $this->authorize('manage-sales');
        $validated = $request->validate([
            'surname' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'workplace' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'ad_source' => 'nullable|string|max:255',
            'is_lead' => 'boolean',
            'director_id' => 'required|exists:users,id',
        ]);

        // Создаем клиента и получаем его ID
        $client = Client::create($validated);

        // Определяем статус в зависимости от значения is_lead
        $status = $validated['is_lead'] ? 'lead_created' : 'client_created';

        ClientStatus::create([
            'client_id' => $client->id,
            'status_to' => $status,
            'director_id' => $client->director_id,
        ]);

        // Если передан callId, обновляем запись звонка
        if ($callId) {
            // Находим запись звонка по callId
            $call = Call::find($callId);

            if ($call) {
                // Получаем номер
                $phoneFrom = $call->phone_from;

                // Находим все записи звонков с таким же номером phone_from
                $callsWithSamePhone = Call::where('phone_from', $phoneFrom)->get();

                // Обновляем client_id для всех найденных записей
                foreach ($callsWithSamePhone as $callToUpdate) {
                    $callToUpdate->update(['client_id' => $client->id]);
                }
            }
        }

        return redirect()->back()->with(['person' => $client]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage-sales');
        $validatedData = $request->validate([
            'surname' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'workplace' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'ad_source' => 'nullable|string|max:255',
        ]);

        $client = Client::findOrFail($id);
        $client->update($validatedData);
    }

    public function destroy(Request $request, $id)
    {
        // Проверка роли пользователя
        $user = $request->user();
        if (!$user->isAn('admin') && !$user->isA('director')) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление клиентов или лидов.']);
        }

        $client = Client::where('director_id', auth()->user()->director_id)->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Клиент не найден'], 404);
        }

        $client->delete();

        return redirect()->back()->with('success', 'Клиент/лид успешно удален.');
    }

    public function search(Request $request)
    {
        $this->authorize('manage-sales');
        $query = $request->input('query');
        $isLead = $request->input('is_lead'); // Параметр может быть передан или нет

        if (empty($query)) {
            return response()->json([]);
        }

        // Генерируем варианты номера телефона
        $phoneVariants = $this->getPhoneVariants($query);

        $clients = Client::select('id', 'name', 'surname', 'patronymic', 'phone', 'ad_source', 'is_lead')
            ->where('director_id', auth()->user()->director_id) // Ограничиваем поиск по director_id
            ->when($isLead !== null, function ($q) use ($isLead) {
                return $q->where('is_lead', $isLead);
            })
            ->where(function ($q) use ($query, $phoneVariants) {
                $q->where('surname', 'like', "$query%")
                    ->orWhere('name', 'like', "$query%")
                    ->orWhere('patronymic', 'like', "$query%");

                // Добавляем поиск по всем вариантам номера телефона
                foreach ($phoneVariants as $variant) {
                    $q->orWhere('phone', 'like', "$variant%");
                }
            })
            ->get();

        return response()->json($clients);
    }

    public function show($id)
    {
        $this->authorize('manage-sales');

        $client = Client::findOrFail($id);

        // Проверяем, что клиент до этого был лидом
        $leadCreatedExists = ClientStatus::where('client_id', $client->id)
            ->where('status_to', 'lead_created')
            ->exists();

        if ($leadCreatedExists) {
            // Проверяем поле purchase_created для получения даты перехода из лида в клиенты (дата первой покупки)
            $clientHistory = ClientStatus::where('client_id', $client->id)
                ->where('status_to', 'purchase_created')
                ->first();

            if ($clientHistory) {
                $client->purchase_created_at = $clientHistory->created_at;
            } else {
                $client->purchase_created_at = null;
            }
        } else {
            $client->purchase_created_at = null;
        }

        return response()->json($client);
    }

    public function old()
    {
        $this->authorize('manage-sales');
        if (auth()->user()->director_id === null) {
            return false;
        }

        $currentDate = now();

        // Получаем всех клиентов с абонементами, отсортированными по дате окончания
        $clients = Client::where('director_id', auth()->user()->director_id)
            ->where('is_lead', false)
            ->whereHas('sales', function ($query) use ($currentDate) {
                $query->whereIn('service_type', ['group', 'minigroup']);
            })
            ->with(['sales' => function ($query) use ($currentDate) {
                $query->select('client_id', 'subscription_end_date', 'service_type')
                    ->whereIn('service_type', ['group', 'minigroup'])
                    ->orderBy('subscription_end_date', 'desc')
                    ->limit(1);
            }])
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->get();

        // Фильтруем клиентов по условиям
        $oldClients = $clients->filter(function ($client) use ($currentDate) {
            $subscriptionEndDate = $client->sales->first()->subscription_end_date ?? null;

            if ($subscriptionEndDate === null) {
                return false;
            }
            // Проверяем, есть ли у клиента хотя бы один действующий абонемент
            $hasActiveSubscription = Sale::where('client_id', $client->id)
                ->where('subscription_end_date', '>', $currentDate)
                ->exists();
            if ($hasActiveSubscription) {
                return false;
            }

            // Клиенты, у которых абонемент закончился более месяца назад
            if ($subscriptionEndDate < (clone $currentDate)->subMonth()->startOfDay()) {
                return true;
            }

            return false;
        });

        // Добавляем поля из sales к каждому клиенту
        $oldClients->each(function ($client) {
            $client->subscription_end_date = $client->sales->first()->subscription_end_date ?? null;
        });

        // Сортируем клиентов по subscription_end_date в порядке убывания
        $sortedCollection = $oldClients->sortByDesc('subscription_end_date')->values();

        // Пагинация на стороне сервера
        $paginatedClients = $this->serverPaginate($sortedCollection);

        return Inertia::render('Clients/Old', [
            'oldClients' => $paginatedClients,
        ]);
    }

    public function trials()
    {
        $this->authorize('manage-sales');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $currentDate = now();
        $oneMonthAgo = (clone $currentDate)->subMonth();
        $directorId = auth()->user()->director_id;

        // Получаем все пробные тренировки, которые были более месяца назад и относятся к текущему director_id
        $trials = Sale::where('sale_date', '<', $oneMonthAgo)
            ->where('service_type', '=', 'trial')
            ->where('director_id', $directorId)
            ->get();

        // Получаем уникальные client_id из этих пробных тренировок
        $clientIds = $trials->pluck('client_id')->unique();

        // Получаем клиентов, у которых нет активного абонемента и относятся к текущему director_id
        $trialClients = Client::whereIn('id', $clientIds)
            ->where('director_id', $directorId)
            ->whereDoesntHave('sales', function ($query) use ($currentDate, $directorId) {
                $query->where('subscription_end_date', '>', $currentDate)
                    ->where('director_id', $directorId);
            })
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->with(['sales' => function ($query) use ($oneMonthAgo, $directorId) {
                $query->where('sale_date', '<', $oneMonthAgo)
                    ->where('service_type', '=', 'trial')
                    ->where('director_id', $directorId);
            }])
            ->get();

        // Получаем training_date для каждого клиента
        $trialClients->each(function ($client) {
            $client->training_date = $client->sales->first()->sale_date ?? null;
        });

        // Сортируем клиентов по training_date в порядке убывания
        $sortedCollection = $trialClients->sortByDesc('training_date')->values();

        // Применяем пагинацию после сортировки
        $paginatedTrialClients = $this->serverPaginate($sortedCollection);

        return Inertia::render('Clients/Trials', [
            'trialClients' => $paginatedTrialClients,
        ]);
    }

    public function getSourceOptions()
    {
        $this->authorize('manage-sales');
        if (auth()->user()->director_id === null) {
            return false;
        }

        $source_options = Category::where('director_id', auth()->user()->director_id)
            ->where('type', 'ad_source')
            ->get();

        return response()->json($source_options);
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

    protected function getPhoneVariants($phone)
    {
        // Удаляем все нецифровые символы
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Если строка пустая, возвращаем пустой массив
        if (empty($phone)) {
            return [];
        }

        $variants = [];

        // Если номер начинается с 8, добавляем варианты с 7 и +7
        if ($phone[0] === '8') {
            $variants[] = '7' . substr($phone, 1); // Заменяем 8 на 7
            $variants[] = '+7' . substr($phone, 1); // Заменяем 8 на +7
        }

        // Если номер начинается с 7, добавляем варианты с 8 и +7
        if ($phone[0] === '7') {
            $variants[] = '8' . substr($phone, 1); // Заменяем 7 на 8
            $variants[] = '+7' . substr($phone, 1); // Заменяем 7 на +7
        }

        // Если номер начинается с +7, добавляем варианты с 8 и 7
        if (substr($phone, 0, 2) === '+7') {
            $variants[] = '8' . substr($phone, 2); // Заменяем +7 на 8
            $variants[] = '7' . substr($phone, 2); // Заменяем +7 на 7
        }

        // Добавляем оригинальный номер
        $variants[] = $phone;

        return $variants;
    }
}
