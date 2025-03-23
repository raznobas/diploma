<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryCost;
use App\Models\CategoryCostAdditional;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Silber\Bouncer\Bouncer;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index()
    {
        $this->authorize('manage-categories');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $categories = Category::where('director_id', auth()->user()->director_id)->get();
        $categoryCosts = CategoryCost::with('additionalCosts')
            ->where('director_id', auth()->user()->director_id)
            ->get();

        return Inertia::render('Director/Categories/Index', [
            'categories' => $categories,
            'categoryCosts' => $categoryCosts,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-categories');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'director_id' => 'required|exists:users,id',
        ]);

        // Проверка на уникальность категории
        $existingCategory = Category::where('director_id', auth()->user()->director_id)
            ->where('name', $request->name)
            ->where('type', $request->type)
            ->first();

        if ($existingCategory) {
            return redirect()->back()->withErrors(['error' => 'Категория с таким именем и типом уже существует.']);
        }

        Category::create([
            'director_id' => auth()->user()->director_id,
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('categories.index');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('manage-categories');

        if ($category->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на редактирование этой категории.']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Проверка на уникальность категории
        $existingCategory = Category::where('director_id', auth()->user()->director_id)
            ->where('name', $request->name)
            ->where('type', $request->type)
            ->first();

        if ($existingCategory) {
            return redirect()->back()->withErrors(['error' => 'Категория с таким именем и типом уже существует.']);
        }

        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $this->authorize('manage-categories');

        if ($category->director_id !== auth()->user()->director_id) {
            return redirect()->route('categories.index')->withErrors(['error' => 'У вас нет прав на удаление этой категории.']);
        }

        $category->delete();

        return redirect()->route('categories.index');
    }
}
