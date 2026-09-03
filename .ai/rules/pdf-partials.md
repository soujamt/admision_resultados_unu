---
paths:
  - '{resources/views/pdf/lista-asistencia.blade.php,resources/views/pdf/partials/tarjeta-asistencia.blade.php}'
---

# Pdf Partials

## La tarjeta de asistencia conserva divisiones asimétricas
La huella ocupa las tres filas superiores de la columna derecha; los datos ocupan dos filas centrales; la foto ocupa las dos filas inferiores de la izquierda; y la firma usa colspan=2 bajo las columnas central y derecha. No agregar celdas vacías en las filas cubiertas por rowspan, porque DomPDF crea una cuarta columna y rompe el diseño institucional.
