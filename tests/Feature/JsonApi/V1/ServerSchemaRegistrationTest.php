<?php

use LaravelJsonApi\Contracts\Server\Repository as ServerRepository;

it('resolves all 5 enfermeria resource types on the v1 server', function (string $type) {
    $server = app(ServerRepository::class)->server('v1');

    $schema = $server->schemas()->schemaFor($type);

    expect($schema->type())->toBe($type);
})->with([
    'rondas-enfermeria',
    'visitas-habitacion',
    'checklist-enfermeria',
    'reportes-enfermeria',
    'alertas-ronda',
]);
