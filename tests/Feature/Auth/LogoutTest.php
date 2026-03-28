<?php

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Laravel\Sanctum\Sanctum;

describe('POST /api/v1/admin/logout', function () {
    it('deletes the current Sanctum token and returns success (AUTH-04)', function () {
        $user = UserModel::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/admin/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('message', 'Đã đăng xuất thành công.');

        expect($user->tokens()->count())->toBe(0);
    });

    it('subsequent request with deleted token returns 401 (AUTH-04)', function () {
        $user = UserModel::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('secret1234'),
        ]);

        // Login to get a real token
        $loginResponse = $this->postJson('/api/v1/admin/login', [
            'email'    => $user->email,
            'password' => 'secret1234',
        ]);
        $token = $loginResponse->json('token');

        // Logout with that token
        $this->withHeader('Authorization', "Bearer {$token}")
             ->postJson('/api/v1/admin/logout')
             ->assertStatus(200);

        // Verify the token is deleted from the database
        expect($user->tokens()->count())->toBe(0);

        // Reset headers and flush the auth guard cache so the next request
        // is truly stateless — same as a new HTTP request would be
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        // Try to use the same (now deleted) token — should be rejected
        $this->withHeader('Authorization', "Bearer {$token}")
             ->postJson('/api/v1/admin/logout')
             ->assertStatus(401)
             ->assertJsonPath('code', 'UNAUTHENTICATED');
    });
});
