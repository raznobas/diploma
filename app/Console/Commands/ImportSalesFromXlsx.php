<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $filePath = storage_path('testImport.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Пропустить заголовки
        array_shift($rows);

        $importedCount = 0;
        $skippedCount = 0;
        $skippedRows = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                // Создаем или находим клиента
                $client = Client::firstOrCreate([
                    'surname' => $row[0],
                    'name' => $row[1],
                    'patronymic' => $row[2],
                    'phone' => $row[3],
                    'is_lead' => false,
                    'director_id' => 3, // поменять это поле в зависимости от директора
                ]);

                // Преобразуем строки с датами в объекты DateTime
                $saleDate = \DateTime::createFromFormat('d.m.Y', $row[4]);
                $subscriptionStartDate = $row[14] !== null ? \DateTime::createFromFormat('d.m.Y', $row[14]) : null;
                $subscriptionEndDate = $row[15] !== null ? \DateTime::createFromFormat('d.m.Y', $row[15]) : null;

                // Преобразуем значение service_or_product в 'service' или 'product'
                if ($row[5] === 'Услуга') {
                    $serviceOrProduct = 'service';
                } elseif ($row[5] === 'Товар') {
                    $serviceOrProduct = 'product';
                } else {
                    $serviceOrProduct = null;
                }

                // Преобразуем значение service_type в соответствующий формат
                $serviceType = $row[7] !== null ? $this->mapServiceType($row[7]) : null;

                // Проверяем, что все необходимые поля заполнены
                if ($serviceOrProduct === null || $saleDate === false) {
                    $skippedCount++;
                    $skippedRows[] = $index + 2; // +2, потому что индекс начинается с 0, а строки в Excel с 1, и мы пропустили заголовки
                    continue;
                }

                // Создаем продажу
                $sale = new Sale();
                $sale->client_id = $client->id;
                $sale->sale_date = $saleDate;
                $sale->service_or_product = $serviceOrProduct;
                $sale->sport_type = $row[6];
                $sale->service_type = $serviceType;
                $sale->product_type = $row[8];
                $sale->subscription_duration = $row[9];
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

                $sale->director_id = 3; // поменять это поле в зависимости от директора

                $sale->save();
                $importedCount++;
            }

            DB::commit();

            $this->info("Sales data imported successfully.");
            $this->info("Imported rows: $importedCount");
            $this->info("Skipped rows: $skippedCount");

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
            'Пробная' => 'trial',
            'Групповая' => 'group',
            'Минигруппа' => 'minigroup',
            'Индивидуальная' => 'individual',
            'Сплит' => 'split',
        ];

        return $serviceTypeMap[$serviceType] ?? null;
    }
}
