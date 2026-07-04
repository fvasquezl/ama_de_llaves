<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['auth:api', 'super.admin'])
        ->get('/api/_test/super-admin', fn () => response()->json(['ok' => true]));
});

it('permite acceso a un usuario con is_super_admin true', function () {
    $user = User::factory()->create(['is_super_admin' => true]);

    Passport::actingAs($user);

    $this->getJson('/api/_test/super-admin')->assertOk();
});

it('bloquea a un usuario con is_super_admin false', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    Passport::actingAs($user);

    $this->getJson('/api/_test/super-admin')->assertForbidden();
});

it('bloquea a un usuario no autenticado', function () {
    $this->getJson('/api/_test/super-admin')->assertUnauthorized();
});
