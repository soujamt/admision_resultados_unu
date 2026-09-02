---
paths:
  - 'resources/views/pages/resultados/aulas/**'
  - resources/views/pages/resultados/aulas/aulas.blade.php
---

# Aulas

## Select de aulas estable en la distribución
Mantener todas las aulas habilitadas como opciones y deshabilitar las ya asignadas. No reducir la lista después de guardar: el cambio de posición puede dejar una selección visual distinta al estado Livewire. Usar wire:model diferido para aula, área y capacidad, de modo que se envíen juntos al hacer submit y no se renderice toda la pantalla en cada selección.

## Placeholder seleccionable en los selectores de aula y área
No usar el atributo placeholder de flux:select en Aula o Área porque genera una opción disabled. Cuando las primeras aulas están deshabilitadas, el navegador muestra la primera disponible aunque Livewire conserve null. Renderizar una flux:select.option explícita con value="" y sin disabled, para que lo visible coincida con el estado diferido.
