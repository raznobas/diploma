<?php

namespace App\Http\Controllers;

use App\Models\CollaborationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class CollaborationController extends Controller
{
    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Проверяем, что текущий пользователь является директором
        if (!Auth::check() || !$this->bouncer->is($user)->a('director')) {
            return redirect()->route('dashboard');
        }

        // Получаем все запросы для текущего директора с загрузкой связанных данных
        $requests = CollaborationRequest::where('director_id', $user->director_id)
            ->whereIn('status', ['approved', 'pending'])
            ->orderBy('created_at', 'desc')
            ->with(['manager', 'director']) // Загружаем связанные модели
            ->paginate(15);

        return Inertia::render('Collaboration/AllRequests', [
            'allRequests' => $requests,
        ]);
    }

    public function indexSendRequest(Request $request)
    {
        $user = $request->user();

        // Проверяем, что текущий пользователь является менеджером
        if (!Auth::check() || !$this->bouncer->is($user)->a('manager')) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Collaboration/SendRequest');
    }

    public function approveRequest($requestId)
    {
        // Проверяем, что текущий пользователь аутентифицирован и является директором
        if (!Auth::check() || !$this->bouncer->is(Auth::user())->a('director')) {
            return redirect()->back()->withErrors(['error' => 'Только директора могут одобрять заявки.']);
        }

        $request = CollaborationRequest::findOrFail($requestId);

        $manager = User::findOrFail($request->manager_id);

        // Устанавливаем пользователю director_id из заявки
        $manager->director_id = $request->director_id;
        $manager->save();

        // Устанавливаем статус заявки на "одобрено"
        $request->status = 'approved';
        $request->save();

        return redirect()->back();
    }

    public function rejectRequest($requestId)
    {
        // Проверяем, что текущий пользователь аутентифицирован и является директором
        if (!Auth::check() || !$this->bouncer->is(Auth::user())->a('director')) {
            return redirect()->back()->withErrors(['error' => 'Только директора могут отклонять заявки.']);
        }

        $request = CollaborationRequest::findOrFail($requestId);

        // Устанавливаем статус заявки на "отклонено"
        $request->status = 'rejected';
        $request->save();

        return redirect()->back();
    }

    public function deleteManager($managerId)
    {
        // Проверяем, что текущий пользователь аутентифицирован и является директором
        if (!Auth::check() || !$this->bouncer->is(Auth::user())->a('director')) {
            return redirect()->back()->withErrors(['error' => 'Только директора могут удалять менеджеров.']);
        }

        $manager = User::findOrFail($managerId);

        $request = CollaborationRequest::where('manager_id', $managerId)->first();
        if ($request) {
            $request->status = 'rejected';
            $request->save();
        }

        // Удаляем связь с директором, устанавливая director_id в null
        $manager->director_id = null;
        $manager->save();

        return redirect()->back();
    }

    public function getCurrentRequest($manager_id)
    {
        $currentRequest = CollaborationRequest::where('manager_id', $manager_id)
            ->with(['manager', 'director'])
            ->first();
        if(!$currentRequest) return null;

        return response()->json($currentRequest);
    }

    public function sendRequest(Request $request)
    {
        // Проверяем, что текущий пользователь является менеджером
        if (!Auth::check() || !$this->bouncer->is(Auth::user())->a('manager')) {
            return redirect()->back()->withErrors(['error' => 'Только менеджеры могут отправлять заявки.']);
        }

        $request->validate([
            'director_email' => 'required|email',
        ]);

        // Находим директора по email
        $director = User::where('email', $request->director_email)->first();

        // Проверяем, что пользователь с таким email существует и является директором
        if (!$director || !$this->bouncer->is($director)->a('director')) {
            return redirect()->back()->withErrors(['director_email' => 'Пользователь с таким email не найден.']);
        }

        // Проверяем, что у менеджера нет активных заявок (pending или approved)
        $activeRequest = CollaborationRequest::where('manager_id', Auth::user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($activeRequest) {
            return redirect()->back()->withErrors(['error' => 'У вас уже есть активная заявка. Дождитесь решения директора или отмените заявку.']);
        }

        $collaborationRequest = CollaborationRequest::create([
            'director_id' => $director->id,
            'manager_id' => Auth::user()->id
        ]);

        // Возвращаем данные о текущей заявке в ответе
        return redirect()->route('collaboration.send-request')->with('success', 'Заявка успешно отправлена.')
            ->with('currentRequest', $collaborationRequest);
    }

    public function deleteRequest($id)
    {
        $collaborationRequest = CollaborationRequest::findOrFail($id);

        // Проверяем, что текущий пользователь является менеджером, который отправил заявку
        if ($collaborationRequest->manager_id !== Auth::user()->id) {
            return redirect()->back()->withErrors(['error' => 'Вы не можете отозвать эту заявку.']);
        }

        $manager = User::findOrFail($collaborationRequest->manager_id);
        // Если связь с директором была, удаляем ее
        if ($manager->director_id !== null) {
            $manager->director_id = null;
            $manager->save();
        }

        $collaborationRequest->delete();

        return redirect()->back()->with('success', 'Заявка успешно отозвана.');
    }

}
