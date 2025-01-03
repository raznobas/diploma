<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GymSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Массив залов с русскими и английскими названиями
        $gyms = [
            ['name' => 'kozhukhovskaya', 'label' => 'КОЖУХОВСКАЯ', 'director_id' => null],
            ['name' => 'shosse_entuziastov', 'label' => 'ШОССЕ ЭНТУЗИАСТОВ', 'director_id' => null],
            ['name' => 'alekseevskaya_vdnkh', 'label' => 'АЛЕКСЕЕВСКАЯ/ВДНХ', 'director_id' => null],
            ['name' => 'dmitrovskaya', 'label' => 'ДМИТРОВСКАЯ', 'director_id' => null],
            ['name' => 'chertanovskaya', 'label' => 'ЧЕРТАНОВСКАЯ', 'director_id' => null],
            ['name' => 'tushinskaya', 'label' => 'ТУШИНСКАЯ', 'director_id' => null],
            ['name' => 'elektrozavodskaya', 'label' => 'ЭЛЕКТРОЗАВОДСКАЯ', 'director_id' => null],
        ];

        // Добавление или обновление залов в таблице
        foreach ($gyms as $gym) {
            DB::table('gyms')->updateOrInsert(
                ['name' => $gym['name']], // Условие поиска (по полю `name`)
                [
                    'label' => $gym['label'], // Обновляем или добавляем поле `label`
                    'director_id' => $gym['director_id'], // Обновляем или добавляем поле `director_id`
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
