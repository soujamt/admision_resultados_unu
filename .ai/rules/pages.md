---
paths:
  - 'resources/views/pages/**/*.php'
  - 'resources/views/pages/**/*.blade.php'
---

# Pages

## Excepciones en componentes Livewire de archivo único
Estos archivos se evalúan sin namespace. No importes clases globales como `RuntimeException` con `use`; PHP lo trata como redundante y Laravel lo eleva a error. Captúralas como `\RuntimeException`.

## Un select cuyas opciones cambian necesita wire:key
Si las opciones cambian de identidad o posición tras una acción, mantener una lista estable y deshabilitar las opciones no disponibles siempre que sea posible. Si la lista realmente debe crecer o reducirse, usar un wire:key derivado de sus IDs para impedir que el navegador conserve una selección visual que ya no corresponde al estado Livewire. En pages/resultados/aulas se usa la lista estable descrita en .ai/rules/aulas.md.
