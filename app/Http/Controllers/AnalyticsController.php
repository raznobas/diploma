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

            /*
             * 1. Месячная агрегация
             */
            $salesMonthlyQuery = "
            WITH
                -- Агрегация данных по продажам за месяц
                SalesMonthly AS (
                    SELECT
                        DATE_FORMAT(s.sale_date, '%Y-%m') AS month,
                        YEAR(s.sale_date) AS year,
                        MONTH(s.sale_date) AS month_num,
                        COUNT(DISTINCT CASE
                            WHEN s.service_type = 'trial'
                            AND NOT EXISTS (
                                SELECT 1
                                FROM sales s2
                                WHERE s2.client_id = s.client_id
                                  AND s2.service_type != 'trial'
                                  AND s2.service_or_product = 'service'
                            )
                            THEN s.client_id
                        END) AS trials,
                        COUNT(DISTINCT CASE
                            WHEN s.service_type = 'trial'
                                 OR fp.first_purchase_date = s.sale_date
                                 OR s.service_type IS NULL
                            THEN s.client_id
                        END) AS purchase_trials,
                        COUNT(DISTINCT CASE
                            WHEN fp.first_purchase_date = s.sale_date
                                 AND (s.service_type != 'trial' OR s.service_type IS NULL)
                            THEN s.client_id
                        END) AS first_purchases,
                        COUNT(DISTINCT s.id) AS total_sales,
                        SUM(s.paid_amount) AS total_paid_amount,
                        SUM(CASE WHEN s.service_or_product = 'product' THEN s.paid_amount ELSE 0 END) AS products_total,
                        COUNT(DISTINCT CASE WHEN s.service_type = 'individual' THEN s.id END) AS individual_sales_count,
                        SUM(CASE WHEN s.service_type = 'individual' THEN s.paid_amount ELSE 0 END) AS individual_sales_total,
                        COUNT(DISTINCT s.client_id) AS unique_clients
                    FROM
                        sales s
                    LEFT JOIN (
                        SELECT
                            client_id,
                            MIN(sale_date) AS first_purchase_date
                        FROM
                            sales
                        WHERE
                            director_id = ?
                            AND service_or_product = 'service'
                            AND (service_type != 'trial' OR service_type IS NULL)
                        GROUP BY
                            client_id
                    ) fp ON s.client_id = fp.client_id
                    WHERE
                        s.director_id = ?
                    GROUP BY
                        DATE_FORMAT(s.sale_date, '%Y-%m'), YEAR(s.sale_date), MONTH(s.sale_date)
                ),
                -- Агрегация продлений за месяц без дублирования
                RenewalsMonthly AS (
                    SELECT
                        DATE_FORMAT(r.renewal_date, '%Y-%m') AS month,
                        YEAR(r.renewal_date) AS year,
                        MONTH(r.renewal_date) AS month_num,
                        COUNT(DISTINCT r.client_id) AS renewals
                    FROM (
                        SELECT
                            s1.client_id,
                            s1.subscription_end_date,
                            s2.subscription_start_date AS renewal_date
                        FROM
                            sales s1
                        JOIN
                            sales s2 ON s1.client_id = s2.client_id
                        WHERE
                            s1.director_id = ?
                            AND s2.director_id = ?
                            AND s1.service_type = 'group'
                            AND s2.service_type = 'group'
                            AND s1.subscription_duration != 0.03
                            AND s2.subscription_duration != 0.03
                            AND s2.subscription_start_date > s1.subscription_end_date
                    ) r
                    GROUP BY
                        DATE_FORMAT(r.renewal_date, '%Y-%m'), YEAR(r.renewal_date), MONTH(r.renewal_date)
                ),
                -- Агрегация повторных продлений (вторая и более покупки)
                RepeatRenewals AS (
                    SELECT
                        DATE_FORMAT(s.sale_date, '%Y-%m') AS month,
                        YEAR(s.sale_date) AS year,
                        MONTH(s.sale_date) AS month_num,
                        COUNT(DISTINCT s.client_id) AS repeat_renewals
                    FROM (
                        SELECT
                            client_id,
                            sale_date,
                            ROW_NUMBER() OVER (PARTITION BY client_id ORDER BY sale_date) AS purchase_number
                        FROM
                            sales
                        WHERE
                            director_id = ?
                            AND service_or_product = 'service' -- Учитываем только услуги
                            AND (service_type != 'trial' OR service_type IS NULL) -- Исключаем пробные тренировки
                    ) s
                    WHERE
                        s.purchase_number >= 2 -- Учитываем только вторую и последующие покупки
                    GROUP BY
                        DATE_FORMAT(s.sale_date, '%Y-%m'), YEAR(s.sale_date), MONTH(s.sale_date)
                ),
                -- Клиенты, которые прошли через статус лида и затем сделали покупку
                LeadsToSales AS (
                    SELECT
                        DATE_FORMAT(s.sale_date, '%Y-%m') AS month,
                        YEAR(s.sale_date) AS year,
                        MONTH(s.sale_date) AS month_num,
                        COUNT(DISTINCT s.client_id) AS leads_to_sales
                    FROM
                        sales s
                    JOIN
                        client_status_history csh ON s.client_id = csh.client_id
                    WHERE
                        s.director_id = ?
                        AND csh.director_id = ?
                        AND csh.status_to IN ('lead_created', 'form_lead_created')
                        AND s.service_or_product = 'service' -- Учитываем только услуги
                    GROUP BY
                        DATE_FORMAT(s.sale_date, '%Y-%m'), YEAR(s.sale_date), MONTH(s.sale_date)
                )
                SELECT
                    sm.month,
                    sm.year,
                    sm.trials,
                    sm.purchase_trials,
                    sm.first_purchases,
                    sm.total_sales,
                    COALESCE(rm.renewals, 0) AS renewals,
                    COALESCE(rr.repeat_renewals, 0) AS repeat_renewals, -- Новый столбец
                    sm.total_paid_amount,
                    ROUND(sm.total_paid_amount / NULLIF(sm.unique_clients, 0), 2) AS avg_check,
                    sm.products_total,
                    sm.individual_sales_count,
                    sm.individual_sales_total,
                    sm.unique_clients,
                    COALESCE(ls.leads_to_sales, 0) AS leads_to_sales, -- Новое поле
                    'monthly' AS aggregation_level
                FROM
                    SalesMonthly sm
                LEFT JOIN
                    RenewalsMonthly rm ON sm.month = rm.month
                LEFT JOIN
                    RepeatRenewals rr ON sm.month = rr.month
                LEFT JOIN
                    LeadsToSales ls ON sm.month = ls.month
                ORDER BY
                    sm.year DESC, sm.month_num DESC;
        ";

            /*
             * 2. Квартальная агрегация
             */
            $salesQuarterlyQuery = "
            WITH
                -- Агрегация данных по продажам за квартал
                SalesQuarterly AS (
                    SELECT
                        YEAR(s.sale_date) AS year,
                        QUARTER(s.sale_date) AS quarter,
                        COUNT(DISTINCT CASE
                            WHEN s.service_type = 'trial'
                            AND NOT EXISTS (
                                SELECT 1
                                FROM sales s2
                                WHERE s2.client_id = s.client_id
                                  AND s2.service_type != 'trial'
                                  AND s2.service_or_product = 'service'
                            )
                            THEN s.client_id
                        END) AS trials,
                        COUNT(DISTINCT CASE
                            WHEN s.service_type = 'trial'
                                 OR fp.first_purchase_date = s.sale_date
                                 OR s.service_type IS NULL
                            THEN s.client_id
                        END) AS purchase_trials,
                        COUNT(DISTINCT CASE
                            WHEN fp.first_purchase_date = s.sale_date
                                 AND (s.service_type != 'trial' OR s.service_type IS NULL)
                            THEN s.client_id
                        END) AS first_purchases,
                        COUNT(DISTINCT s.id) AS total_sales,
                        SUM(s.paid_amount) AS total_paid_amount,
                        SUM(CASE WHEN s.service_or_product = 'product' THEN s.paid_amount ELSE 0 END) AS products_total,
                        COUNT(DISTINCT CASE WHEN s.service_type = 'individual' THEN s.id END) AS individual_sales_count,
                        SUM(CASE WHEN s.service_type = 'individual' THEN s.paid_amount ELSE 0 END) AS individual_sales_total,
                        COUNT(DISTINCT s.client_id) AS unique_clients
                    FROM
                        sales s
                    LEFT JOIN (
                        SELECT
                            client_id,
                            MIN(sale_date) AS first_purchase_date
                        FROM
                            sales
                        WHERE
                            director_id = ?
                            AND service_or_product = 'service'
                            AND (service_type != 'trial' OR service_type IS NULL)
                        GROUP BY
                            client_id
                    ) fp ON s.client_id = fp.client_id
                    WHERE
                        s.director_id = ?
                    GROUP BY
                        YEAR(s.sale_date), QUARTER(s.sale_date)
                ),
                -- Агрегация продлений за квартал без дублирования
                RenewalsQuarterly AS (
                    SELECT
                        YEAR(r.renewal_date) AS year,
                        QUARTER(r.renewal_date) AS quarter,
                        COUNT(DISTINCT r.client_id) AS renewals
                    FROM (
                        SELECT
                            s1.client_id,
                            s1.subscription_end_date,
                            s2.subscription_start_date AS renewal_date
                        FROM
                            sales s1
                        JOIN
                            sales s2 ON s1.client_id = s2.client_id
                        WHERE
                            s1.director_id = ?
                            AND s2.director_id = ?
                            AND s1.service_type = 'group'
                            AND s2.service_type = 'group'
                            AND s1.subscription_duration != 0.03
                            AND s2.subscription_duration != 0.03
                            AND s2.subscription_start_date > s1.subscription_end_date
                    ) r
                    GROUP BY
                        YEAR(r.renewal_date), QUARTER(r.renewal_date)
                ),
                -- Агрегация повторных продлений (вторая и более покупки) за квартал
                RepeatRenewalsQuarterly AS (
                    SELECT
                        YEAR(s.sale_date) AS year,
                        QUARTER(s.sale_date) AS quarter,
                        COUNT(DISTINCT s.client_id) AS repeat_renewals
                    FROM (
                        SELECT
                            client_id,
                            sale_date,
                            ROW_NUMBER() OVER (PARTITION BY client_id ORDER BY sale_date) AS purchase_number
                        FROM
                            sales
                        WHERE
                            director_id = ?
                            AND service_or_product = 'service' -- Учитываем только услуги
                            AND (service_type != 'trial' OR service_type IS NULL) -- Исключаем пробные тренировки
                    ) s
                    WHERE
                        s.purchase_number >= 2 -- Учитываем только вторую и последующие покупки
                    GROUP BY
                        YEAR(s.sale_date), QUARTER(s.sale_date)
                ),
                -- Клиенты, которые прошли через статус лида (client_status_history) и затем сделали покупку
                LeadsToSalesQuarterly AS (
                    SELECT
                        YEAR(s.sale_date) AS year,
                        QUARTER(s.sale_date) AS quarter,
                        COUNT(DISTINCT s.client_id) AS leads_to_sales
                    FROM
                        sales s
                    JOIN
                        client_status_history csh ON s.client_id = csh.client_id
                    WHERE
                        s.director_id = ?
                        AND csh.director_id = ?
                        AND csh.status_to IN ('lead_created', 'form_lead_created')
                        AND s.service_or_product = 'service'
                    GROUP BY
                        YEAR(s.sale_date), QUARTER(s.sale_date)
                )
            SELECT
                sq.year,
                sq.quarter,
                sq.trials,
                sq.purchase_trials,
                sq.first_purchases,
                sq.total_sales,
                COALESCE(rq.renewals, 0) AS renewals,
                COALESCE(rrq.repeat_renewals, 0) AS repeat_renewals,
                sq.total_paid_amount,
                ROUND(sq.total_paid_amount / sq.unique_clients, 2) AS avg_check,
                sq.products_total,
                sq.individual_sales_count,
                sq.individual_sales_total,
                sq.unique_clients,
                COALESCE(ltq.leads_to_sales, 0) AS leads_to_sales, -- Новое поле: лиды, конвертировавшиеся в продажи
                'quarterly' AS aggregation_level
            FROM
                SalesQuarterly sq
            LEFT JOIN
                RenewalsQuarterly rq ON sq.year = rq.year AND sq.quarter = rq.quarter
            LEFT JOIN
                RepeatRenewalsQuarterly rrq ON sq.year = rrq.year AND sq.quarter = rrq.quarter
            LEFT JOIN
                LeadsToSalesQuarterly ltq ON sq.year = ltq.year AND sq.quarter = ltq.quarter
            ORDER BY
                sq.year DESC, sq.quarter DESC;
        ";

            /*
             * 3. Годовая агрегация
             */
            $salesYearlyQuery = "
            WITH
                SalesYearly AS (
                    SELECT
                        YEAR(s.sale_date) AS year,
                        COUNT(DISTINCT CASE
                            WHEN s.service_type = 'trial'
                            AND NOT EXISTS (
                                SELECT 1
                                FROM sales s2
                                WHERE s2.client_id = s.client_id
                                  AND s2.service_type != 'trial'
                                  AND s2.service_or_product = 'service'
                            )
                            THEN s.client_id
                        END) AS trials,
                        COUNT(DISTINCT CASE
                            WHEN s.service_type = 'trial'
                                 OR fp.first_purchase_date = s.sale_date
                                 OR s.service_type IS NULL
                            THEN s.client_id
                        END) AS purchase_trials,
                        COUNT(DISTINCT CASE
                            WHEN fp.first_purchase_date = s.sale_date
                                 AND (s.service_type != 'trial' OR s.service_type IS NULL)
                            THEN s.client_id
                        END) AS first_purchases,
                        COUNT(DISTINCT s.id) AS total_sales,
                        SUM(s.paid_amount) AS total_paid_amount,
                        SUM(CASE WHEN s.service_or_product = 'product' THEN s.paid_amount ELSE 0 END) AS products_total,
                        COUNT(DISTINCT CASE WHEN s.service_type = 'individual' THEN s.id END) AS individual_sales_count,
                        SUM(CASE WHEN s.service_type = 'individual' THEN s.paid_amount ELSE 0 END) AS individual_sales_total,
                        COUNT(DISTINCT s.client_id) AS unique_clients
                    FROM
                        sales s
                    LEFT JOIN (
                        SELECT
                            client_id,
                            MIN(sale_date) AS first_purchase_date
                        FROM
                            sales
                        WHERE
                            director_id = ?
                            AND service_or_product = 'service'
                            AND (service_type != 'trial' OR service_type IS NULL)
                        GROUP BY
                            client_id
                    ) fp ON s.client_id = fp.client_id
                    WHERE
                        s.director_id = ?
                    GROUP BY
                        YEAR(s.sale_date)
                ),

                RenewalsYearly AS (
                    SELECT
                        YEAR(r.renewal_date) AS year,
                        COUNT(DISTINCT r.client_id) AS renewals
                    FROM (
                        SELECT
                            s1.client_id,
                            s1.subscription_end_date,
                            s2.subscription_start_date AS renewal_date
                        FROM
                            sales s1
                        JOIN
                            sales s2 ON s1.client_id = s2.client_id
                        WHERE
                            s1.director_id = ?
                            AND s2.director_id = ?
                            AND s1.service_type = 'group'
                            AND s2.service_type = 'group'
                            AND s1.subscription_duration != 0.03
                            AND s2.subscription_duration != 0.03
                            AND s2.subscription_start_date > s1.subscription_end_date
                    ) r
                    GROUP BY
                        YEAR(r.renewal_date)
                ),

                RepeatRenewalsYearly AS (
                    SELECT
                        YEAR(s.sale_date) AS year,
                        COUNT(DISTINCT s.client_id) AS repeat_renewals
                    FROM (
                        SELECT
                            client_id,
                            sale_date,
                            ROW_NUMBER() OVER (PARTITION BY client_id ORDER BY sale_date) AS purchase_number
                        FROM
                            sales
                        WHERE
                            director_id = ?
                            AND service_or_product = 'service'
                            AND (service_type != 'trial' OR service_type IS NULL)
                    ) s
                    WHERE
                        s.purchase_number >= 2
                    GROUP BY
                        YEAR(s.sale_date)
                ),

                LeadsToSalesYearly AS (
                    SELECT
                        YEAR(s.sale_date) AS year,
                        COUNT(DISTINCT s.client_id) AS leads_to_sales
                    FROM
                        sales s
                    JOIN
                        client_status_history csh ON s.client_id = csh.client_id
                    WHERE
                        s.director_id = ?
                        AND csh.director_id = ?
                        AND csh.status_to IN ('lead_created', 'form_lead_created')
                        AND s.service_or_product = 'service'
                    GROUP BY
                        YEAR(s.sale_date)
                )

            SELECT
                sy.year,
                sy.trials,
                sy.purchase_trials,
                sy.first_purchases,
                sy.total_sales,
                COALESCE(ry.renewals, 0) AS renewals,
                COALESCE(ryy.repeat_renewals, 0) AS repeat_renewals,
                sy.total_paid_amount,
                ROUND(sy.total_paid_amount / sy.unique_clients, 2) AS avg_check,
                sy.products_total,
                sy.individual_sales_count,
                sy.individual_sales_total,
                sy.unique_clients,
                COALESCE(ly.leads_to_sales, 0) AS leads_to_sales,
                'yearly' AS aggregation_level
            FROM
                SalesYearly sy
            LEFT JOIN
                RenewalsYearly ry ON sy.year = ry.year
            LEFT JOIN
                RepeatRenewalsYearly ryy ON sy.year = ryy.year
            LEFT JOIN
                LeadsToSalesYearly ly ON sy.year = ly.year
            ORDER BY
                sy.year DESC;
        ";

            // Выполнение запросов с передачей необходимых параметров director_id.
            // Для каждого подзапроса порядок параметров должен соответствовать знакам вопроса в запросе.
            $monthlyData = DB::select($salesMonthlyQuery, [$directorId, $directorId, $directorId, $directorId, $directorId, $directorId, $directorId ]);

            $quarterlyData = DB::select($salesQuarterlyQuery, [$directorId, $directorId, $directorId, $directorId, $directorId, $directorId, $directorId]);

            $yearlyData = DB::select($salesYearlyQuery, [$directorId, $directorId, $directorId, $directorId, $directorId, $directorId, $directorId]);

            // Объединяем данные из трёх выборок
            $salesData = array_merge($monthlyData, $quarterlyData, $yearlyData);

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

            $leadsData = DB::select($leadsQuery, [$directorId]);
            $formLeadsData = DB::select($formLeadsQuery, [$directorId]);
            $salesData = $this->mergeAnalyticsData($salesData, $leadsData, $formLeadsData);

            return $salesData;
        });

        return Inertia::render('Analytics/Index', [
            'analytics' => $analytics,
        ]);
    }

    private function mergeAnalyticsData(array $salesData, array $leadsData, array $formLeadsData): array
    {
        // Преобразуем данные о лидах в ассоциативный массив для быстрого поиска
        $leadsMap = [];
        foreach ($leadsData as $lead) {
            $key = $lead->year . '-' . ($lead->month ?? '') . '-' . ($lead->quarter ?? '') . '-' . $lead->aggregation_level;
            $leadsMap[$key] = $lead->leads;
        }

        // Преобразуем данные о заявках с формы в ассоциативный массив для быстрого поиска
        $formLeadsMap = [];
        foreach ($formLeadsData as $formLead) {
            $key = $formLead->year . '-' . ($formLead->month ?? '') . '-' . ($formLead->quarter ?? '') . '-' . $formLead->aggregation_level;
            $formLeadsMap[$key] = $formLead->form_leads;
        }

        // Добавляем количество лидов, звонков и заявок к данным о продажах
        foreach ($salesData as &$sale) {
            $key = $sale->year . '-' . ($sale->month ?? '') . '-' . ($sale->quarter ?? '') . '-' . $sale->aggregation_level;
            $sale->leads = $leadsMap[$key] ?? 0;
            $sale->form_leads = $formLeadsMap[$key] ?? 0;
        }

        return $salesData;
    }
}
