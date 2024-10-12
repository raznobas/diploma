<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryCost;
use App\Models\CategoryCostAdditional;
use App\Models\Client;
use App\Models\ClientStatus;
use App\Models\LeadAppointment;
use App\Models\Sale;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class SaleController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index()
    {
        $this->authorize('manage-sales');

        if (auth()->user()->director_id === null) {
            return false;
        }
        $sales = Sale::where('director_id', auth()->user()->director_id)
            ->with(['client:id,name,surname,patronymic,is_lead'])
            ->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $categories = Category::where('director_id', auth()->user()->director_id)->get();

        // Получаем все настройки стоимости
        $categoryCosts = CategoryCost::with('additionalCosts')
            ->where('director_id', auth()->user()->director_id)
            ->get();

        $person = session('person');
        return Inertia::render('Sales', [
            'categories' => $categories,
            'categoryCosts' => $categoryCosts,
            'sales' => $sales,
            'person' => $person
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-sales');

        $validated = $request->validate([
            'sale_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'service_or_product' => 'required|in:service,product',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'product_type' => 'nullable|exists:categories,name',
            'subscription_duration' => 'nullable|exists:categories,name',
            'visits_per_week' => 'nullable|exists:categories,name',
            'training_count' => 'nullable|exists:categories,name',
            'trainer_category' => 'nullable|exists:categories,name',
            'trainer' => 'nullable|exists:categories,name',
            'subscription_start_date' => 'nullable|date',
            'subscription_end_date' => 'nullable|date',
            'cost' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'pay_method' => 'nullable|exists:categories,name',
            'comment' => 'nullable|string',
            'created_by' => 'required||exists:users,id',
        ]);

        $client = Client::find($validated['client_id']);

        // Если покупку сделал лид, тогда меняем статус аккаунта на клиента.
        if ($client->is_lead) {
            $client->is_lead = false;
            $client->save();
        }

        // Если тренировка пробная, то ищем запись в таблице lead_appointments и меняем статус на completed.
        // Это случай, когда Лид пришел на записанную тренировку
        if ($validated['service_type'] === 'trial') {
            $leadAppointment = LeadAppointment::where('client_id', $validated['client_id'])->first();

            if ($leadAppointment) {
                $leadAppointment->status = 'completed';
                $leadAppointment->save();

                ClientStatus::create([
                    'client_id' => $leadAppointment->client_id,
                    'status_to' => 'appointment_completed',
                    'director_id' => $leadAppointment->director_id,
                ]);
            }
        }

        Sale::create($validated);

        ClientStatus::create([
            'client_id' => $client->id,
            'status_to' => 'purchase_created',
            'director_id' => $client->director_id,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage-sales');

        $validated = $request->validate([
            'sale_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'service_or_product' => 'required|in:service,product',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'product_type' => 'nullable|exists:categories,name',
            'subscription_duration' => 'nullable|exists:categories,name',
            'visits_per_week' => 'nullable|exists:categories,name',
            'training_count' => 'nullable|exists:categories,name',
            'trainer_category' => 'nullable|exists:categories,name',
            'trainer' => 'nullable|exists:categories,name',
            'subscription_start_date' => 'nullable|date',
            'subscription_end_date' => 'nullable|date',
            'cost' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'pay_method' => 'nullable|exists:categories,name',
            'comment' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
        ]);

        if ($validated['director_id'] != auth()->user()->director_id) {
            return redirect()->back()->withErrors(['director_id' => 'У вас нет прав на редактирование этой продажи.']);
        }

        $sale = Sale::where('id', $id)
            ->where('director_id', auth()->user()->director_id)
            ->firstOrFail();

        $client = Client::where('id', $validated['client_id'])
            ->where('director_id', auth()->user()->director_id)
            ->firstOrFail();

        // Если покупку сделал лид, тогда меняем статус аккаунта на клиента.
        if ($client->is_lead) {
            $client->is_lead = false;
            $client->save();
        }

        // Если тренировка пробная, то ищем запись в таблице lead_appointments и меняем статус на completed.
        // Это случай, когда Лид пришел на записанную тренировку
        if ($validated['service_type'] === 'trial') {
            $leadAppointment = LeadAppointment::where('client_id', $validated['client_id'])
                ->where('director_id', auth()->user()->director_id)
                ->first();

            if ($leadAppointment) {
                $leadAppointment->status = 'completed';
                $leadAppointment->save();

                ClientStatus::updateOrCreate(
                    [
                        'client_id' => $leadAppointment->client_id,
                        'status_to' => 'appointment_completed',
                        'director_id' => $leadAppointment->director_id,
                    ],
                    [
                        'client_id' => $leadAppointment->client_id,
                        'status_to' => 'appointment_completed',
                        'director_id' => $leadAppointment->director_id,
                    ]
                );
            }
        }

        // Обновляем данные продажи
        $sale->update($validated);

        // Создаем или обновляем запись о статусе клиента
        ClientStatus::updateOrCreate(
            [
                'client_id' => $client->id,
                'status_to' => 'purchase_created',
            ],
            [
                'client_id' => $client->id,
                'status_to' => 'purchase_created',
            ]
        );

        return redirect()->back()->with('success', 'Продажа успешно обновлена!');
    }

    public function show($client_id)
    {
        $this->authorize('manage-sales');

        $clientSales = Sale::where('client_id', $client_id)
            ->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($clientSales);
    }

    public function destroy(Sale $sale)
    {
        $this->authorize('manage-sales');

        if ($sale->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление этой задачи.']);
        }
        $sale->delete();

        return redirect()->back();
    }
}
