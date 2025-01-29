<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
}
