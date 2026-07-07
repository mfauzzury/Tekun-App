<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserChatTest extends TestCase
{
    use RefreshDatabase;

    private function userWithChatPermission(): User
    {
        $role = Role::create([
            'name' => 'chat-user',
            'description' => 'AINA chat',
            'permissions' => [Permission::CHAT_USE],
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_suggestions_requires_authentication(): void
    {
        $this->getJson('/api/chat/user/suggestions')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_suggestions_forbidden_without_permission(): void
    {
        $role = Role::create([
            'name' => 'no-chat',
            'description' => 'No chat',
            'permissions' => ['posts.view'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/chat/user/suggestions')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_suggestions_returns_starter_prompts(): void
    {
        Sanctum::actingAs($this->userWithChatPermission());

        $this->getJson('/api/chat/user/suggestions')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'label', 'module']]]);
    }

    public function test_my_sessions_returns_empty_list_initially(): void
    {
        Sanctum::actingAs($this->userWithChatPermission());

        $this->getJson('/api/chat/user/sessions')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
