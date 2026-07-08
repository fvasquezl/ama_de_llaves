<?php

use App\Models\ChecklistItem;
use App\Models\TareaLimpieza;
use App\Models\User;
use App\Policies\ChecklistItemPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a la camarera dueña de la tarea ver y editar el item de checklist', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id]);

    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->allows('view', $item))->toBeTrue()
        ->and(Gate::forUser($camarera)->allows('update', $item))->toBeTrue()
        ->and(Gate::forUser($camarera)->allows('viewAny', ChecklistItem::class))->toBeTrue();
});

it('niega a la camarera crear items de checklist (tiene ver/editar, no crear)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');

    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->denies('create', ChecklistItem::class))->toBeTrue();
});

it('niega a una camarera ver o editar el item de checklist de la tarea de otra camarera', function () {
    $dueña = User::factory()->create();
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $dueña->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id]);

    $otra = User::factory()->create();
    $otra->assignRole('camarera');

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $item))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $item))->toBeTrue();
});

it('permite a supervisor ver y editar cualquier item de checklist sin importar el dueño', function () {
    $dueña = User::factory()->create();
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $dueña->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $item))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $item))->toBeTrue();
});

it('permite a admin ver y editar cualquier item de checklist sin importar el dueño', function () {
    // Admin holds `checklist-limpieza.ver`/`.crear`/`.editar` (Phase 10
    // seeder gap-fix, spec-part-14 — parity with supervisor). The
    // ownership-bypass logic (`hasAnyRole`) applies regardless of owner.
    $dueña = User::factory()->create();
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $dueña->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $item))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $item))->toBeTrue();
});

it('permite a admin crear items de checklist (spec-part-14 gap fix, Phase 10 seeder)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Passport::actingAs($admin);

    expect(Gate::forUser($admin)->allows('create', ChecklistItem::class))->toBeTrue();
});

it('niega a un usuario sin permiso ver o editar el item de checklist de su propia tarea', function () {
    $usuario = User::factory()->create();
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $usuario->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $item))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $item))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', ChecklistItem::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', ChecklistItem::class))->toBeTrue();
});

it('no existe una acción delete para checklist-limpieza (el recurso no tiene ruta destroy)', function () {
    expect(method_exists(ChecklistItemPolicy::class, 'delete'))->toBeFalse();
});
