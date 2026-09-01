---
paths:
  - '{app/Services/**,app/Enums/Permiso.php,app/Providers/AppServiceProvider.php}'
---

# Providers

## La lógica de negocio vive en servicios; los permisos son un enum cacheado por rol
Los componentes Livewire no hacen lógica de negocio: reciben el servicio por inyección en el método de acción (`public function autenticar(AutenticacionService $servicio)`) y solo deciden qué mostrar o a dónde redirigir. Las reglas de validación viven en `App\Livewire\Forms\*`.

Los permisos se definen como casos de `App\Enums\Permiso` con valor `recurso.accion`. `AppServiceProvider::registrarGates()` publica cada caso como Gate, así que en Blade basta `@can(Permiso::UsuariosVer->value)`. Al agregar un permiso solo se añade el caso al enum y se concede en `tbl_rol.permisos_rol`.

`AccesoService` cachea los permisos por rol con `rememberForever`. Si agregas otro punto donde se modifiquen los permisos de un rol fuera de Eloquent (SQL crudo, `DB::table`), llama a `AccesoService::olvidar($idRol)`: los hooks `Rol::saved`/`Rol::deleted` solo cubren el modelo.
