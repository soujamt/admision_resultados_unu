---
paths:
  - '{app/Services/Admision/ListaAsistenciaPdf.php,resources/views/pdf/lista-asistencia.blade.php,resources/views/pdf/partials/tarjeta-asistencia.blade.php}'
---

# Partials

## Lista de asistencia: tarjetas con foto y código de barras
Es un documento por aula, distinto del padrón general: dos columnas de cinco tarjetas por página, y la numeración baja por la izquierda antes de pasar a la derecha. Las tarjetas van en orden alfabético, normalizando con `Str::ascii` como todo listado de apellidos del proyecto.

La geometría de la tarjeta —qué celda ocupa qué filas— está en [pdf-partials](pdf-partials.md); en resumen, la izquierda apila número, documento y foto, y el centro lleva el código de barras arriba y los datos debajo.

Los anchos de esas tres columnas van en porcentaje, no en milímetros: con `table-layout: fixed` DomPDF ignora los anchos en mm de esta tabla anidada y reparte las columnas casi iguales, dejando la huella tan ancha como los datos.

El código de barras lleva el número de documento en Code 128 (`picqer/php-barcode-generator`) para que el docente lo lea con el escáner. Va como PNG en data URI y hay que acotarlo con `width: 100%`: a un ancho fijo en milímetros DomPDF lo desborda sobre la celda de la huella.

La foto viaja incrustada en base64 desde el disco privado vía `AlmacenFotos::contenido()`. Nunca por URL: es un dato personal y DomPDF no puede pedir una ruta autenticada. Sin foto la tarjeta imprime «SIN FOTO», así que un fallo al leer el disco pasa desapercibido; el test que incrusta una foto de verdad es lo que lo cubre.

El área que se imprime es la de la carrera (`AREA 5: SCP-C - DERECHO`), no la del aula: un aula recibe carreras de áreas distintas.

Arial se resuelve a las fuentes core Helvetica de DomPDF, que no son Unicode completas. Se verificó sobre la capa de texto del PDF que sobreviven tildes y eñes; si se cambia de fuente hay que volver a comprobarlo con apellidos como SALDAÑA o REÁTEGUI.
