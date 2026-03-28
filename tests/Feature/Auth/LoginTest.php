<?php

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Hash;

describe('POST /api/v1/admin/login', function () {
    it('returns a Sanctum token on valid credentials (AUTH-01)', function () {
        UserModel::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data' => ['token', 'expires_at']])
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.token', fn ($v) => is_string($v) && strlen($v) > 10)
                 ->assertJsonPath('data.expires_at', fn ($v) => is_string($v) && strlen($v) > 10);
    });

    it('returns 401 INVALID_CREDENTIALS envelope on wrong password (AUTH-01)', function () {
        UserModel::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('code', 'INVALID_CREDENTIALS')
                 ->assertJsonStructure(['success', 'code', 'message', 'errors']);
    });

    it('returns 422 VALIDATION_ERROR envelope on missing fields (AUTH-01)', function () {
        $response = $this->postJson('/api/v1/admin/login', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('code', 'VALIDATION_ERROR')
                 ->assertJsonStructure(['success', 'code', 'message', 'errors']);
    });

    it('created token has non-null expires_at (AUTH-02)', function () {
        $user = UserModel::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        $this->postJson('/api/v1/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'secret1234',
        ])->assertStatus(200);

        $token = $user->tokens()->latest()->first();
        expect($token->expires_at)->not->toBeNull();
    });

    it('validation error message for missing email is in Vietnamese (TECH-04)', function () {
        $response = $this->postJson('/api/v1/admin/login', [
            'password' => 'secret1234',
        ]);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $emailError = $errors['email'][0] ?? '';
        expect($emailError)->toContain('bắt buộc');
    });
});
