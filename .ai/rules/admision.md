---
paths:
  - '{app/Models/Vacante.php,app/Models/ProcesoModalidad.php,app/Services/Admision/**}'
  - 'resources/views/pages/configuracion/vacantes/**'
---

# Admision

## Los códigos del formato oficial viven en la oferta del proceso, no en el catálogo
El formato de inscripción que se reporta al MINEDU/SUNEDU renumera los códigos en cada proceso y además los cambia según la modalidad: la misma carrera es 2562 por Exoneración CEPREUNU y 2576 por Reserva CEPREUNU en 2027-I. Por eso `codigo_externo_vac` está en `tbl_vacante` (proceso+modalidad+carrera+sede) y `codigo_lugar_prm` en `tbl_proceso_modalidad`, nunca en `tbl_carrera` ni en `tbl_modalidad`. Al importar inscripciones, el CODIGO_CARRERA de la fila se traduce a carrera/modalidad/sede buscando la vacante del proceso; si no existe, la fila se rechaza. Corre siempre `admision:importar-oferta` antes de `admision:importar-inscripciones`.

## Los Arts. 14 y 16 avisan, no bloquean
`ValidadorCuadroVacantes` contrasta el cuadro general del año contra el reparto 25/25/50 del Art. 14 y contra el tope del 30% del CEPREUNU por Escuela Profesional del Art. 16, y se muestra en la pantalla del cuadro. No impide guardar: la Primera Disposición Transitoria somete la cifra del Art. 16 a lo que aprueba cada Consejo de Facultad y ratifica el Consejo Universitario, así que la última palabra no es del sistema.

Dos cosas que hay que respetar al tocarlo. La medición va sobre `cantidad_vac`, nunca sobre `Vacante::plazas()`: el arrastre de los Arts. 17, 18 y 19 engorda la tercera convocatoria por mandato del reglamento y contarlo haría fallar el reparto siempre. Y el reparto solo se juzga cuando existen las tres convocatorias del año; con el cuadro a medio configurar se informa qué falta y no se acusa desvío.

El cupo del Art. 16 suma las dos modalidades del convenio, exoneración y reserva, que es lo que resuelve `Modalidad::esCepreunu()` por el prefijo del `codigo_mod`. La tolerancia de un punto porcentual del Art. 14 existe porque el cuadro se arma carrera por carrera en enteros y un 25% exacto casi nunca sale redondo.
