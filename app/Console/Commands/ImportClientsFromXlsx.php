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

class ImportClientsFromXlsx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-clients-from-xlsx {file : The path to the XLSX file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сырой функционал импорта только клиентов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Получаем путь к файлу из аргумента команды
        $filePath = $this->argument('file');

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

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                try {
                    // Если поле name пустое, устанавливаем значение "Тренер"
                    if (empty($row[1])) {
                        $row[1] = "Тренер";
                    }

                    // Обработка номера телефона
                    $clientPhone = $row[3] ?? null;
                    if ($clientPhone) {
                        // Очищаем номер телефона от лишних символов (убираем все, кроме цифр)
                        $clientPhone = preg_replace('/[^0-9]/', '', $clientPhone);

                        // Приводим номер к формату 79123456789
                        if (strlen($clientPhone) === 10 && $clientPhone[0] === '9') {
                            $clientPhone = '7' . $clientPhone; // Добавляем 7 в начало номера
                        } elseif (strlen($clientPhone) === 11 && $clientPhone[0] === '8') {
                            $clientPhone = '7' . substr($clientPhone, 1); // Заменяем 8 на 7 в начале номера
                        }
                    }

                    // Обработка instagram: обрезаем значение, если оно превышает лимит
                    $instagram = $row[8] ?? null;
                    if (mb_strlen($instagram, 'UTF-8') > 255) {
                        $instagram = mb_substr($instagram, 0, 255, 'UTF-8');
                    }

                    // Создаем или находим клиента
                    $client = Client::firstOrCreate(
                        [
                            'surname' => $row[0],
                            'name' => $row[1],
                            'phone' => $clientPhone,
                            'director_id' => $directorId, // Используем значение из таблицы
                        ],
                        [
                            'patronymic' => $row[2] ?? null,
                            'is_lead' => false,
                            'address' => $row[5] ?? null,
                            'workplace' => $row[6] ?? null,
                            'email' => $row[7] ?? null,
                            'instagram' => $instagram, // Используем обрезанное значение
                            'telegram' => $row[9] ?? null,
                        ]
                    );

                    // Увеличиваем счетчик клиентов
                    if ($client->wasRecentlyCreated) {
                        $clientCount++;
                    }

                    $importedCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("An error on row " . ($index + 2) . ": " . $e->getMessage());
                    return;
                }
            }

            $this->info("Imported rows: $importedCount");
            $this->info("Skipped rows: $skippedCount");
            $this->info("Total clients created: $clientCount"); // Вывод количества созданных клиентов

            if (!empty($skippedRows)) {
                $this->info("Skipped rows numbers: " . implode(', ', $skippedRows));
            }

            if ($this->confirm('Are you sure you want to import this data?', false)) {
                DB::commit();
                $this->info("Sales data imported successfully.");
            } else {
                DB::rollBack();
                $this->info("Import operation cancelled by user.");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
