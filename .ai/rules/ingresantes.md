---
paths:
  - '{app/Services/Admision/IngresanteService.php,app/Services/Admision/ArrastreVacantesService.php,app/Models/Ingresante.php,app/Enums/CondicionIngresante.php,resources/views/pages/resultados/ingresantes/**}'
---

# Ingresantes

## El padron de ingresantes es una tabla aparte porque el Art. 72 congela los resultados
`tbl_resultado` guarda el resultado del examen, que el Art. 72 declara irrevisable e inmodificable. Quien es ingresante, en cambio, cambia despues de publicado el padron: se renuncia, no se completa expediente, no se matricula y por el Art. 93 entra alguien que en el examen figura como no ingreso. Por eso el ciclo de vida vive en `tbl_ingresante` y nunca se toca `estado_res` para reflejarlo. Volver a resolver la jornada no borra una renuncia: `IngresanteService::generar()` conserva `condicion_ing` de quien ya estaba y solo retira a los vigentes de esa misma jornada que dejaron de ser ingresantes.

## `id_exa` en el padron evita que una jornada pise a otra
Un proceso puede tener varias jornadas. `generar()` retira unicamente ingresantes con el `id_exa` de la jornada que se esta regenerando, asi que generar desde la segunda jornada no borra los ingresantes de la primera. Los sustitutos del Art. 93 (`id_sustituido_ing` no nulo) nunca se retiran automaticamente.

## Art. 93: el sustituto sale de la misma modalidad, salvo la reserva
El inmediato inferior se busca siempre en la tercera convocatoria del mismo anio, en la misma carrera y sede, entre los `no_ingreso` que alcanzaron su `puntaje_minimo_res`, y de la misma modalidad que la vacante liberada. La unica excepcion es la reserva: el ultimo parrafo del articulo manda buscarlo en el examen ordinario. El sustituto ocupa el `id_vac` que quedo libre, no el suyo, y restaurar la condicion del titular borra al sustituto.

## El arrastre se guarda aparte de la cifra que aprueba el Consejo Universitario
Los Arts. 17, 18 y 19 escriben en `cantidad_arrastre_vac`, nunca sobre `cantidad_vac`, que es la cifra del Art. 15. Las plazas reales son `Vacante::plazas()` y toda adjudicacion y todo conteo de desiertas usa ese metodo. `ArrastreVacantesService::aplicar()` recalcula el total entero en cada pasada y lo asigna, no lo suma, para que ejecutarlo dos veces no duplique plazas.

## Que cuenta cada articulo, sin contarlo dos veces
Por vacante de origen: el Art. 17 aporta `plazas - ingresantes registrados` (las nunca cubiertas) y el Art. 18 aporta los ingresantes cuya condicion `generaArrastre()`, es decir expediente incompleto y constancia no recogida. Sumar en cambio `plazas - vigentes` a los liberados contaria dos veces las mismas plazas. El Art. 19 aplica dentro de la propia tercera convocatoria y manda al examen ordinario de la misma carrera y sede lo no cubierto por exoneracion, convenio, PRONABEC y traslado.

## La falta de matricula no arrastra: la resuelve el Art. 93
Una plaza liberada por no matricularse no entra en el arrastre porque el Art. 93 la cubre llamando al inmediato inferior. Cuando no hubo a quien llamar, `calcular()` la informa en `sin_sustituto` para que la comision lo decida; el reglamento no la arrastra por si sola.

## El arrastre exige el padron de ingresantes de la convocatoria de origen
Las plazas cubiertas se miden contra `tbl_ingresante`, no contra los resultados. Si un proceso de origen tiene jornadas resueltas y ningun ingresante registrado, `calcular()` falla en vez de reportar todo como desierto. Despues de aplicar el arrastre hay que volver a generar los resultados para que el resolver reparta las plazas nuevas.
