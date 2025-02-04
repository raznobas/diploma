<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WhatsappController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->director_id === null) {
            return false;
        }

        return Inertia::render('Whatsapp/Index');
    }

    public function getIframeUrl(Request $request)
    {
        if (auth()->user()->director_id === null) {
            return false;
        }

        $apiKey = config('services.wazzup.api_key');

        $data = $request->validate([
            'user' => 'required|array',
            'user.id' => 'required|string',
            'user.name' => 'required|string',
            'scope' => 'required|string',
            'filter' => 'array',
            'filter.*.chatType' => 'string',
            'filter.*.chatId' => 'string',
            'filter.*.name' => 'string',
        ]);

        try {
            // Отправляем POST-запрос на внешний API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.wazzup24.com/v3/iframe', $data);

            if ($response->successful()) {
                // Возвращаем URL из ответа
                return response()->json(['url' => $response->json()['url']]);
            } else {
                // Возвращаем ошибку от внешнего API
                $errorResponse = $response->json();
                return response()->json([
                    'error' => $errorResponse['message'] ?? 'Ошибка при получении URL',
                    'details' => $errorResponse, // Дополнительные детали ошибки
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при отправке запроса на внешний API',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function handleWazzupMessageEvent(Request $request) {
        Log::info('Webhook data received:', $request->all());
        $webhookData = $request->json()->all();

        if (isset($webhookData['messages']) && is_array($webhookData['messages'])) {
            $messagesToInsert = [];

            foreach ($webhookData['messages'] as $message) {
                try {
                    $dateTime = new \DateTime($message['dateTime']);
                    $mysqlDateTime = $dateTime->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::error('Failed to parse dateTime:', [
                        'messageId' => $message['messageId'],
                        'dateTime' => $message['dateTime'],
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                $messagesToInsert[] = [
                    'messageId' => $message['messageId'],
                    'channelId' => $message['channelId'],
                    'chatType' => $message['chatType'],
                    'chatId' => $message['chatId'],
                    'dateTime' => $mysqlDateTime,
                    'type' => $message['type'],
                    'status' => $message['status'],
                    'text' => $message['text'] ?? null,
                    'contentUri' => $message['contentUri'] ?? null,
                    'authorId' => $message['authorId'] ?? null,
                    'authorName' => $message['authorName'] ?? null,
                    'isEcho' => $message['isEcho'] ?? false,
                    'contact_name' => $message['contact']['name'] ?? null,
                    'contact_username' => $message['contact']['username'] ?? null,
                    'contact_phone' => $message['contact']['phone'] ?? null,
                    'error' => isset($message['error']) ? json_encode($message['error']) : null,
                    'quotedMessage' => isset($message['quotedMessage']) ? json_encode($message['quotedMessage']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($messagesToInsert)) {
                DB::transaction(function () use ($messagesToInsert) {
                    DB::table('chat_message_history')->insertOrIgnore($messagesToInsert);
                });
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
