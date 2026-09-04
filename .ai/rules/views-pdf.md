---
paths:
  - '{app/Models/Aula.php,resources/views/pdf/padron-postulantes.blade.php,resources/views/pdf/lista-asistencia.blade.php}'
---

# Views Pdf

## Los padrones imprimen el pabellón y el aula en corto
El maestro guarda etiquetas largas: `pabellon_aul` viene como «PAB I - Piso 2» y `nombre_aul` como «Aula 8». Los padrones titulan esas columnas «Pabellón» y «Aula», así que repetir el prefijo en cada fila es ruido y el piso no ubica a nadie, porque el aula ya lo dice.

Usa `Aula::numeroDePabellon()` («I») y `Aula::numeroDeAula()` («8») en cualquier listado, nunca las columnas crudas. Ambos degradan con gracia: si el pabellón no lleva numeral romano devuelven lo que haya sin el piso, y si el nombre del aula no empieza por «Aula» se devuelve entero.

`Aula::etiqueta()` sigue dando la forma larga («PAB I - Piso 2 · Aula 8») y es la que va en pantallas y pies de página, donde no hay una columna que dé el contexto.
