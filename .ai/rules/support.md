---
paths:
  - '{app/Services/Excel/**,tests/Support/**}'
---

# Support

## Los .xlsx se leen con LectorXlsx, no con la librería de hojas de cálculo
PhpSpreadsheet está instalado, pero sólo como dependencia de `maatwebsite/excel` y sólo se usa para **escribir** (`app/Exports/**`). Para **leer** va siempre `App\Services\Excel\LectorXlsx`, que abre el paquete OOXML directamente (ZipArchive + XMLReader sobre `zip://`) y entrega las filas con un generador: es lo que permite recorrer el padrón de 26 mil colegios sin cargarlo en memoria, cosa que PhpSpreadsheet no hace.

Ojo al leer: `LectorXlsx` recorta las celdas vacías del final de cada fila, así que una fila corta no significa que falten columnas.

Para las pruebas, `Tests\Support\ConstructorXlsx` arma un .xlsx mínimo en disco: los padrones reales traen datos personales de postulantes y no pueden comitearse como fixture.
