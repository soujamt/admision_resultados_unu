---
paths:
  - '{app/Services/Admision/ResolverResultadosService.php,app/Models/ExamenPostulante.php,app/Services/Admision/GeneradorLecturaOptica.php}'
---

# Models Services Admision

## La cartilla nula marca al no presentado (Art. 76)
El lector óptico entrega el mismo número de filas en el TXT de padrón y en el de respuestas: una tarjeta por fila. Quien no rindió no figura en ninguno de los dos, así que el NSP no se puede deducir de "está en el padrón y no tiene respuesta".

Por eso `ResolverResultadosService::completarNoPresentados()` abre una fila de `tbl_examen_postulante` por cada inscripción vigente del proceso que falte en la jornada, con `codigo_cartilla_exp` en nulo: nunca recibió tarjeta. Sin eso el inscrito que no se presenta desaparece del padrón oficial en vez de publicarse como NSP.

La cartilla nula es la única señal que separa lo que entregó el escáner de lo que completó la resolución. Úsala con el scope `ExamenPostulante::delLector()` en cualquier estadística que deba decir "lo que importé" (el contador "Padrón" de la pantalla de resultados), o el número saltará al resolver. El único (id_exa, codigo_cartilla_exp) sigue valiendo porque MySQL admite varios nulos.

`GeneradorLecturaOptica` respeta el mismo contrato: la opción `--ausentes` deja al postulante fuera de los dos archivos, no solo del de respuestas.
