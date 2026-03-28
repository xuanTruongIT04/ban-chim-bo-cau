<?php

describe('JSON Error Envelope', function () {
    it('returns { success, code, message, errors } on validation error (TECH-03)', function () {
        $response = $this->postJson('/api/v1/admin/login', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['success', 'code', 'message', 'errors'])
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('code', 'VALIDATION_ERROR');
    });

    it('returns 401 envelope on unauthenticated admin route request (TECH-03)', function () {
        $response = $this->postJson('/api/v1/admin/logout');

        $response->assertStatus(401)
                 ->assertJsonStructure(['success', 'code', 'message', 'errors'])
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('code', 'UNAUTHENTICATED');
    });

    it('returns 404 envelope on non-existent route (TECH-03)', function () {
        $response = $this->getJson('/api/v1/nonexistent');

        $response->assertStatus(404)
                 ->assertJsonStructure(['success', 'code', 'message', 'errors'])
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('code', 'NOT_FOUND');
    });

    it('never returns HTML on api/* routes even without Accept header (TECH-03)', function () {
        // Use post() instead of postJson() — no Accept: application/json header
        $response = $this->post('/api/v1/admin/logout');

        $response->assertStatus(401);
        $contentType = $response->headers->get('Content-Type');
        expect($contentType)->toContain('application/json');
    });
});
