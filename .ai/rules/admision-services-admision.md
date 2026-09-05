---
paths:
  - '{app/Services/Admision/PadronAulaPdf.php,app/Services/Admision/PadronPostulantesPdf.php,app/Services/Admision/ListaAsistenciaPdf.php}'
---

# Admision Services Admision

## Un aula tiene dos documentos, y los dos padrones comparten la vista
De cada aula salen dos PDF y se confunden facil:

- `PadronAulaPdf` va alfabetico y se publica: el postulante busca su apellido y lee su carpeta. Boton «Padrón» en la pantalla de aulas.
- `ListaAsistenciaPdf` va por carpeta y la recorre el docente asiento por asiento, marcando. Boton «Asistencia».

Es la misma gente en el mismo orden inverso, asi que al tocar uno hay que preguntarse si el cambio vale para el otro; casi nunca vale.

`PadronAulaPdf` y `PadronPostulantesPdf` comparten la vista `pdf/padron-postulantes.blade.php`, parametrizada con `$titulo` y un `$aulaCabecera` opcional. Es a proposito: la Direccion pide que el padron por aula tenga «el mismo formato que el general», y con una sola vista los formatos no pueden separarse con el tiempo. No dupliques la vista para variar un titulo. La vista exige `$titulo`, asi que sus datos se arman siempre por el servicio, nunca con un arreglo a mano en un test.
