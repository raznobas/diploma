<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MassMailingClientService
{
    /**
     * Получить клиентов для рассылки.
     *
     * @param int $directorId
     * @param array $sportTypes
     * @param Carbon $currentDate
     * @return Collection
     */
    public function getTrialClients(int $directorId, array $sportTypes, Carbon $currentDate): Collection
    {
        // 1. Получаем все продажи с пробными тренировками для данного директора
        $trials = Sale::where('service_type', '=', 'trial')
            ->where('director_id', $directorId)
            ->when(!empty($sportTypes), function ($query) use ($sportTypes) {
                $query->whereIn('sport_type', $sportTypes);
            })
            ->get();

        // 2. Извлекаем уникальные client_id из этих продаж
        $clientIds = $trials->pluck('client_id')->unique();

        // 3. Получаем клиентов с нужными условиями:
        //    - Клиенты, относящиеся к заданному director_id
        //    - У которых заполнен phone
        //    - У которых отсутствует активный абонемент и продажи, отличные от пробных
        return Client::whereIn('id', $clientIds)
            ->where('director_id', $directorId)
            ->whereNotNull('phone')
            ->where('phone', '<>', '')
            ->whereDoesntHave('sales', function ($query) use ($currentDate, $directorId) {
                $query->where('subscription_end_date', '>', $currentDate)
                    ->where('director_id', $directorId);
            })
            ->whereDoesntHave('sales', function ($query) use ($directorId) {
                $query->where('service_type', '!=', 'trial')
                    ->where('service_or_product', 'service')
                    ->where('director_id', $directorId);
            })
            ->select('id', 'surname', 'name', 'phone')
            ->with(['sales' => function ($query) use ($directorId) {
                $query->where('service_type', '=', 'trial')
                    ->where('director_id', $directorId);
            }])
            ->get();
    }
}
