---
paths:
  - '{app/Services/Admision/DistribucionAulasService.php,app/Livewire/Forms/DistribucionAulaForm.php,resources/views/pages/resultados/aulas/**}'
---

# Resultados Aulas

## Capacidad de aulas y carga de botones
La cantidad asignada en una jornada se valida contra capacidad_aul del aula elegida; no existe un máximo global porque cada aula puede variar. Los flux:button ya muestran su propio estado de carga: no añadir wire:loading, textos alternos ni spinner manual a esos botones. La optimización debe reducir solicitudes, consultas y renderizados.
