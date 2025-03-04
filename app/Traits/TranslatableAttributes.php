<?php
namespace App\Traits;

trait TranslatableAttributes
{
    protected function getTranslatableAttributes()
    {
        return [
            'sale_date' => __('attributes.sale_date'),
            'client_id' => __('attributes.client_id'),
            'director_id' => __('attributes.director_id'),
            'service_or_product' => __('attributes.service_or_product'),
            'sport_type' => __('attributes.sport_type'),
            'service_type' => __('attributes.service_type'),
            'product_type' => __('attributes.product_type'),
            'subscription_duration' => __('attributes.subscription_duration'),
            'visits_per_week' => __('attributes.visits_per_week'),
            'training_count' => __('attributes.training_count'),
            'trainer_category' => __('attributes.trainer_category'),
            'trainer' => __('attributes.trainer'),
            'subscription_start_date' => __('attributes.subscription_start_date'),
            'subscription_end_date' => __('attributes.subscription_end_date'),
            'cost' => __('attributes.cost'),
            'paid_amount' => __('attributes.paid_amount'),
            'pay_method' => __('attributes.pay_method'),
            'surname' => __('attributes.surname'),
            'name' => __('attributes.name'),
            'patronymic' => __('attributes.patronymic'),
            'birthdate' => __('attributes.birthdate'),
            'workplace' => __('attributes.workplace'),
            'phone' => __('attributes.phone'),
            'telegram' => __('attributes.telegram'),
            'instagram' => __('attributes.instagram'),
            'address' => __('attributes.address'),
            'gender' => __('attributes.gender'),
            'ad_source' => __('attributes.ad_source'),
            'training_date' => __('attributes.training_date')
        ];
    }
}
