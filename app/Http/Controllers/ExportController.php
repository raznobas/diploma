<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index() {
        $directorId = auth()->user()->director_id;

        if (auth()->user()->id !== $directorId) {
            return redirect()->back()->withErrors(['error' => 'Экспорт продаж доступен только директору.']);
        }

        $categories = Category::where('director_id', auth()->user()->director_id)->get();

        return Inertia::render('Excel/Export', [
            'categories' => $categories,
        ]);
    }

    public function export(Request $request)
    {
        $directorId = auth()->user()->director_id;

        if (auth()->user()->id !== $directorId) {
            return redirect()->back()->withErrors(['error' => 'Экспорт продаж доступен только директору.']);
        }

        // Валидация входных данных
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'categories' => 'nullable|array',
            'export_all_categories' => 'nullable|boolean', // Параметр для экспорта всех категорий
        ]);

        // Получаем данные из запроса
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $selectedCategories = $request->input('categories', []);
        $exportAllCategories = $request->input('export_all_categories', false);

        $directorId = auth()->user()->director_id;

        // Фильтруем данные по диапазону дат и director_id
        $query = Sale::where('director_id', $directorId)
            ->whereBetween('sale_date', [$startDate, $endDate]);

        // Если не выбран экспорт всех категорий, применяем фильтр по категориям
        if (!$exportAllCategories && !empty($selectedCategories)) {
            $query->where(function ($q) use ($selectedCategories) {
                foreach ($selectedCategories as $type => $names) {
                    if (!empty($names)) {
                        $q->whereIn($type, $names);
                    }
                }
            });
        }

        $query->with('client:id,name,surname,patronymic,phone');

        // Получаем отфильтрованные данные
        $data = $query->get();

        // Проверяем, есть ли данные
        if ($data->isEmpty()) {
            return response()->json([
                'error' => 'Нет данных для экспорта по выбранным параметрам.',
            ], 404);
        }

        // Создаем Excel-файл
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Продажи');

        // Заголовки столбцов
        $headers = [
            'Фамилия',
            'Имя',
            'Отчество',
            'Телефон',
            'Дата продажи',
            'Услуга или товар',
            'Вид спорта',
            'Вид услуги',
            'Вид товара',
            'Длительность абонемента',
            'Посещений в неделю',
            'Кол-во тренировок',
            'Категория',
            'Тренер',
            'Начало абонемента',
            'Конец абонемента',
            'Стоимость',
            'Сумма оплаченная',
            'Способ оплаты',
            'Комментарий',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $serviceTypeLabels = [
            'trial' => 'Пробная',
            'group' => 'Групповая',
            'minigroup' => 'Минигруппа',
            'individual' => 'Индивидуальная',
            'split' => 'Сплит',
        ];

        // Заполняем данные
        $row = 2;
        foreach ($data as $item) {
            $serviceOrProduct = ($item->service_or_product === 'product') ? 'Товар' : 'Услуга';
            $serviceType = $serviceTypeLabels[$item->service_type] ?? null;
            $saleDateFormatted = Carbon::parse($item->sale_date)->format('d.m.Y');
            $subscriptionStartFormatted = Carbon::parse($item->subscription_start_date)->format('d.m.Y');
            $subscriptionEndFormatted = Carbon::parse($item->subscription_end_date)->format('d.m.Y');

            $client = $item->client;

            $rowData = [
                $client->surname,
                $client->name,
                $client->patronymic,
                $client->phone,
                $saleDateFormatted,
                $serviceOrProduct,
                $item->sport_type,
                $serviceType,
                $item->product_type,
                $item->subscription_duration,
                $item->visits_per_week,
                $item->training_count,
                $item->category,
                $item->trainer,
                $subscriptionStartFormatted,
                $subscriptionEndFormatted,
                $item->cost,
                $item->paid_amount,
                $item->pay_method,
                $item->comment,
            ];

            $sheet->fromArray([$rowData], null, 'A' . $row); // Записываем строку данных
            $row++;
        }

        // Возвращаем файл для скачивания
        $writer = new Xlsx($spreadsheet);

        // Формируем имя файла с датой и временем
        $currentDateTime = now()->format('Y-m-d_H-i-s'); // Формат: Год-Месяц-День_Часы-Минуты-Секунды
        $fileName = "export_{$currentDateTime}.xlsx"; // Имя файла с датой и временем

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment;filename={$fileName}");
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
