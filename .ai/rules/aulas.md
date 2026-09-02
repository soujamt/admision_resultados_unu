---
paths:
  - 'resources/views/pages/resultados/aulas/**'
---

# Aulas

## Select de aulas estable en la distribución
Mantener todas las aulas habilitadas como opciones y deshabilitar las ya asignadas. No reducir la lista después de guardar: el cambio de posición puede dejar una selección visual distinta al estado Livewire. Usar wire:model diferido para aula, área y capacidad, de modo que se envíen juntos al hacer submit y no se renderice toda la pantalla en cada selección.
