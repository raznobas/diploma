<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // История переписки сохраняется от интеграции wazzup
        Schema::create('chat_message_history', function (Blueprint $table) {
            $table->uuid('messageId')->primary(); // Уникальный идентификатор сообщения
            $table->uuid('channelId'); // ID канала
            $table->string('chatType'); // Тип чата (whatsapp, telegram и т.д.)
            $table->string('chatId'); // ID чата (аккаунт контакта)
            $table->dateTime('dateTime'); // Время отправки сообщения
            $table->string('type'); // Тип сообщения (текст, изображение и т.д.)
            $table->string('status'); // Статус сообщения (отправлено, доставлено и т.д.)
            $table->text('text')->nullable(); // Текст сообщения
            $table->string('contentUri')->nullable(); // Ссылка на контент
            $table->string('authorId')->nullable(); // Идентификатор пользователя CRM
            $table->string('authorName')->nullable(); // Имя пользователя CRM
            $table->boolean('isEcho')->nullable(); // Флаг исходящего сообщения

            // Информация о контакте
            $table->string('contact_name')->nullable(); // Имя контакта
            $table->string('contact_username')->nullable(); // Username контакта (для Telegram)
            $table->string('contact_phone')->nullable(); // Телефон контакта (для Telegram)

            // Дополнительные данные
            $table->json('error')->nullable(); // Информация об ошибке
            $table->json('quotedMessage')->nullable(); // Цитируемое сообщение

            // Индексы для ускорения поиска
            $table->index('channelId');
            $table->index('chatId');
            $table->index('dateTime');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_history');
    }
};
