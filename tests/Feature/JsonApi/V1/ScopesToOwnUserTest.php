<?php

use App\JsonApi\V1\Concerns\ScopesToOwnUser;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Exercise ScopesToOwnUser::mustScopeToOwnUser() in isolation via an
 * anonymous class, mirroring the trait's single responsibility (see
 * design part-08/09: the shared surface area is deliberately limited to
 * this one boolean bypass check, so it is the only thing that needs a
 * dedicated shared test — the per-domain FK-walking logic is tested
 * independently per Schema/Policy instead).
 */
function mustScopeToOwnUser(?Request $request): bool
{
    $probe = new class
    {
        use ScopesToOwnUser;

        public function check(?Request $request): bool
        {
            return $this->mustScopeToOwnUser($request);
        }
    };

    return $probe->check($request);
}

it('does not scope a guest request (no authenticated user)', function () {
    $request = Request::create('/api/v1/tareas-limpieza');
    $request->setUserResolver(fn () => null);

    expect(mustScopeToOwnUser($request))->toBeFalse();
});

it('does not scope a super-admin request', function () {
    $user = User::factory()->superAdmin()->create();

    $request = Request::create('/api/v1/tareas-limpieza');
    $request->setUserResolver(fn () => $user);

    expect(mustScopeToOwnUser($request))->toBeFalse();
});

it('does not scope a supervisor request', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->assignRole('supervisor');

    $request = Request::create('/api/v1/tareas-limpieza');
    $request->setUserResolver(fn () => $user);

    expect(mustScopeToOwnUser($request))->toBeFalse();
});

it('does not scope an admin request', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->assignRole('admin');

    $request = Request::create('/api/v1/tareas-limpieza');
    $request->setUserResolver(fn () => $user);

    expect(mustScopeToOwnUser($request))->toBeFalse();
});

it('scopes a plain (non-elevated) authenticated user request', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->assignRole('camarera');

    $request = Request::create('/api/v1/tareas-limpieza');
    $request->setUserResolver(fn () => $user);

    expect(mustScopeToOwnUser($request))->toBeTrue();
});
