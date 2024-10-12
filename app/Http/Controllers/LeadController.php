<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\ClientStatus;
use App\Models\LeadAppointment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class LeadController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index(Request $request)
    {
        $this->authorize('manage-leads');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $leadsPage = $request->input('page', 1);
        $leadAppointmentsPage = $request->input('page_appointments', 1);

        $leads = Client::where('director_id', auth()->user()->director_id)
            ->where('is_lead', true)
            ->orderBy('created_at', 'desc')
            ->paginate(50, ['*'], 'page', $leadsPage);

        $leadAppointments = LeadAppointment::where('director_id', auth()->user()->director_id)
            ->where('status', 'scheduled')
            ->with('client:id,name,surname,patronymic,phone,ad_source')
            ->orderBy('training_date', 'desc')
            ->paginate(50, ['*'], 'page_appointments', $leadAppointmentsPage);

        $categories = Category::where('director_id', auth()->user()->director_id)->get();

        $person = session('person');

        return Inertia::render('Leads/Index', [
            'categories' => $categories,
            'leads' => $leads,
            'leadAppointments' => $leadAppointments,
            'person' => $person
        ]);
    }

    // сохранение записи лида на пробную тренировку
    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $today = now()->toDateString();

        $validated = $request->validate([
            'sale_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'trainer' => 'nullable',
            'training_date' => [
                'required',
                'date',
                'after_or_equal:' . $today,
            ],
            'training_time' => 'nullable',
            'status' => 'nullable|in:scheduled,cancelled,completed,no_show',
        ]);

        $appointment = LeadAppointment::create($validated);

        ClientStatus::create([
            'client_id' => $appointment->client_id,
            'status_to' => 'appointment_created',
            'director_id' => $appointment->director_id,
        ]);

        return redirect()->back();
    }

    // редактирование пробной тренировки
    public function update(Request $request, LeadAppointment $lead)
    {
        $this->authorize('manage-leads');

        $today = now()->toDateString();

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'trainer' => 'nullable',
            'training_date' => [
                'required',
                'date',
                'after_or_equal:' . $today,
            ],
            'training_time' => 'nullable',
            'status' => 'nullable|in:scheduled,cancelled,completed,no_show',
        ]);

        if ($validated['director_id'] != auth()->user()->director_id) {
            return redirect()->back()->withErrors(['director_id' => 'У вас нет прав на редактирование этой записи.']);
        }

        $lead->update($validated);

        ClientStatus::updateOrCreate(
            [
                'client_id' => $lead->client_id,
                'status_to' => 'appointment_created',
                'director_id' => $lead->director_id,
            ],
            [
                'client_id' => $lead->client_id,
                'status_to' => 'appointment_created',
                'director_id' => $lead->director_id,
            ]);

        return redirect()->back()->with('success', 'Запись успешно обновлена!');
    }

    // удаление пробной тренировки
    public function destroy(LeadAppointment $lead)
    {
        $this->authorize('manage-leads');

        if ($lead->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление этой задачи.']);
        }

        $lead->delete();

        return redirect()->back()->with('success', "Запись успешно удалена");
    }

}
