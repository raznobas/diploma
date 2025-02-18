<?php

use App\Models\Client;
use App\Models\LeadAppointment;
use App\Models\MassMailing;
use App\Models\Sale;
use App\Services\MassMailingClientService;
use App\Services\MessageSendingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

Artisan::command('appointments:update-status', function () {
    $now = Carbon::now()->startOfDay();

    $updated = LeadAppointment::whereDate('training_date', '<', $now)
        ->where('status', 'scheduled')
        ->update(['status' => 'no_show']);

    Log::info('Lead appointments statuses updated.', ['updated_count' => $updated]);

    $this->info('Lead appointments statuses updated successfully.');
})->purpose('Update lead appointments status to no_show if training_date has passed and status was scheduled')->dailyAt('00:00');

Artisan::command('send-message-command:process', function (MassMailingClientService $clientService, MessageSendingService $messageService) {
    $currentDate = Carbon::now();

    // Получаем все настройки рассылки для блока 'trials'
    $mailings = MassMailing::where('block', 'trials')->get();
    Log::info('Найдено настроек рассылки для блока trials.', ['mailings_count' => $mailings->count()]);
    $this->info("Найдено настроек рассылки: " . $mailings->count());

    // Создаем ключ для кэша, чтобы отслеживать отправленные сообщения за сегодня
    $cacheKey = 'sent_messages_' . $currentDate->toDateString();

    // Получаем список клиентов, которые уже получили сообщение сегодня
    $sentClients = Cache::get($cacheKey, []);

    foreach ($mailings as $mailing) {
        // Директор берется из записи mass_mailings
        $directorId = $mailing->director_id;
        Log::info('Обработка рассылки.', ['mailing_id' => $mailing->id, 'director_id' => $directorId]);
        $this->info("Обрабатывается рассылка ID: {$mailing->id} для директора ID: {$directorId}");

        // Расшифровываем selected_categories (JSON)
        $selectedCategories = json_decode($mailing->selected_categories, true);
        $sportTypes = $selectedCategories['sport_type'] ?? [];

        // Получаем клиентов для рассылки
        $trialClients = $clientService->getTrialClients($directorId, $sportTypes, $currentDate);
        Log::info('Клиенты для рассылки отобраны.', ['trial_clients_count' => $trialClients->count()]);
        $this->info("Найдено клиентов для рассылки: " . $trialClients->count());

        // 4. Для каждого клиента вычисляем training_date на основе даты первой пробной продажи
        $trialClients->each(function ($client) {
            $client->training_date = $client->sales->first()->sale_date ?? null;
        });

        // Расшифровываем смещения отправки (send_offset хранится как JSON-массив, например, ["after1day", "after1week"])
        $sendOffsets = json_decode($mailing->send_offset, true);

        // 5. Для каждого клиента и каждого смещения вычисляем дату отправки и, если условие выполнено,
        // вызываем отправку сообщения
        foreach ($trialClients as $client) {
            if (!$client->training_date) {
                continue;
            }
            $trialDate = Carbon::parse($client->training_date);

            foreach ($sendOffsets as $offset) {
                $scheduleDate = getScheduleDate($trialDate, $offset);

                // Если вычисленная дата отправки совпадает с сегодняшней
                if ($scheduleDate && $scheduleDate->isSameDay($currentDate)) {
                    // Проверяем, не получал ли клиент уже сообщение сегодня
                    if (!in_array($client->id, $sentClients)) {
                        Log::info('client training date: ' . $client->training_date);
                        // Вызываем сервис
                        $messageService->sendMessage(
                            $mailing->id,
                            $client->id,
                            $client->phone,
                            $mailing->message_text,
                            $directorId
                        );

                        // Добавляем клиента в список отправленных
                        $sentClients[] = $client->id;
                    }
                }
            }
        }
    }

    // Сохраняем список отправленных клиентов в кэше
    Cache::put($cacheKey, $sentClients, now()->endOfDay());

    $this->info('Message processed successfully.');
    Log::info('Обработка массовых рассылок завершена успешно.');
})->purpose('Process messages based on mass_mailings settings')->dailyAt('16:00');

// Вспомогательная функция для вычисления даты отправки на основе ключевой даты и смещения
function getScheduleDate(Carbon $keyDate, string $offset): ?Carbon
{
    return match ($offset) {
        'before1day'  => $keyDate->copy()->subDay(),
        'after1day'   => $keyDate->copy()->addDay(),
        'after1week'  => $keyDate->copy()->addWeek(),
        'after1month' => $keyDate->copy()->addMonth(),
        default       => null,
    };
}


