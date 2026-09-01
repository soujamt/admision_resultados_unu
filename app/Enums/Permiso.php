<?php

namespace App\Enums;

/**
 * Acciones que un rol puede tener concedidas.
 *
 * Cada caso se registra como Gate en AppServiceProvider, de modo que en las
 * vistas y componentes se comprueba con `@can('usuarios.crear')` o
 * `$this->authorize(Permiso::UsuariosCrear->value)`.
 *
 * El valor sigue el formato `recurso.accion` para que agrupar por modulo sea
 * cuestion de mirar el prefijo.
 */
enum Permiso: string
{
    case UsuariosVer = 'usuarios.ver';
    case UsuariosCrear = 'usuarios.crear';
    case UsuariosEditar = 'usuarios.editar';
    case UsuariosEliminar = 'usuarios.eliminar';

    case RolesVer = 'roles.ver';
    case RolesCrear = 'roles.crear';
    case RolesEditar = 'roles.editar';
    case RolesEliminar = 'roles.eliminar';

    case FacultadesVer = 'facultades.ver';
    case FacultadesCrear = 'facultades.crear';
    case FacultadesEditar = 'facultades.editar';
    case FacultadesEliminar = 'facultades.eliminar';

    case AreasVer = 'areas.ver';
    case AreasCrear = 'areas.crear';
    case AreasEditar = 'areas.editar';
    case AreasEliminar = 'areas.eliminar';

    case CarrerasVer = 'carreras.ver';
    case CarrerasCrear = 'carreras.crear';
    case CarrerasEditar = 'carreras.editar';
    case CarrerasEliminar = 'carreras.eliminar';

    case SedesVer = 'sedes.ver';
    case SedesCrear = 'sedes.crear';
    case SedesEditar = 'sedes.editar';
    case SedesEliminar = 'sedes.eliminar';

    case AulasVer = 'aulas.ver';
    case AulasCrear = 'aulas.crear';
    case AulasEditar = 'aulas.editar';
    case AulasEliminar = 'aulas.eliminar';

    case ProcesosVer = 'procesos.ver';
    case ProcesosCrear = 'procesos.crear';
    case ProcesosEditar = 'procesos.editar';
    case ProcesosEliminar = 'procesos.eliminar';

    case VacantesVer = 'vacantes.ver';
    case VacantesEditar = 'vacantes.editar';

    case InscripcionesVer = 'inscripciones.ver';
    case InscripcionesImportar = 'inscripciones.importar';
    case InscripcionesExportar = 'inscripciones.exportar';
    case InscripcionesEliminar = 'inscripciones.eliminar';

    public function etiqueta(): string
    {
        return match ($this) {
            self::UsuariosVer => 'Ver usuarios',
            self::UsuariosCrear => 'Crear usuarios',
            self::UsuariosEditar => 'Editar usuarios',
            self::UsuariosEliminar => 'Eliminar usuarios',
            self::RolesVer => 'Ver roles',
            self::RolesCrear => 'Crear roles',
            self::RolesEditar => 'Editar roles',
            self::RolesEliminar => 'Eliminar roles',
            self::FacultadesVer => 'Ver facultades',
            self::FacultadesCrear => 'Crear facultades',
            self::FacultadesEditar => 'Editar facultades',
            self::FacultadesEliminar => 'Eliminar facultades',
            self::AreasVer => 'Ver áreas',
            self::AreasCrear => 'Crear áreas',
            self::AreasEditar => 'Editar áreas',
            self::AreasEliminar => 'Eliminar áreas',
            self::CarrerasVer => 'Ver carreras',
            self::CarrerasCrear => 'Crear carreras',
            self::CarrerasEditar => 'Editar carreras',
            self::CarrerasEliminar => 'Eliminar carreras',
            self::SedesVer => 'Ver sedes',
            self::SedesCrear => 'Crear sedes',
            self::SedesEditar => 'Editar sedes',
            self::SedesEliminar => 'Eliminar sedes',
            self::AulasVer => 'Ver aulas',
            self::AulasCrear => 'Crear aulas',
            self::AulasEditar => 'Editar aulas',
            self::AulasEliminar => 'Eliminar aulas',
            self::ProcesosVer => 'Ver procesos',
            self::ProcesosCrear => 'Crear procesos',
            self::ProcesosEditar => 'Editar procesos',
            self::ProcesosEliminar => 'Eliminar procesos',
            self::VacantesVer => 'Ver el cuadro de vacantes',
            self::VacantesEditar => 'Configurar vacantes',
            self::InscripcionesVer => 'Ver inscripciones',
            self::InscripcionesImportar => 'Importar inscripciones',
            self::InscripcionesExportar => 'Exportar inscripciones',
            self::InscripcionesEliminar => 'Eliminar inscripciones',
        };
    }

    /**
     * Recurso al que pertenece la accion, para agrupar la matriz de permisos
     * en la pantalla de roles.
     */
    public function recurso(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Todos los permisos agrupados por recurso.
     *
     * @return array<string, list<self>>
     */
    public static function agrupados(): array
    {
        $agrupados = [];

        foreach (self::cases() as $permiso) {
            $agrupados[$permiso->recurso()][] = $permiso;
        }

        return $agrupados;
    }

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
