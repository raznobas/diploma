<?php

namespace App\Http\Controllers;

use Silber\Bouncer\Bouncer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isAn('admin')) {
            return redirect()->back()->withErrors(['error' => 'Вы не администратор']);
        }

        $sortByRole = $request->input('role', null);
        $sortByClients = $request->input('clients', null);
        $sortBySales = $request->input('sales', null);

        $query = User::where('id', '!=', $user->id)->with('roles');

        if ($sortByRole) {
            $query->whereHas('roles', function ($query) use ($sortByRole) {
                $query->where('name', $sortByRole);
            });
        }

        $query->withCount('clients')->withCount('sales');

        // Сортировка по клиентам
        if ($sortByClients) {
            $query->withCount('clients')->orderBy('clients_count', $sortByClients);
        }

        // Сортировка по продажам
        if ($sortBySales) {
            $query->withCount('sales')->orderBy('sales_count', $sortBySales);
        }

        $users = $query->paginate(50);

        // Преобразуем данные для передачи
        $users->getCollection()->transform(function ($user) {
            $totalClients = $user->clients_count;
            $totalSales = $user->sales_count;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => $user->roles->pluck('name')->toArray(),
                'total_clients' => $totalClients,
                'total_sales' => $totalSales,
            ];
        });

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'sortByRole' => $sortByRole,
            'sortByClients' => $sortByClients,
            'sortBySales' => $sortBySales,
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (!$currentUser->isAn('admin') || $currentUser->director_id != null) {
            return redirect()->back()->withErrors(['error' => 'Вы не администратор']);
        }

        // Проверка, что пользователь, которого пытаются удалить, не является администратором
        if ($user->isAn('admin')) {
            return redirect()->back()->withErrors(['error' => 'Нельзя удалить администратора']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Пользователь успешно удален');
    }
}
