<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('openai_thread_id')->unique();
            $table->string('title')->nullable();
            $table->string('module_filter')->nullable();
            $table->string('chat_type')->default('support');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_type')->default('solo');
            $table->string('desk365_ticket_id')->nullable();
            $table->json('participant_ids')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'chat_type']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->json('citations')->nullable();
            $table->foreignId('reply_to_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->foreignId('reply_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mention_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });

        Schema::create('chat_session_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('chat_session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'chat_session_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('chat_message_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'chat_message_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_favorites');
        Schema::dropIfExists('chat_session_favorites');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};
