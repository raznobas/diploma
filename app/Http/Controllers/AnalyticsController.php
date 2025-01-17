<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        if (auth()->user()->director_id === null) {
            return false;
        }

        $directorId = auth()->user()->director_id;

        // Ключ для кэширования, уникальный для каждого директора
        $cacheKey = 'analytics_data_' . $directorId;

        // Кэшируем результат запроса на 24 часа (1440 минут)
        $analytics = Cache::remember($cacheKey, 1440, function () use ($directorId) {
            // Основной SQL-запрос с использованием привязки параметров
            $query = "
            WITH FirstPurchases AS (
                SELECT
                    client_id,
                    MIN(sale_date) AS first_purchase_date
                FROM
                    sales
                WHERE
                    director_id = ?
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
                    COUNT(DISTINCT CASE WHEN s.service_type = 'trial' THEN s.client_id END) AS trials,
                    COUNT(DISTINCT CASE WHEN fp.first_purchase_date = s.sale_date THEN s.client_id END) AS first_purchases,
                    COUNT(DISTINCT s.id) AS total_sales,
                    COUNT(DISTINCT CASE WHEN r.renewal_date IS NOT NULL THEN s.client_id END) AS renewals,
                    SUM(s.paid_amount) AS total_paid_amount,
                    ROUND(SUM(s.paid_amount) / COUNT(DISTINCT s.client_id), 2) AS avg_check,
                    SUM(CASE WHEN s.service_or_product = 'product' THEN s.paid_amount ELSE 0 END) AS products_total,
                    CONCAT(ROUND(SUM(CASE WHEN s.service_or_product = 'product' THEN s.paid_amount ELSE 0 END) / SUM(s.paid_amount) * 100, 2), '%') AS products_percentage,
                    COUNT(DISTINCT CASE WHEN s.service_type = 'individual' THEN s.id END) AS individual_sales_count,
                    SUM(CASE WHEN s.service_type = 'individual' THEN s.paid_amount ELSE 0 END) AS individual_sales_total,
                    CONCAT(ROUND(SUM(CASE WHEN s.service_type = 'individual' THEN s.paid_amount ELSE 0 END) / SUM(s.paid_amount) * 100, 2), '%') AS individual_sales_percentage,
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
                CONCAT(ROUND(SUM(products_total) / SUM(total_paid_amount) * 100, 2), '%') AS products_percentage,
                SUM(individual_sales_count) AS individual_sales_count,
                SUM(individual_sales_total) AS individual_sales_total,
                CONCAT(ROUND(SUM(individual_sales_total) / SUM(total_paid_amount) * 100, 2), '%') AS individual_sales_percentage,
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
                CONCAT(ROUND(SUM(products_total) / SUM(total_paid_amount) * 100, 2), '%') AS products_percentage,
                SUM(individual_sales_count) AS individual_sales_count,
                SUM(individual_sales_total) AS individual_sales_total,
                CONCAT(ROUND(SUM(individual_sales_total) / SUM(total_paid_amount) * 100, 2), '%') AS individual_sales_percentage,
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
                CONCAT(ROUND(SUM(products_total) / SUM(total_paid_amount) * 100, 2), '%') AS products_percentage,
                SUM(individual_sales_count) AS individual_sales_count,
                SUM(individual_sales_total) AS individual_sales_total,
                CONCAT(ROUND(SUM(individual_sales_total) / SUM(total_paid_amount) * 100, 2), '%') AS individual_sales_percentage,
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

            return DB::select($query, [$directorId, $directorId, $directorId, $directorId]);
        });

        return Inertia::render('Analytics/Index', [
            'analytics' => $analytics,
        ]);
    }
}
