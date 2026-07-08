<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\AlertasRonda\AlertaRondaSchema;
use App\JsonApi\V1\ChecklistEnfermeria\ChecklistEnfermeriaItemSchema;
use App\JsonApi\V1\ChecklistLimpieza\ChecklistItemSchema;
use App\JsonApi\V1\Estancias\EstanciaSchema;
use App\JsonApi\V1\Habitaciones\HabitacionSchema;
use App\JsonApi\V1\Inspecciones\InspeccionSchema;
use App\JsonApi\V1\ReportesEnfermeria\ReporteEnfermeriaSchema;
use App\JsonApi\V1\ReportesMantenimiento\ReporteMantenimientoSchema;
use App\JsonApi\V1\Residentes\ResidenteSchema;
use App\JsonApi\V1\RondasEnfermeria\RondaEnfermeriaSchema;
use App\JsonApi\V1\Sucursales\SucursalSchema;
use App\JsonApi\V1\TareasLimpieza\TareaLimpiezaSchema;
use App\JsonApi\V1\Users\UserSchema;
use App\JsonApi\V1\VisitasHabitacion\VisitaHabitacionSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            RondaEnfermeriaSchema::class,
            VisitaHabitacionSchema::class,
            ChecklistEnfermeriaItemSchema::class,
            ReporteEnfermeriaSchema::class,
            AlertaRondaSchema::class,
            SucursalSchema::class,
            HabitacionSchema::class,
            ResidenteSchema::class,
            EstanciaSchema::class,
            TareaLimpiezaSchema::class,
            ChecklistItemSchema::class,
            InspeccionSchema::class,
            ReporteMantenimientoSchema::class,
            // Relationship-target-only schemas (no HTTP routes mounted for
            // these — see each schema's docblock for why they're needed).
            UserSchema::class,
        ];
    }
}
