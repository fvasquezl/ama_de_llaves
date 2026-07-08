<?php

use App\Models\Inspeccion;
use App\Models\User;
use App\Policies\InspeccionPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

/*
 * `supervisor` is itself one of the elevated bypass roles
 * (`hasAnyRole(['supervisor', 'admin'])`), so it can't be used to exercise
 * the direct-ownership branch (`supervisora_id === $user->id`) — a
 * supervisor always bypasses ownership regardless of who owns the row.
 * Non-elevated, permission-holding actors are simulated via direct
 * `givePermissionTo()` grants (no role assigned), consistent with
 * spec-part-16's own framing: "structurally consistent but currently
 * inert" since no real seeded role holds `inspecciones.ver`/`.crear`
 * without also being `supervisor`/`admin` today.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a un usuario no elevado con permiso ver su propia inspección', function () {
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('inspecciones.ver');
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $usuario->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->allows('view', $inspeccion))->toBeTrue();
});

it('niega a un usuario no elevado con permiso ver la inspección de otro usuario', function () {
    $dueño = User::factory()->create();
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $dueño->id]);

    $otro = User::factory()->create();
    $otro->givePermissionTo('inspecciones.ver');

    Passport::actingAs($otro);

    expect(Gate::forUser($otro)->denies('view', $inspeccion))->toBeTrue();
});

it('permite a supervisor y admin ver cualquier inspección sin importar el dueño', function () {
    $dueño = User::factory()->create();
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $dueño->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $inspeccion))->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $inspeccion))->toBeTrue();
});

it('permite crear a quien tiene el permiso inspecciones.crear sin importar el rol', function () {
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('inspecciones.crear');

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->allows('create', Inspeccion::class))->toBeTrue();
});

it('permite a admin crear inspecciones (spec-part-16 gap fix, Phase 10 seeder)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Passport::actingAs($admin);

    expect(Gate::forUser($admin)->allows('create', Inspeccion::class))->toBeTrue();
});

it('niega a un usuario sin permiso ver, crear inspecciones', function () {
    $usuario = User::factory()->create();
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $usuario->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $inspeccion))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', Inspeccion::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', Inspeccion::class))->toBeTrue();
});

it('no existen acciones update ni delete para inspecciones (el recurso es inmutable)', function () {
    expect(method_exists(InspeccionPolicy::class, 'update'))->toBeFalse()
        ->and(method_exists(InspeccionPolicy::class, 'delete'))->toBeFalse();
});
