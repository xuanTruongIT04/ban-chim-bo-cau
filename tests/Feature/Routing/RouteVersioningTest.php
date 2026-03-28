<?php

use Illuminate\Support\Facades\Route;

describe('API Route Versioning', function () {
    it('all api routes are prefixed with /api/v1/ (TECH-02)', function () {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
            ->map(fn ($route) => $route->uri());

        expect($routes)->not->toBeEmpty();

        $routes->each(function (string $uri) {
            expect($uri)->toStartWith('api/v1/');
        });
    });

    it('request to /api/admin/login (no v1) returns 404 (TECH-02)', function () {
        $response = $this->postJson('/api/admin/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(404);
    });
});
