<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Silber\Bouncer\Bouncer;

class ImportSalesFromXlsx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-sales-from-xlsx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = storage_path('20241003подготовкакЦРМ(1).xlsx');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Пропустить заголовки
        $headers = array_shift($rows);

        // Определите индекс столбца, содержащего director_id (предположим, что это столбец F)
        $directorIdColumnIndex = array_search('director_id', $headers);

        if ($directorIdColumnIndex === false) {
            $this->error("Column 'director_id' not found in the headers.");
            return;
        }

        // Получите значение director_id из первой строки
        $directorId = $rows[0][$directorIdColumnIndex];

        if (empty($directorId)) {
            $this->error("Director ID is empty in the first row.");
            return;
        }

        // Проверка, что director_id действительно по роли директор
        $director = User::find($directorId);
        $bouncer = app(Bouncer::class);

        if (!$director || !$bouncer->is($director)->a('director')) {
            $this->error("Director with ID $directorId is not director.");
            return;
        }

        $this->info("Importing for director ID: " . $directorId);

        $importedCount = 0;
        $skippedCount = 0;
        $skippedRows = [];
        $clientCount = 0; // Счетчик для созданных клиентов
        $categoryCount = 0; // Счетчик для созданных категорий

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                try {
                    // Проверка, что поле name не пустое
                    if (empty($row[1])) {
                        $skippedCount++;
                        $skippedRows[] = $index + 2;
                        continue;
                    }
                    // Создаем или находим клиента
                    $client = Client::firstOrCreate(
                        [
                            'surname' => $row[0],
                            'name' => $row[1],
                            'director_id' => $directorId, // Используем значение из таблицы
                        ],
                        [
                            'patronymic' => $row[2],
                            'phone' => $row[3],
                            'is_lead' => false,
                        ]
                    );

                    // Увеличиваем счетчик клиентов
                    if ($client->wasRecentlyCreated) {
                        $clientCount++;
                    }

                    // Преобразуем строки с датами в объекты DateTime
                    $saleDate = \DateTime::createFromFormat('d.m.Y', $row[4]);
                    $subscriptionStartDate = $row[14] !== null ? \DateTime::createFromFormat('d.m.Y', $row[14]) : null;
                    $subscriptionEndDate = $row[15] !== null ? \DateTime::createFromFormat('d.m.Y', $row[15]) : null;

                    // Преобразуем значение service_or_product в 'service' или 'product' без учета регистра
                    $serviceOrProduct = null;
                    $lowercaseServiceOrProduct = mb_strtolower($row[5], 'UTF-8');
                    if ($lowercaseServiceOrProduct === 'услуга') {
                        $serviceOrProduct = 'service';
                    } elseif ($lowercaseServiceOrProduct === 'товар') {
                        $serviceOrProduct = 'product';
                    }

                    // Преобразуем значение service_type в соответствующий формат
                    $serviceType = $row[7] !== null ? $this->mapServiceType($row[7]) : null;

                    // Проверяем, что все необходимые поля заполнены
                    if ($serviceOrProduct === null || $saleDate === false) {
                        $skippedCount++;
                        $skippedRows[] = $index + 2; // +2, потому что индекс начинается с 0, а строки в Excel с 1, и мы пропустили заголовки
                        continue;
                    }

                    // Создаем или находим категории
                    $categories = [
                        ['name' => $row[6], 'type' => 'sport_type'],
                        ['name' => $row[8], 'type' => 'product_type'],
                        ['name' => $row[9], 'type' => 'subscription_duration'],
                        ['name' => $row[10], 'type' => 'visits_per_week'],
                        ['name' => $row[11], 'type' => 'training_count'],
                        ['name' => $row[12], 'type' => 'trainer_category'],
                        ['name' => $row[13], 'type' => 'trainer'],
                        ['name' => $row[18], 'type' => 'pay_method'],
                    ];

                    foreach ($categories as $categoryData) {
                        if (!empty($categoryData['name'])) {
                            $category = Category::firstOrCreate(
                                [
                                    'director_id' => $directorId,
                                    'name' => $categoryData['name'],
                                    'type' => $categoryData['type'],
                                ]
                            );

                            if ($category->wasRecentlyCreated) {
                                $categoryCount++;
                            }
                        }
                    }

                    // Создаем продажу
                    $sale = new Sale();
                    $sale->client_id = $client->id;
                    $sale->sale_date = $saleDate;
                    $sale->service_or_product = $serviceOrProduct;
                    $sale->sport_type = $row[6];
                    $sale->service_type = $serviceType;
                    $sale->product_type = $row[8];

                    // Проверяем и заменяем значение, если оно равно '0,03'
                    $subscriptionDuration = $row[9] === '0,03' ? '0.03' : $row[9];
                    $sale->subscription_duration = $subscriptionDuration;

                    $sale->visits_per_week = $row[10];
                    $sale->training_count = $row[11];
                    $sale->trainer_category = $row[12];
                    $sale->trainer = $row[13];
                    $sale->subscription_start_date = $subscriptionStartDate;
                    $sale->subscription_end_date = $subscriptionEndDate;
                    $sale->cost = $row[16] !== null ? $row[16] : 0;
                    $sale->paid_amount = $row[17] !== null ? $row[17] : 0;
                    $sale->pay_method = $row[18];
                    $sale->comment = $row[19];

                    $sale->director_id = $directorId; // Используем значение из таблицы

                    $sale->save();
                    $importedCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("An error on row " . ($index + 2) . ": " . $e->getMessage());
                    return;
                }
            }

            DB::commit();

            $this->info("Sales data imported successfully.");
            $this->info("Imported rows: $importedCount");
            $this->info("Skipped rows: $skippedCount");
            $this->info("Total clients created: $clientCount"); // Вывод количества созданных клиентов
            $this->info("Total categories created: $categoryCount"); // Вывод количества созданных категорий

            if (!empty($skippedRows)) {
                $this->info("Skipped rows numbers: " . implode(', ', $skippedRows));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("An error occurred: " . $e->getMessage());
        }
    }

    private function mapServiceType(string $serviceType): ?string
    {
        $serviceTypeMap = [
            'пробная' => 'trial',
            'групповая' => 'group',
            'минигруппа' => 'minigroup',
            'индивидуальная' => 'individual',
            'сплит' => 'split',
        ];

        // Преобразуем входное значение в нижний регистр
        $lowercaseServiceType = mb_strtolower($serviceType, 'UTF-8');

        return $serviceTypeMap[$lowercaseServiceType] ?? null;
    }
}
