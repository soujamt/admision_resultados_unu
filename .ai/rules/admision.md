---
paths:
  - '{app/Models/Vacante.php,app/Models/ProcesoModalidad.php,app/Services/Admision/**}'
---

# Admision

## Los códigos del formato oficial viven en la oferta del proceso, no en el catálogo
El formato de inscripción que se reporta al MINEDU/SUNEDU renumera los códigos en cada proceso y además los cambia según la modalidad: la misma carrera es 2562 por Exoneración CEPREUNU y 2576 por Reserva CEPREUNU en 2027-I. Por eso `codigo_externo_vac` está en `tbl_vacante` (proceso+modalidad+carrera+sede) y `codigo_lugar_prm` en `tbl_proceso_modalidad`, nunca en `tbl_carrera` ni en `tbl_modalidad`. Al importar inscripciones, el CODIGO_CARRERA de la fila se traduce a carrera/modalidad/sede buscando la vacante del proceso; si no existe, la fila se rechaza. Corre siempre `admision:importar-oferta` antes de `admision:importar-inscripciones`.
