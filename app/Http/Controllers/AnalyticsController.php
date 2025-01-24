<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $directorId = auth()->user()->director_id;

        if (auth()->user()->id !== $directorId) {
            return redirect()->back()->withErrors(['error' => 'Просмотр аналитики доступен только директору.']);
        }

        // Ключ для кэширования, уникальный для каждого директора
        $cacheKey = 'analytics_data_' . $directorId;

        // Кэшируем результат запроса на 24 часа (1440 минут)
        $analytics = Cache::remember($cacheKey, 1440, function () use ($directorId) {
            // Основной SQL-запрос с использованием привязки параметров
            $salesQuery = "
            WITH FirstPurchases AS (
                SELECT
                    client_id,
                    MIN(sale_date) AS first_purchase_date
                FROM
                    sales
                WHERE
                    director_id = ?
                    AND service_or_product = 'service' -- Учитываем только услуги
                    AND (service_type != 'trial' OR service_type IS NULL) -- Исключаем пробные тренировки, но учитываем NULL
                GROUP BY
                    client_id
            ),
            Renewals AS (
                SELECT
                    s1.client_id,
                    s1.subscription_end_date AS prev_end_date,
                    s2.subscription_start_date AS renewal_date
                FROM
                    sales s1
                JOIN
                    sales s2 ON s1.client_id = s2.client_id
                    AND s2.subscription_start_date > s1.subscription_end_date
                WHERE
                    s1.service_type = 'group'
                    AND s2.service_type = 'group'
                    AND s1.subscription_duration != 0.03
                    AND s1.director_id = ?
                    AND s2.director_id = ?
            ),
            AggregatedData AS (
                SELECT
                    DATE_FORMAT(s.sale_date, '%Y-%m') AS month,
                    YEAR(s.sale_date) AS year,
                    QUARTER(s.sale_date) AS quarter,
                    COUNT(DISTINCT CASE
                        WHEN s.service_type = 'trial'
                             OR fp.first_purchase_date = s.sale_date
                             OR s.service_type IS NULL -- Учитываем записи с service_type = NULL
                        THEN s.client_id
                    END) AS trials, -- Учитываем пробники (trial, первые покупки и NULL)
                    COUNT(DISTINCT CASE
                        WHEN fp.first_purchase_date = s.sale_date -- Первые покупки
                             AND (s.service_type != 'trial' OR s.service_type IS NULL) -- Исключаем trial, но учитываем NULL
                        THEN s.client_id
                    END) AS first_purchases, -- Учитываем только первые покупки (исключая trial, но включая NULL)
                    COUNT(DISTINCT s.id) AS total_sales,
                    COUNT(DISTINCT CASE WHEN r.renewal_date IS NOT NULL THEN s.client_id END) AS renewals,
                    SUM(s.paid_amount) AS total_paid_amount,
                    ROUND(SUM(s.paid_amount) / COUNT(DISTINCT s.client_id), 2) AS avg_check,
                    SUM(CASE WHEN s.service_or_product = 'product' THEN s.paid_amount ELSE 0 END) AS products_total,
                    COUNT(DISTINCT CASE WHEN s.service_type = 'individual' THEN s.id END) AS individual_sales_count,
                    SUM(CASE WHEN s.service_type = 'individual' THEN s.paid_amount ELSE 0 END) AS individual_sales_total,
                    COUNT(DISTINCT s.client_id) AS unique_clients
                FROM
                    sales s
                LEFT JOIN
                    FirstPurchases fp ON s.client_id = fp.client_id
                LEFT JOIN
                    Renewals r ON s.client_id = r.client_id
                    AND DATE_FORMAT(r.renewal_date, '%Y-%m') = DATE_FORMAT(s.sale_date, '%Y-%m')
                WHERE
                    s.director_id = ?
                GROUP BY
                    DATE_FORMAT(s.sale_date, '%Y-%m'), YEAR(s.sale_date), QUARTER(s.sale_date)
            )
            SELECT
                month,
                year,
                NULL AS quarter,
                SUM(trials) AS trials,
                SUM(first_purchases) AS first_purchases,
                SUM(total_sales) AS total_sales,
                SUM(renewals) AS renewals,
                SUM(total_paid_amount) AS total_paid_amount,
                ROUND(SUM(total_paid_amount) / SUM(unique_clients), 2) AS avg_check,
                SUM(products_total) AS products_total,
                SUM(individual_sales_count) AS individual_sales_count,
                SUM(individual_sales_total) AS individual_sales_total,
                'monthly' AS aggregation_level
            FROM
                AggregatedData
            GROUP BY
                month, year
            UNION ALL
            SELECT
                NULL AS month,
                year,
                quarter,
                SUM(trials) AS trials,
                SUM(first_purchases) AS first_purchases,
                SUM(total_sales) AS total_sales,
                SUM(renewals) AS renewals,
                SUM(total_paid_amount) AS total_paid_amount,
                ROUND(SUM(total_paid_amount) / SUM(unique_clients), 2) AS avg_check,
                SUM(products_total) AS products_total,
                SUM(individual_sales_count) AS individual_sales_count,
                SUM(individual_sales_total) AS individual_sales_total,
                'quarterly' AS aggregation_level
            FROM
                AggregatedData
            GROUP BY
                year, quarter
            UNION ALL
            SELECT
                NULL AS month,
                year,
                NULL AS quarter,
                SUM(trials) AS trials,
                SUM(first_purchases) AS first_purchases,
                SUM(total_sales) AS total_sales,
                SUM(renewals) AS renewals,
                SUM(total_paid_amount) AS total_paid_amount,
                ROUND(SUM(total_paid_amount) / SUM(unique_clients), 2) AS avg_check,
                SUM(products_total) AS products_total,
                SUM(individual_sales_count) AS individual_sales_count,
                SUM(individual_sales_total) AS individual_sales_total,
                'yearly' AS aggregation_level
            FROM
                AggregatedData
            GROUP BY
                year
            ORDER BY
                year DESC,
                aggregation_level DESC,
                quarter DESC,
                month DESC;
            ";
            // Выполняем запрос для основной аналитики
            $salesData = DB::select($salesQuery, [$directorId, $directorId, $directorId, $directorId]);

            // Запрос для лидов
            $leadsQuery = "
            WITH Leads AS (
                SELECT
                    DATE_FORMAT(csh.created_at, '%Y-%m') AS month,
                    YEAR(csh.created_at) AS year,
                    QUARTER(csh.created_at) AS quarter,
                    COUNT(DISTINCT csh.client_id) AS leads
                FROM
                    client_status_history csh
                WHERE
                    csh.director_id = ?
                    AND (csh.status_to = 'lead_created' OR csh.status_to = 'form_lead_created')
                GROUP BY
                    DATE_FORMAT(csh.created_at, '%Y-%m'), YEAR(csh.created_at), QUARTER(csh.created_at)
            )
            SELECT
                month,
                year,
                NULL AS quarter,
                leads,
                'monthly' AS aggregation_level
            FROM
                Leads
            UNION ALL
            SELECT
                NULL AS month,
                year,
                quarter,
                SUM(leads) AS leads,
                'quarterly' AS aggregation_level
            FROM
                Leads
            GROUP BY
                year, quarter
            UNION ALL
            SELECT
                NULL AS month,
                year,
                NULL AS quarter,
                SUM(leads) AS leads,
                'yearly' AS aggregation_level
            FROM
                Leads
            GROUP BY
                year
            ORDER BY
                year DESC,
                aggregation_level DESC,
                quarter DESC,
                month DESC;
            ";

            // Выполняем запрос для лидов
            $leadsData = DB::select($leadsQuery, [$directorId]);

            // Запрос для заявок с формы
            $formLeadsQuery = "
            WITH FormLeads AS (
                SELECT
                    DATE_FORMAT(csh.created_at, '%Y-%m') AS month,
                    YEAR(csh.created_at) AS year,
                    QUARTER(csh.created_at) AS quarter,
                    COUNT(DISTINCT csh.client_id) AS form_leads
                FROM
                    client_status_history csh
                WHERE
                    csh.director_id = ?
                    AND csh.status_to = 'form_lead_created' -- Только заявки с формы
                GROUP BY
                    DATE_FORMAT(csh.created_at, '%Y-%m'), YEAR(csh.created_at), QUARTER(csh.created_at)
            )
            SELECT
                month,
                year,
                NULL AS quarter,
                form_leads,
                'monthly' AS aggregation_level
            FROM
                FormLeads
            UNION ALL
            SELECT
                NULL AS month,
                year,
                quarter,
                SUM(form_leads) AS form_leads,
                'quarterly' AS aggregation_level
            FROM
                FormLeads
            GROUP BY
                year, quarter
            UNION ALL
            SELECT
                NULL AS month,
                year,
                NULL AS quarter,
                SUM(form_leads) AS form_leads,
                'yearly' AS aggregation_level
            FROM
                FormLeads
            GROUP BY
                year
            ORDER BY
                year DESC,
                aggregation_level DESC,
                quarter DESC,
                month DESC;
            ";

            // Выполняем запрос для заявок с формы
            $formLeadsData = DB::select($formLeadsQuery, [$directorId]);

            // Запрос для звонков
            $callsQuery = "
            WITH Calls AS (
                SELECT
                    DATE_FORMAT(c.created_at, '%Y-%m') AS `month`,
                    YEAR(c.created_at) AS `year`,
                    QUARTER(c.created_at) AS `quarter`,
                    COUNT(DISTINCT c.id) AS calls
                FROM
                    calls c
                WHERE
                    c.director_id = ?
                    AND c.status = 'answered'
                GROUP BY
                    DATE_FORMAT(c.created_at, '%Y-%m'),
                    YEAR(c.created_at),
                    QUARTER(c.created_at)
            )
            SELECT
                `month`,
                `year`,
                NULL AS `quarter`,
                calls,
                'monthly' AS aggregation_level
            FROM
                Calls
            UNION ALL
            SELECT
                NULL AS `month`,
                `year`,
                `quarter`,
                SUM(calls) AS calls,
                'quarterly' AS aggregation_level
            FROM
                Calls
            GROUP BY
                `year`,
                `quarter`
            UNION ALL
            SELECT
                NULL AS `month`,
                `year`,
                NULL AS `quarter`,
                SUM(calls) AS calls,
                'yearly' AS aggregation_level
            FROM
                Calls
            GROUP BY
                `year`
            ORDER BY
                `year` DESC,
                aggregation_level DESC,
                `quarter` DESC,
                `month` DESC;
            ";

            // Выполняем запрос для звонков
            $callsData = DB::select($callsQuery, [$directorId]);

            // Объединяем данные
            return $this->mergeAnalyticsData($salesData, $leadsData, $callsData, $formLeadsData);
        });

        return Inertia::render('Analytics/Index', [
            'analytics' => $analytics,
        ]);
    }

    private function mergeAnalyticsData(array $salesData, array $leadsData, array $callsData, array $formLeadsData): array
    {
        // Преобразуем данные о лидах в ассоциативный массив для быстрого поиска
        $leadsMap = [];
        foreach ($leadsData as $lead) {
            $key = $lead->year . '-' . $lead->month . '-' . $lead->quarter . '-' . $lead->aggregation_level;
            $leadsMap[$key] = $lead->leads;
        }

        // Преобразуем данные о заявках с формы в ассоциативный массив для быстрого поиска
        $formLeadsMap = [];
        foreach ($formLeadsData as $formLead) {
            $key = $formLead->year . '-' . $formLead->month . '-' . $formLead->quarter . '-' . $formLead->aggregation_level;
            $formLeadsMap[$key] = $formLead->form_leads;
        }

        // Преобразуем данные о звонках в ассоциативный массив для быстрого поиска
        $callsMap = [];
        foreach ($callsData as $call) {
            $key = $call->year . '-' . $call->month . '-' . $call->quarter . '-' . $call->aggregation_level;
            $callsMap[$key] = $call->calls;
        }

        // Добавляем количество лидов и звонков в данные о продажах
        foreach ($salesData as &$sale) {
            $key = $sale->year . '-' . $sale->month . '-' . $sale->quarter . '-' . $sale->aggregation_level;
            $sale->leads = $leadsMap[$key] ?? 0;
            $sale->form_leads = $formLeadsMap[$key] ?? 0;
            $sale->calls = $callsMap[$key] ?? 0;
        }

        return $salesData;
    }
}
