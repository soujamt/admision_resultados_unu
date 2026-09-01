---
paths:
  - 'resources/views/pages/**/*.php'
  - 'resources/views/pages/**/*.blade.php'
---

# Pages

## Excepciones en componentes Livewire de archivo único
Estos archivos se evalúan sin namespace. No importes clases globales como `RuntimeException` con `use`; PHP lo trata como redundante y Laravel lo eleva a error. Captúralas como `\RuntimeException`.

## Un select cuyas opciones cambian necesita wire:key
Si un `<flux:select>` se llena con una lista que se encoge o crece tras una acción (por ejemplo aulas disponibles, que excluye las ya asignadas), hay que darle un `wire:key` derivado de esa lista. Sin él Livewire reutiliza el mismo `<select>` al morphear el DOM, el navegador conserva la posición elegida y esa posición ya apunta a otra opción: el usuario ve una seleccionada y el servidor recibe vacío, con el consiguiente "El campo es obligatorio" sobre un campo que se ve lleno. Patrón usado en `pages/resultados/aulas`: la vista recibe `claveAulas = md5(ids disponibles)` y el select lleva `wire:key="aula-disponible-{$claveAulas}"`.
