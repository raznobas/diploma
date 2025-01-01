<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    public function applyFilters(Builder $query, Request $request, $routeName = null): Builder
    {
        if ($routeName === 'clients.index' || $routeName === 'leads.index') {
            if ($request->filled('client_name')) {
                $clientNameParts = preg_split('/[\s,.]+/', $request->client_name);

                // Проверяем, что введено как минимум два слова (имя и фамилия)
                if (count($clientNameParts) >= 2) {
                    $query->where(function ($subQuery) use ($clientNameParts) {
                        // Первый вариант: первое слово - имя, второе - фамилия
                        $subQuery->where(function ($innerQuery) use ($clientNameParts) {
                            $innerQuery->where('name', 'like', '%' . $clientNameParts[0] . '%')
                                ->where('surname', 'like', '%' . $clientNameParts[1] . '%');
                        });

                        // Второй вариант: первое слово - фамилия, второе - имя
                        $subQuery->orWhere(function ($innerQuery) use ($clientNameParts) {
                            $innerQuery->where('name', 'like', '%' . $clientNameParts[1] . '%')
                                ->where('surname', 'like', '%' . $clientNameParts[0] . '%');
                        });
                    });
                } else {
                    // Если введено только одно слово, ищем по имени или фамилии
                    $query->where(function ($subQuery) use ($clientNameParts) {
                        foreach ($clientNameParts as $part) {
                            $subQuery->orWhere(function ($innerQuery) use ($part) {
                                $innerQuery->where('name', 'like', '%' . $part . '%')
                                    ->orWhere('surname', 'like', '%' . $part . '%');
                            });
                        }
                    });
                }
            }

            // Фильтрация по фамилии
            if ($request->filled('patronymic')) {
                $query->where('patronymic', 'like', '%' . $request->patronymic . '%');
            }

            // Фильтрация по дате рождения
            if ($request->filled('birthdate')) {
                $query->where('birthdate', $request->birthdate);
            }

            // Фильтрация по месту работы
            if ($request->filled('workplace')) {
                $query->where('workplace', 'like', '%' . $request->workplace . '%');
            }

            // Фильтрация по телефону
            if ($request->filled('phone')) {
                $query->where('phone', 'like', '%' . $request->phone . '%');
            }

            // Фильтрация по email
            if ($request->filled('email')) {
                $query->where('email', 'like', '%' . $request->email . '%');
            }

            // Фильтрация по Telegram
            if ($request->filled('telegram')) {
                $query->where('telegram', 'like', '%' . $request->telegram . '%');
            }

            // Фильтрация по Instagram
            if ($request->filled('instagram')) {
                $query->where('instagram', 'like', '%' . $request->instagram . '%');
            }

            // Фильтрация по адресу
            if ($request->filled('address')) {
                $query->where('address', 'like', '%' . $request->address . '%');
            }

            // Фильтрация по полу
            if ($request->filled('gender')) {
                $query->where('gender', $request->gender);
            }

            // Фильтрация по источнику рекламы
            if ($request->filled('ad_source')) {
                $query->where('ad_source', 'like', '%' . $request->ad_source . '%');
            }
        }
        if ($routeName === 'sales.index') {
            // Фильтрация по имени клиента
            if ($request->filled('client_name')) {
                $clientNameParts = preg_split('/[\s,.]+/', $request->client_name);

                // Проверяем, что введено как минимум два слова (имя и фамилия)
                if (count($clientNameParts) >= 2) {
                    $query->whereHas('client', function ($q) use ($clientNameParts) {
                        $q->where(function ($subQuery) use ($clientNameParts) {
                            // Первый вариант: первое слово - имя, второе - фамилия
                            $subQuery->where(function ($innerQuery) use ($clientNameParts) {
                                $innerQuery->where('name', 'like', '%' . $clientNameParts[0] . '%')
                                    ->where('surname', 'like', '%' . $clientNameParts[1] . '%');
                            });

                            // Второй вариант: первое слово - фамилия, второе - имя
                            $subQuery->orWhere(function ($innerQuery) use ($clientNameParts) {
                                $innerQuery->where('name', 'like', '%' . $clientNameParts[1] . '%')
                                    ->where('surname', 'like', '%' . $clientNameParts[0] . '%');
                            });
                        });
                    });
                } else {
                    // Если введено только одно слово, ищем по имени или фамилии
                    $query->whereHas('client', function ($q) use ($clientNameParts) {
                        $q->where(function ($subQuery) use ($clientNameParts) {
                            foreach ($clientNameParts as $part) {
                                $subQuery->orWhere(function ($innerQuery) use ($part) {
                                    $innerQuery->where('name', 'like', '%' . $part . '%')
                                        ->orWhere('surname', 'like', '%' . $part . '%');
                                });
                            }
                        });
                    });
                }
            }

            // Фильтрация по виду спорта
            if ($request->filled('sport_type')) {
                $query->where('sport_type', 'like', '%' . $request->sport_type . '%');
            }

            // Фильтрация по виду товара
            if ($request->filled('product_type')) {
                $query->where('product_type', 'like', '%' . $request->product_type . '%');
            }

            // Фильтрация по виду услуги
            if ($request->filled('service_type')) {
                $query->where('service_type', $request->service_type);
            }

            // Фильтрация по абонементу в неделю
            if ($request->filled('subscription_duration')) {
                $query->where('subscription_duration', $request->subscription_duration);
            }

            // Фильтрация по посещениям в неделю
            if ($request->filled('visits_per_week')) {
                $query->where('visits_per_week', $request->visits_per_week);
            }

            // Фильтрация по тренеру
            if ($request->filled('trainer')) {
                $query->where('trainer', 'like', '%' . $request->trainer . '%');
            }

            // Фильтрация по количеству тренировок
            if ($request->filled('training_count')) {
                $query->where('training_count', $request->training_count);
            }

            // Фильтрация по способу оплаты
            if ($request->filled('pay_method')) {
                $query->where('pay_method', 'like', '%' . $request->pay_method . '%');
            }

            // Фильтрация по комментарию
            if ($request->filled('comment')) {
                $query->where('comment', 'like', '%' . $request->comment . '%');
            }

            // Фильтрация по дате от
            if ($request->filled('date_from')) {
                $query->where('sale_date', '>=', $request->date_from);
            }

            // Фильтрация по дате до
            if ($request->filled('date_to')) {
                $query->where('sale_date', '<=', $request->date_to);
            }

            // Фильтрация по началу абонента
            if ($request->filled('subscription_start_date')) {
                $query->where('subscription_start_date', '=', $request->subscription_start_date);
            }

            // Фильтрация по концу абонента
            if ($request->filled('subscription_end_date')) {
                $query->where('subscription_end_date', '=', $request->subscription_end_date);
            }
        }

        return $query;
    }


    // Создание отдельного метода для записей на пробные - из-за того, что один и тот же маршрут
    public function applyLeadAppointmentFilters($query, Request $request)
    {
        if ($request->filled('client_name_book')) {
            $clientNameParts = preg_split('/[\s,.]+/', $request->client_name);

            if (count($clientNameParts) >= 2) {
                $query->whereHas('client', function ($q) use ($clientNameParts) {
                    $q->where(function ($subQuery) use ($clientNameParts) {
                        $subQuery->where(function ($innerQuery) use ($clientNameParts) {
                            $innerQuery->where('name', 'like', '%' . $clientNameParts[0] . '%')
                                ->where('surname', 'like', '%' . $clientNameParts[1] . '%');
                        })->orWhere(function ($innerQuery) use ($clientNameParts) {
                            $innerQuery->where('name', 'like', '%' . $clientNameParts[1] . '%')
                                ->where('surname', 'like', '%' . $clientNameParts[0] . '%');
                        });
                    });
                });
            } else {
                $query->whereHas('client', function ($q) use ($clientNameParts) {
                    $q->where(function ($subQuery) use ($clientNameParts) {
                        foreach ($clientNameParts as $part) {
                            $subQuery->orWhere(function ($innerQuery) use ($part) {
                                $innerQuery->where('name', 'like', '%' . $part . '%')
                                    ->orWhere('surname', 'like', '%' . $part . '%');
                            });
                        }
                    });
                });
            }
        }

        if ($request->filled('sport_type')) {
            $query->where('sport_type', 'like', '%' . $request->sport_type . '%');
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->filled('trainer')) {
            $query->where('trainer', 'like', '%' . $request->trainer . '%');
        }

        if ($request->filled('training_date')) {
            $query->whereDate('training_date', $request->training_date);
        }

        return $query;
    }
}
