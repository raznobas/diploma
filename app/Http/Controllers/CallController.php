<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Client;
use App\Models\Gym;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CallController extends Controller
{
    public function index() {
        if (auth()->user()->director_id === null) {
            return false;
        }
        $calls = Call::where('director_id', auth()->user()->director_id)->paginate(50);

        return Inertia::render('Calls/Index', [
            'calls' => $calls,
        ]);
    }

    public function update(Request $request, Call $call)
    {
        $call->update([
            'client_id' => $request->input('client_id'),
        ]);

        return response()->json(['message' => 'Запись звонка обновлена']);
    }

    public function handleCallEvent(Request $request)
    {
        $vpbxApiKey = $request->header('vpbx_api_key');
        $sign = $request->header('sign');

        if ($vpbxApiKey !== env('MANGO_VPBX_API_KEY') || $sign !== env('MANGO_SIGN')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Получение JSON-данных
        $json = $request->json()->all();

        // Обработка данных звонка
        $callData = $this->parseCallData($json);

        // Создание записи в таблице calls
        $call = Call::create($callData);

        return response()->json(['status' => 'success', 'call_id' => $call->id], 200);
    }

    private function parseCallData(array $json): array
    {
        $toPhone = $json['to']['number'];

        // Поиск записи по телефону
        $gym = Gym::where('phone', $toPhone)
            ->orWhere('phone', $this->swapSevenAndEight($toPhone))
            ->first();

        $directorId = $gym ? $gym->director_id : null;

        $fromPhone = $json['from']['number'];

        // Поиск клиента или лида по номеру с 7 или 8
        $client = Client::where('director_id', $directorId)
            ->where('phone', $fromPhone)
            ->orWhere('phone', $this->swapSevenAndEight($fromPhone))
            ->first();

        return [
            'phone' => $json['from']['number'],
            'call_time' => Carbon::createFromTimestamp($json['timestamp']),
            'status' => $json['call_state'] === 'Disconnected' ? 'missed' : 'processing',
            'client_id' => $client ? $client->id : null,
            'director_id' => $directorId,
        ];
    }

    function swapSevenAndEight($phone)
    {
        if (strlen($phone) === 11) {
            if ($phone[0] === '7') {
                return '8' . substr($phone, 1);
            } elseif ($phone[0] === '8') {
                return '7' . substr($phone, 1);
            }
        }
        return $phone;
    }
}
