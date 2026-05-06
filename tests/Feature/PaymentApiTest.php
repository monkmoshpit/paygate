<?php

use App\Jobs\ProcessPaymentJob;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('it persists idempotency key and dispatches processing once', function () {
    Queue::fake();

    $payload = [
        'user_id' => 42,
        'amount' => 120.50,
        'idempotency_key' => 'idem-001',
    ];

    $response = $this->postJson('/api/payments', $payload);

    $response->assertOk()
        ->assertJsonFragment([
            'user_id' => 42,
            'idempotency_key' => 'idem-001',
        ]);

    $this->assertDatabaseHas('payments', [
        'user_id' => 42,
        'idempotency_key' => 'idem-001',
    ]);

    Queue::assertPushed(ProcessPaymentJob::class, 1);
});

test('it returns the same payment for duplicate idempotency key', function () {
    Queue::fake();

    $payload = [
        'user_id' => 7,
        'amount' => 30.00,
        'idempotency_key' => 'dup-123',
    ];

    $first = $this->postJson('/api/payments', $payload)->assertOk();
    $second = $this->postJson('/api/payments', $payload)->assertOk();

    expect($first->json('id'))->toBe($second->json('id'));
    expect(Payment::count())->toBe(1);
    Queue::assertPushed(ProcessPaymentJob::class, 1);
});
