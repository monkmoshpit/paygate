<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the payments endpoint validates required fields', function () {
    $response = $this->postJson('/api/payments', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_id', 'amount', 'idempotency_key']);
});
