---
paths:
  - '{resources/views/pdf/lista-asistencia.blade.php,resources/views/pdf/partials/tarjeta-asistencia.blade.php}'
---

# Pdf Partials

## La tarjeta de asistencia conserva divisiones asimétricas
La huella ocupa las tres filas superiores de la columna derecha; los datos ocupan dos filas centrales; la foto ocupa las dos filas inferiores de la izquierda; y la firma usa colspan=2 bajo las columnas central y derecha. No agregar celdas vacías en las filas cubiertas por rowspan, porque DomPDF crea una cuarta columna y rompe el diseño institucional.

## El alto de la tarjeta de asistencia se mide sobre el PDF, no se calcula
Las cinco filas de tarjetas tienen que caber entre la cabecera y el recuadro de observaciones: el flujo acaba en 265mm (297 de la hoja menos los margenes de 8mm y 32mm del @page). Si se pasan, DomPDF baja la quinta fila a otra hoja y el pie miente («Pagina 4 de 3»), porque ese total sale de contar secciones, no paginas reales.

Dos trampas comprobadas midiendo el PDF, no leyendo el HTML:

1. `height` sobre los `<tr>` (`.fila-codigo`, `.fila-foto`, `.fila-firma`...) NO hace nada: con `table-layout: fixed` y celdas combinadas DomPDF lo ignora en los dos sentidos. Se probo de 1mm a 34mm sin que cambiara un pixel. En la celda si funciona.

2. El alto lo gobiernan `.celda-datos { height }` y `.foto { max-height }`. La foto manda porque su celda abarca las dos filas de abajo, y su tope tiene que quedar por debajo del alto que ya le impone el ancho de columna (~25.8mm) para que toda foto vertical mida igual. Cada milimetro de foto mueve la rejilla unos 5mm, uno por fila.

`.celda-datos` lleva altura fija (11mm) para que la tarjeta mida lo mismo con nombres cortos que largos. Sin ella crecia cuando el nombre o la carrera saltaban a dos lineas, y habia que dejar de reserva ese alto: la hoja quedaba a media pagina del recuadro con apellidos cortos y se partia con los largos.

Para tantear valores: genera el PDF, pasalo a PNG con Imagick y busca las lineas horizontales oscuras. Verifica siempre contra nombres de dos y tres lineas, no solo contra los cortos.

## La caja de observaciones crece hacia arriba y le quita sitio a las tarjetas
`.pie` va `position: fixed` con la base anclada, asi que agrandar la caja de observaciones sube su borde superior en vez de bajar el inferior: cada milimetro de `.renglon` la sube tres, uno por renglon. Y por ir fija no aparta a las tarjetas, las pisaria.

El techo lo pone la rejilla en el PEOR caso, no en el normal. Con nombres de una o dos lineas la quinta fila cierra en 253.8mm, pero con uno de tres llega a 258.4mm; el borde de la caja esta en 261.9mm, asi que el margen real es de 3.5mm, no de 8.1mm.

La hoja esta llena: para agrandar las observaciones hay que bajar antes el tope de `.foto` (1mm de foto = 5mm de rejilla, uno por fila). Hoy son 22mm de foto y 6mm de renglon. Abajo no hay de donde sacar: entre la caja y `.pie-linea` solo median 2mm, y debajo del pie quedan ~5mm hasta el borde de la hoja, que es margen de impresora.
