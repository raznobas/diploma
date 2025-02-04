<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Получаем данные о зале, если пользователь авторизован
        $gym = null;
        if ($user) {
            $gym = \App\Models\Gym::where('director_id', $user->director_id)->first();
        }

        $wazzup_user = null;
        if ($user) {
            $wazzup_user = \App\Models\WazzupUsers::where('director_id', $user->director_id)->first();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'role' => $user ? $user->roles->first()->name : null,
                'abilities' => $user ? $user->getAbilities()->pluck('name')->toArray() : [],
                'director_id' => $user ? $user->director_id : null,
                'gym' => $gym ? [ // Добавляем данные о зале
                    'label' => $gym->label,
                ] : null,
                'wazzup_user' => $wazzup_user ? [
                    'id' => $wazzup_user->wazzup_id,
                    'name' => $wazzup_user->name,
                ] : null,
            ],
        ];
    }
}
