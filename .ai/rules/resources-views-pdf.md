---
paths:
  - 'resources/views/pdf/**'
---

# Resources Views Pdf

## Los meses en letras se escriben «setiembre», no «septiembre»
Los documentos que publica la Direccion de Admision usan «setiembre», la forma corriente en el Peru. Carbon trae «septiembre» en su locale `es`, asi que `AppServiceProvider::nombrarLosMesesComoEnElPeru()` sobrescribe `months` y `months_short` del traductor global con `setMessages('es', ...)`, que carga el archivo del locale y encima mezcla el override.

En las vistas se sigue usando `translatedFormat('d \d\e F \d\e Y')` sin tocar nada: la correccion es central y vale para cualquier fecha con el mes en letras. No armes el nombre del mes a mano ni parchees vista por vista.
