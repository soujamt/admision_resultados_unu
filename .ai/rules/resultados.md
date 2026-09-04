---
paths:
  - '{app/Services/Admision/ResolverResultadosService.php,app/Services/Admision/ExamenService.php,app/Services/Admision/PadronResultadosPdf.php,app/Http/Controllers/Resultados/**,app/Models/Resultado.php,app/Models/ExamenPostulante.php,app/Enums/EstadoResultado.php,app/Enums/OrdenPadronResultados.php,resources/views/pages/resultados/procesamiento/**,resources/views/pdf/resultados/**}'
---

# Resultados

## El factor de dificultad es por carrera profesional, con umbral global
El Art. 80 aplica un unico FDE = 1 + (100 - PME) / 100 por carrera profesional, donde PME es el puntaje directo maximo obtenido en esa carrera contando todas sus modalidades y sedes. La condicion que lo dispara es global: se mide primero la adjudicacion completa sin factor y solo si el examen deja sin cubrir al menos el `umbral_factor_dificultad_exa` (40 por defecto) de las vacantes ofrecidas se recalcula todo con factor. No agrupar el factor por vacante: dos modalidades de la misma carrera deben recibir el mismo FDE.

## La nota minima del Art. 81 vive en la carrera, no en la vacante
`tbl_carrera.puntaje_minimo_car` guarda la excepcion del Art. 81 (Psicologia 55, Medicina Humana 60) y la siembra `EstructuraAcademicaSeeder`. En null, rige el minimo general de la jornada (`puntaje_minimo_exa`, 50). Nunca reintroducir un minimo por vacante: el articulo lo fija por carrera profesional y repetirlo en cada modalidad por sede es la via directa a publicar un padron con minimos inconsistentes.

## La anulacion vive en el padron del examen, no en el resultado
`ResolverResultadosService` borra y regenera `tbl_resultado` en cada corrida, asi que la anulacion de los Arts. 79, 96 y 105 al 108 se guarda en `tbl_examen_postulante` (`anulado_en_exp`, `motivo_anulacion_exp`) para sobrevivir a la regeneracion. Un anulado no recibe puntaje ni orden de merito y no consume vacante. Anular o restaurar invalida `resuelto_en_exa` y obliga a generar de nuevo.

## Art. 23: la repesca al examen ordinario es una segunda pasada
La adjudicacion resuelve primero las vacantes que no son de grupo Ordinario. Recien despues, cada vacante ordinaria compite entre sus propios postulantes y los repescados: quienes no lograron vacante por exoneracion, convenio o traslado en la misma carrera y sede. `Modalidad::pasaAlExamenOrdinario()` excluye al `EXO_CEPREUNU` porque el propio articulo lo exceptua; reserva y PRONABEC tampoco entran. El repescado que gana queda con `repesca_res` en true y con el `id_vac` de la vacante ordinaria.

## Solo cuentan las vacantes habilitadas, y las plazas son `plazas()`
Toda lectura del cuadro de vacantes para resolver o para medir desiertas usa el scope `habilitada()`. Una vacante deshabilitada no ofrece cupos ni engorda el denominador del porcentaje de desiertas ni el del examen complementario de la Disposicion Complementaria Decima Segunda (>20% en tercera convocatoria). Las plazas que se reparten son `Vacante::plazas()`, no `cantidad_vac`: incluyen el arrastre de los Arts. 17, 18 y 19 descrito en [ingresantes](ingresantes.md).

## El PDF del Art. 84 lo arma un servicio, no el controlador
`PadronResultadosPdf` construye los datos del padron y los dos controladores lo usan: uno entrega un PDF suelto y otro el juego completo en ZIP, con el general y uno por carrera.

El formato es el que ya publica la Direccion de Admision: cabecera, listado y nada mas. La carrera va en su columna de cada fila, no en el titulo, y el estado cierra la linea. **No se imprime resumen, ni factor de dificultad, ni linea de corte** entre la cabecera y los resultados; eso se saco a pedido de la Direccion y no debe volver.

## Los tres ordenes del padron de resultados
`OrdenPadronResultados` decide como sale el listado sin filtrar: `PorCarrera` reparte una seccion por carrera con salto de pagina, `Alfabetico` publica todo de corrido por apellidos y `Merito` todo de corrido por `orden_general_res`. El titulo del documento sale del propio enum, y el orden viaja por la query (`?orden=`).

Filtrar por carrera o vacante manda sobre el enum: ahi siempre es una sola seccion en su orden de merito interno, porque es el PDF que se publica por carrera.

El alfabetico normaliza con `Str::ascii`, como todo listado de apellidos del proyecto: comparado byte a byte la «A» con tilde cae despues de la «B». En cualquiera de los tres, el NSP y el anulado figuran con el puntaje vacio y quedan al final del orden de merito, que es lo que pide el Art. 76.

## Puntajes y empates
Los valores del Art. 77 (+1 acierto, -0,01 error, +0,1 blanco) son configurables por jornada. El empate en el ultimo puesto admite a todos los empatados por el Art. 85. El NSP del Art. 76 alcanza tanto al que figura en el padron sin lectura optica como al inscrito que no aparece en el padron del escaner; ver [models-services-admision](models-services-admision.md) para como entra ese segundo caso.
