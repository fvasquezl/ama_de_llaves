<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api';

        $permissions = [
            // Administración
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'sucursales.ver',
            'sucursales.crear',
            'sucursales.editar',
            'sucursales.eliminar',

            // Instalaciones
            'habitaciones.ver',
            'habitaciones.crear',
            'habitaciones.editar',
            'habitaciones.eliminar',

            // Residentes
            'residentes.ver',
            'residentes.crear',
            'residentes.editar',
            'residentes.eliminar',
            'estancias.ver',
            'estancias.crear',
            'estancias.editar',

            // Enfermería
            'rondas-enfermeria.ver',
            'rondas-enfermeria.crear',
            'rondas-enfermeria.editar',
            'visitas-habitacion.ver',
            'visitas-habitacion.crear',
            'visitas-habitacion.editar',
            'checklist-enfermeria.ver',
            'checklist-enfermeria.crear',
            'checklist-enfermeria.editar',
            'reportes-enfermeria.ver',
            'reportes-enfermeria.crear',
            'reportes-enfermeria.editar',
            'alertas-ronda.ver',
            'alertas-ronda.editar',

            // Limpieza
            'tareas-limpieza.ver',
            'tareas-limpieza.crear',
            'tareas-limpieza.editar',
            'checklist-limpieza.ver',
            'checklist-limpieza.crear',
            'checklist-limpieza.editar',
            'inspecciones.ver',
            'inspecciones.crear',

            // Mantenimiento
            'reportes-mantenimiento.ver',
            'reportes-mantenimiento.crear',
            'reportes-mantenimiento.editar',
        ];

        foreach ($permissions as $name) {
            Permission::create(['name' => $name, 'guard_name' => $guard]);
        }

        Role::create(['name' => 'admin', 'guard_name' => $guard])
            ->givePermissionTo([
                'usuarios.ver', 'usuarios.crear', 'usuarios.editar',
                'sucursales.ver', 'sucursales.crear', 'sucursales.editar', 'sucursales.eliminar',
                'habitaciones.ver', 'habitaciones.crear', 'habitaciones.editar', 'habitaciones.eliminar',
                'residentes.ver', 'residentes.crear', 'residentes.editar', 'residentes.eliminar',
                'estancias.ver', 'estancias.crear', 'estancias.editar',
                'rondas-enfermeria.ver', 'rondas-enfermeria.crear', 'rondas-enfermeria.editar',
                'visitas-habitacion.ver', 'visitas-habitacion.crear', 'visitas-habitacion.editar',
                'checklist-enfermeria.ver', 'checklist-enfermeria.crear', 'checklist-enfermeria.editar',
                'reportes-enfermeria.ver', 'reportes-enfermeria.crear', 'reportes-enfermeria.editar',
                'alertas-ronda.ver', 'alertas-ronda.editar',
                'tareas-limpieza.ver', 'tareas-limpieza.crear', 'tareas-limpieza.editar',
                'checklist-limpieza.ver', 'checklist-limpieza.crear', 'checklist-limpieza.editar',
                'inspecciones.ver', 'inspecciones.crear',
                'reportes-mantenimiento.ver', 'reportes-mantenimiento.crear', 'reportes-mantenimiento.editar',
            ]);

        Role::create(['name' => 'supervisor', 'guard_name' => $guard])
            ->givePermissionTo([
                'usuarios.ver',
                'habitaciones.ver',
                'residentes.ver',
                'estancias.ver',
                'rondas-enfermeria.ver', 'rondas-enfermeria.crear', 'rondas-enfermeria.editar',
                'visitas-habitacion.ver', 'visitas-habitacion.crear', 'visitas-habitacion.editar',
                'checklist-enfermeria.ver', 'checklist-enfermeria.crear', 'checklist-enfermeria.editar',
                'reportes-enfermeria.ver', 'reportes-enfermeria.crear', 'reportes-enfermeria.editar',
                'alertas-ronda.ver', 'alertas-ronda.editar',
                'tareas-limpieza.ver', 'tareas-limpieza.crear', 'tareas-limpieza.editar',
                'checklist-limpieza.ver', 'checklist-limpieza.crear', 'checklist-limpieza.editar',
                'inspecciones.ver', 'inspecciones.crear',
                'reportes-mantenimiento.ver', 'reportes-mantenimiento.crear', 'reportes-mantenimiento.editar',
            ]);

        Role::create(['name' => 'camarera', 'guard_name' => $guard])
            ->givePermissionTo([
                'habitaciones.ver',
                'tareas-limpieza.ver', 'tareas-limpieza.editar',
                'checklist-limpieza.ver', 'checklist-limpieza.editar',
                'reportes-mantenimiento.ver', 'reportes-mantenimiento.crear',
            ]);

        Role::create(['name' => 'enfermera', 'guard_name' => $guard])
            ->givePermissionTo([
                'habitaciones.ver',
                'residentes.ver',
                'estancias.ver', 'estancias.crear',
                'rondas-enfermeria.ver', 'rondas-enfermeria.crear', 'rondas-enfermeria.editar',
                'visitas-habitacion.ver', 'visitas-habitacion.crear', 'visitas-habitacion.editar',
                'checklist-enfermeria.ver', 'checklist-enfermeria.crear', 'checklist-enfermeria.editar',
                'reportes-enfermeria.ver', 'reportes-enfermeria.crear', 'reportes-enfermeria.editar',
                'alertas-ronda.ver', 'alertas-ronda.editar',
            ]);
    }
}
