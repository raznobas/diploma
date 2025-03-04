<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Silber\Bouncer\Bouncer;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class SaleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $client;
    protected $categories;
    protected $bouncer;

    /**
     * Настройка тестовой среды.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Создаем экземпляр Bouncer
        $this->bouncer = Bouncer::create();

        // Создаем роли и разрешения
        $directorRole = Role::create(['name' => 'director']);
        $manageSalesAbility = Ability::create(['name' => 'manage-sales']);
        $manageCategoriesAbility = Ability::create(['name' => 'manage-categories']);

        // Связываем роли с разрешениями
        $directorRole->allow($manageSalesAbility);
        $directorRole->allow($manageCategoriesAbility);

        // Создаем фейкового пользователя
        $this->user = User::factory()->create();
        $this->user->director_id = $this->user->id;

        // Назначаем пользователю роль director
        $this->bouncer->assign('director')->to($this->user);

        // Создаем фейкового клиента
        $this->client = Client::factory()->withDirectorId($this->user->director_id)->create();

        // Создаем необходимые категории
        $this->categories = [
            ['name' => 'Футбол', 'type' => 'sport_type'],
            ['name' => 'Баскетбол', 'type' => 'sport_type'],
            ['name' => 'Продукт 1', 'type' => 'product_type'],
            ['name' => 'Продукт 2', 'type' => 'product_type'],
            ['name' => '10', 'type' => 'training_count'],
            ['name' => '20', 'type' => 'training_count'],
            ['name' => '1 месяц', 'type' => 'subscription_duration'],
            ['name' => '3 месяца', 'type' => 'subscription_duration'],
            ['name' => '3', 'type' => 'visits_per_week'],
            ['name' => '5', 'type' => 'visits_per_week'],
            ['name' => 'Тренер 1', 'type' => 'trainer'],
            ['name' => 'Тренер 2', 'type' => 'trainer'],
            ['name' => 'Обычный', 'type' => 'trainer_category'],
            ['name' => 'Мастер', 'type' => 'trainer_category'],
            ['name' => 'Карта', 'type' => 'pay_method'],
            ['name' => 'Наличные', 'type' => 'pay_method'],
            ['name' => 'Интернет', 'type' => 'ad_source'],
            ['name' => 'Друзья', 'type' => 'ad_source'],
        ];

        foreach ($this->categories as $category) {
            Category::create([
                'director_id' => $this->user->director_id,
                'name' => $category['name'],
                'type' => $category['type'],
            ]);
        }
    }

    /**
     * Тест добавления новой продажи.
     *
     * @return void
     */
    public function test_new_sale_can_be_added()
    {
        // Формируем данные для продажи
        $saleData = [
            'sale_date' => now()->toDateString(),
            'client_id' => $this->client->id,
            'director_id' => $this->user->director_id,
            'service_or_product' => 'service',
            'sport_type' => 'Футбол',
            'service_type' => 'trial',
            'subscription_start_date' => now()->toDateString(),
            'subscription_end_date' => now()->addMonth()->toDateString(),
            'cost' => 1000,
            'paid_amount' => 500,
            'pay_method' => 'Карта',
        ];

        // Аутентифицируем пользователя
        $this->actingAs($this->user);

        // Отправляем POST-запрос на добавление продажи
        $response = $this->post('/sales', $saleData);

        // Проверяем, что продажа была добавлена в базу данных
        $this->assertDatabaseHas('sales', [
            'client_id' => $this->client->id,
            'director_id' => $this->user->director_id,
            'service_or_product' => 'service',
            'sport_type' => 'Футбол',
            'service_type' => 'trial',
            'cost' => 1000,
            'paid_amount' => 500,
            'pay_method' => 'Карта',
        ]);

        // Получаем созданную продажу
        $sale = Sale::where('client_id', $this->client->id)->first();

        // Проверяем, что пользователь был перенаправлен обратно
        $response->assertRedirect();

        // Возвращаем созданную продажу для использования в других тестах
        return $sale;
    }

    /**
     * Тест редактирования существующей продажи.
     *
     * @return void
     */
    public function test_sale_can_be_updated()
    {
        // Создаем продажу
        $sale = Sale::create([
            'sale_date' => now()->toDateString(),
            'client_id' => $this->client->id,
            'director_id' => $this->user->director_id,
            'service_or_product' => 'service',
            'sport_type' => 'Футбол',
            'service_type' => 'trial',
            'subscription_start_date' => now()->toDateString(),
            'subscription_end_date' => now()->addMonth()->toDateString(),
            'cost' => 1000,
            'paid_amount' => 500,
            'pay_method' => 'Карта',
        ]);

        // Новые данные для обновления продажи
        $updatedSaleData = [
            'sale_date' => now()->toDateString(),
            'client_id' => $this->client->id,
            'director_id' => $sale->director_id,
            'service_or_product' => 'service',
            'sport_type' => 'Баскетбол',
            'service_type' => 'group',
            'subscription_start_date' => now()->toDateString(),
            'subscription_end_date' => now()->addMonths(3)->toDateString(),
            'cost' => 2000,
            'paid_amount' => 1000,
            'pay_method' => 'Наличные',
        ];

        // Аутентифицируем пользователя
        $this->actingAs($this->user);

        // Отправляем PUT-запрос на обновление продажи
        $response = $this->put("/sales/{$sale->id}", $updatedSaleData);

        // Проверяем, что продажа была обновлена в базе данных
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'client_id' => $this->client->id,
            'director_id' => $sale->director_id,
            'service_or_product' => 'service',
            'sport_type' => 'Баскетбол',
            'service_type' => 'group',
            'cost' => 2000,
            'paid_amount' => 1000,
            'pay_method' => 'Наличные',
        ]);

        // Проверяем, что пользователь был перенаправлен обратно
        $response->assertRedirect();
    }

    /**
     * Тест удаления продажи.
     *
     * @return void
     */
    public function test_sale_can_be_deleted()
    {
        // Создаем продажу
        $sale = Sale::create([
            'sale_date' => now()->toDateString(),
            'client_id' => $this->client->id,
            'director_id' => $this->user->director_id,
            'service_or_product' => 'service',
            'sport_type' => 'Футбол',
            'service_type' => 'trial',
            'subscription_start_date' => now()->toDateString(),
            'subscription_end_date' => now()->addMonth()->toDateString(),
            'cost' => 1000,
            'paid_amount' => 500,
            'pay_method' => 'Карта',
        ]);

        // Удаление продажи
        $this->actingAs($this->user);
        $response = $this->delete("/sales/{$sale->id}");

        // Проверка, что продажа удалена из базы данных
        $this->assertDatabaseMissing('sales', [
            'id' => $sale->id,
        ]);

        // Проверка, что редирект прошел успешно
        $response->assertRedirect();
    }

}
