<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryCost;
use App\Models\CategoryCostAdditional;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Silber\Bouncer\Bouncer;

class CategoryCostController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function store(Request $request)
    {
        $this->authorize('manage-categories');

        $request->validate([
            'mainCategory.type' => 'required|string',
            'mainCategory.option' => 'required|string',
            'additionalCategories' => 'array',
            'additionalCategories.*.type' => 'required|string',
            'additionalCategories.*.option' => 'required|string',
            'cost' => 'required|numeric',
            'director_id' => 'required|exists:users,id',
        ]);

        // Найти основную категорию
        $mainCategory = Category::where('name', $request->input('mainCategory.option'))
            ->where('type', $request->input('mainCategory.type'))
            ->where('director_id', auth()->user()->director_id)
            ->firstOrFail();

        // Создать новую запись в таблице category_costs
        $categoryCost = CategoryCost::create([
            'main_category_id' => $mainCategory->id,
            'cost' => $request->input('cost'),
            'director_id' => auth()->user()->director_id,
        ]);

        // Добавить дополнительные категории
        foreach ($request->input('additionalCategories') as $additionalCategory) {
            $additionalCategoryModel = Category::where('name', $additionalCategory['option'])
                ->where('type', $additionalCategory['type'])
                ->where('director_id', auth()->user()->director_id)
                ->firstOrFail();

            CategoryCostAdditional::create([
                'category_cost_id' => $categoryCost->id,
                'additional_category_id' => $additionalCategoryModel->id,
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Стоимость категорий успешно сохранена.');
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage-categories');

        $request->validate([
            'mainCategory.type' => 'required|string',
            'mainCategory.option' => 'required|string',
            'additionalCategories' => 'array',
            'additionalCategories.*.type' => 'required|string',
            'additionalCategories.*.option' => 'required|string',
            'cost' => 'required|numeric',
            'director_id' => 'required|exists:users,id',
        ]);

        // Найти основную категорию
        $mainCategory = Category::where('name', $request->input('mainCategory.option'))
            ->where('type', $request->input('mainCategory.type'))
            ->where('director_id', auth()->user()->director_id)
            ->firstOrFail();

        // Найти существующую запись в таблице category_costs
        $categoryCost = CategoryCost::findOrFail($id);

        if ($categoryCost->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на редактирование этой связки.']);
        }

        // Обновить основную категорию и стоимость
        $categoryCost->update([
            'main_category_id' => $mainCategory->id,
            'cost' => $request->input('cost'),
        ]);

        // Удалить все существующие дополнительные категории
        $categoryCost->additionalCosts()->delete();

        // Добавить новые дополнительные категории
        foreach ($request->input('additionalCategories') as $additionalCategory) {
            $additionalCategoryModel = Category::where('name', $additionalCategory['option'])
                ->where('type', $additionalCategory['type'])
                ->where('director_id', auth()->user()->director_id)
                ->firstOrFail();

            CategoryCostAdditional::create([
                'category_cost_id' => $categoryCost->id,
                'additional_category_id' => $additionalCategoryModel->id,
            ]);
        }

        return redirect()->back()->with('success', 'Стоимость категорий успешно обновлена.');
    }

    public function destroy($id)
    {
        $this->authorize('manage-categories');

        $categoryCost = CategoryCost::findOrFail($id);

        if ($categoryCost->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление этой связки.']);
        }

        $categoryCost->delete();

        return redirect()->back()->with('success', 'Сборка стоимости успешно удалена.');
    }
}
