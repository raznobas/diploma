<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Client;
use App\Models\Gym;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CallController extends Controller
{
    public function index() {
        if (auth()->user()->director_id === null) {
            return false;
        }
        $calls = Call::where('director_id', auth()->user()->director_id)
            ->orderBy('call_time', 'desc')
            ->paginate(50);

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
        // Логируем получение запроса
        Log::info('Получен запрос на обработку звонка', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $jsonString = $request->input('json');

        // Декодируем JSON-строку в массив
        $json = json_decode($jsonString, true);

        // Логируем данные звонка
        Log::info('Данные звонка получены', [
            'entry_id' => $json['entry_id'],
            'json' => $json,
        ]);

        // Не записываем звонки от сотрудников клиентам
        if (isset($json['from']['extension'])) {
            return;
        }

        // Сохраняем только звонки с location = abonent
        if ($json['location'] !== 'abonent') {
            Log::info('Запись пропущена, так как location не равен abonent', [
                'entry_id' => $json['entry_id'],
                'location' => $json['location'],
            ]);
            return;
        }

        // Обработка данных звонка
        $callData = $this->parseCallData($json);

        // Поиск существующей записи по entry_id
        $call = Call::where('entry_id', $json['entry_id'])->first();

        if ($call) {
            if (!in_array($call->status, ['answered', 'missed'])) {
                // Обновляем статус только если текущий статус не является завершенным
                $call->update(['status' => $json['call_state']]);

                Log::info('Запись звонка успешно обновлена', [
                    'id' => $call->id,
                    'new_status' => $json['call_state'],
                ]);
            }
        } else {
            // Если записи нет, создаем новую
            $call = Call::create($callData);

            // Логируем успешное создание записи
            Log::info('Запись звонка успешно создана', ['call_id' => $call->id]);
        }
    }

    public function handleCallSummary(Request $request)
    {
        // Логируем получение запроса
        Log::info('Получен запрос на обработку завершения звонка', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $jsonString = $request->input('json');

        // Декодируем JSON-строку в массив
        $json = json_decode($jsonString, true);

        // Логируем данные завершения звонка
        Log::info('Данные завершения звонка получены', [
            'entry_id' => $json['entry_id'],
            'json' => $json,
        ]);

        $status = ($json['entry_result'] == 1) ? 'answered' : 'missed';

        $duration = $json['end_time'] - $json['create_time'];

        // Поиск существующей записи по entry_id
        $call = Call::where('entry_id', $json['entry_id'])->first();

        if ($call) {
            // Если запись существует, обновляем её поля
            $call->update([
                'duration' => $duration,
                'status' => $status,
            ]);

            // Логируем успешное обновление записи
            Log::info('Запись звонка успешно обновлена (завершение)', [
                'id' => $call->id,
            ]);
        } else {
            // Если записи нет, логируем ошибку
            Log::warning('Запись звонка не найдена для обновления', [
                'entry_id' => $json['entry_id'],
            ]);
        }
    }

    private function parseCallData(array $json): array
    {
        $toPhone = isset($json['to']['extension'])
            ? $json['to']['line_number'] // Используем line_number, если есть extension
            : $json['to']['number']; // Иначе используем number

        // Поиск записи по телефону
        $gym = Gym::where('phone', $toPhone)
            ->orWhere('phone', $this->swapSevenAndEight($toPhone))
            ->first();

        $directorId = $gym ? $gym->director_id : 3; // ID тестового директора

        // Логируем результат поиска зала
        Log::info('Результат поиска зала', [
            'gym' => $gym,
            'director_id' => $directorId,
        ]);

        $fromPhone = $json['from']['number'];

        // Поиск клиента или лида по номеру с 7 или 8
        $client = Client::where('director_id', $directorId)
            ->where(function ($query) use ($fromPhone) {
                $query->where('phone', $fromPhone)
                    ->orWhere('phone', $this->swapSevenAndEight($fromPhone));
            })
            ->first();

        // Логируем результат поиска клиента
        Log::info('Результат поиска клиента', ['client' => $client]);

        $callData = [
            'entry_id' => $json['entry_id'],
            'phone_from' => $json['from']['number'],
            'phone_to' => $toPhone,
            'call_time' => Carbon::createFromTimestamp($json['timestamp']),
            'status' => $json['call_state'],
            'client_id' => $client ? $client->id : null,
            'director_id' => $directorId,
        ];

        // Логируем возвращаемые данные
        Log::info('Данные для создания записи звонка', ['callData' => $callData]);

        return $callData;
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
