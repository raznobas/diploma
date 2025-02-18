<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MassMailing;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MassMailingController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->director_id === null) {
            return false;
        }

        $categories = Category::where('director_id', auth()->user()->director_id)
            ->where('type', 'sport_type')
            ->get();

        $massMailings = MassMailing::where('director_id', auth()->user()->director_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('MassMailing/Index', [
            'categories' => $categories,
            'massMailings' => $massMailings
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->director_id === null) {
            return false;
        }

        $validatedData = $request->validate([
            'block' => 'required|string',
            'selected_categories' => 'nullable|json',
            'message_text' => 'required|string',
            'send_offset' => 'required|json',
            'director_id' => 'required|exists:users,id',
        ]);

        MassMailing::create($validatedData);

        return redirect()->back()->with('success', 'Рассылка успешно сохранена!');
    }

    public function destroy(MassMailing $massMailing)
    {
        if ($massMailing->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление этой задачи.']);
        }

        $massMailing->delete();

        return redirect()->back()->with('success', "Настройка успешно удалена");
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->director_id === null) {
            return false;
        }

        $validatedData = $request->validate([
            'block' => 'required|string',
            'selected_categories' => 'nullable|json',
            'message_text' => 'required|string',
            'send_offset' => 'required|json',
            'director_id' => 'required|exists:users,id',
        ]);

        $massMailing = MassMailing::where('id', $id)
            ->where('director_id', auth()->user()->director_id)
            ->first();

        if (!$massMailing) {
            return redirect()->back()->withErrors('Настройка рассылки не найдена');
        }

        // Обновляем данные
        $massMailing->update($validatedData);

        return redirect()->back()->with('success', 'Настройка рассылки успешно обновлена!');
    }

}
