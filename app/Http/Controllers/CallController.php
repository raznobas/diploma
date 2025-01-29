<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Client;
use App\Models\Gym;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Обработка данных звонка
        $callData = $this->parseCallData($json);

        // Начинаем транзакцию для безопасного изменения данных
        DB::transaction(function () use ($json, $callData) {
            // Поиск существующей записи по entry_id с блокировкой на обновление
            $call = Call::where('entry_id', $json['entry_id'])->lockForUpdate()->first();

            if ($call) {
                // Проверяем, что новое событие имеет большее или равное значение seq
                if ($json['seq'] >= $call->last_seq) {
                    // Если текущий статус не является завершенным, обновляем его
                    if (!in_array($call->status, ['answered', 'missed'])) {
                        $call->update([
                            'status' => $json['call_state'],
                            'last_seq' => $json['seq'], // Обновляем last_seq
                        ]);

                        Log::info('Запись звонка успешно обновлена', [
                            'id' => $call->id,
                            'new_status' => $json['call_state'],
                            'new_seq' => $json['seq'],
                        ]);
                    } else {
                        Log::info('Статус звонка не обновлен, так как он уже завершен', [
                            'id' => $call->id,
                            'current_status' => $call->status,
                            'new_status (not_updated)' => $json['call_state'],
                        ]);
                    }
                } else {
                    Log::info('Событие проигнорировано, так как оно устарело', [
                        'id' => $call->id,
                        'current_status' => $call->status,
                        'new_status (ignored)' => $json['call_state'],
                        'current_seq' => $call->last_seq,
                        'new_seq' => $json['seq'],
                    ]);
                }
            } else {
                // Если записи нет, создаем новую
                $call = Call::create($callData);

                // Логируем успешное создание записи
                Log::info('Запись звонка успешно создана', ['call_id' => $call->id]);
            }
        });
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

        $fromPhone = $json['from']['number'];

        // Поиск клиента или лида по номеру с 7 или 8
        $client = Client::where('director_id', $directorId)
            ->where(function ($query) use ($fromPhone) {
                $query->where('phone', $fromPhone)
                    ->orWhere('phone', $this->swapSevenAndEight($fromPhone));
            })
            ->first();

        $callData = [
            'entry_id' => $json['entry_id'],
            'phone_from' => $json['from']['number'],
            'phone_to' => $toPhone,
            'call_time' => Carbon::createFromTimestamp($json['timestamp']),
            'status' => $json['call_state'],
            'client_id' => $client ? $client->id : null,
            'director_id' => $directorId,
            'last_seq' => $json['seq'],
        ];

        // Логируем возвращаемые данные
        Log::info('Данные для создания записи звонка', ['callData' => $callData]);

        return $callData;
    }

    public function handleCallSummary(Request $request)
    {
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

        // Начинаем транзакцию для безопасного обновления данных
        DB::transaction(function () use ($json, $status, $duration) {
            // Поиск существующей записи по entry_id с блокировкой на обновление
            $call = Call::where('entry_id', $json['entry_id'])->lockForUpdate()->first();

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
        });
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
