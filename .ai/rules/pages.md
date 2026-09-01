---
paths:
  - 'resources/views/pages/**/*.php'
---

# Pages

## Excepciones en componentes Livewire de archivo único
Estos archivos se evalúan sin namespace. No importes clases globales como `RuntimeException` con `use`; PHP lo trata como redundante y Laravel lo eleva a error. Captúralas como `\RuntimeException`.
