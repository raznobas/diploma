<?php

namespace App\Services;

use App\Models\WazzupUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageSendingService
{
    public function sendMessage(int $mailingId, int $clientId, string $phone, string $message, int $directorId): void
    {
        Log::info('Отправка сообщения.', [
            'mailing_id' => $mailingId,
            'client_id'  => $clientId,
            'phone'      => $phone,
            'message'    => $message,
            'director_id'=> $directorId,
        ]);

        // Получаем данные из модели WazzupUser
        $wazzupUser = WazzupUser::where('director_id', $directorId)->first();

        if (!$wazzupUser) {
            Log::error('WazzupUser не найден для director_id: ' . $directorId);
            return;
        }

        $apiKey = config('services.wazzup.api_key');
        $apiUrl = 'https://api.wazzup24.com/v3/message';

        // Формируем данные для отправки
        $data = [
            'channelId' => $wazzupUser->channel_id,
            'chatType' => 'whatsapp',
            'chatId' => $phone,
            'text' => $message,
            'crmUserId' => $wazzupUser->wazzup_id,
        ];

        try {
            // Отправляем POST-запрос на внешний API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($apiUrl, $data);

            if ($response->successful()) {
                Log::info('Сообщение успешно отправлено.', [
                    'response' => $response->json(),
                ]);
            } else {
                // Логируем ошибку от внешнего API
                $errorResponse = $response->json();
                Log::error('Ошибка при отправке сообщения через API Wazzup', [
                    'error' => $errorResponse['message'] ?? 'Неизвестная ошибка',
                    'details' => $errorResponse,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Исключение при отправке запроса на внешний API Wazzup', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
