---
paths:
  - '{app/Services/Excel/**,tests/Support/**}'
---

# Support

## Los .xlsx se leen con LectorXlsx, sin librería de hojas de cálculo
No hay PhpSpreadsheet ni equivalente entre las dependencias y no se debe agregar sin aprobación: `App\Services\Excel\LectorXlsx` lee el paquete OOXML directamente (ZipArchive + XMLReader sobre `zip://`) y entrega las filas con un generador, que es lo que permite recorrer el padrón de 26 mil colegios sin cargarlo en memoria. Para las pruebas, `Tests\Support\ConstructorXlsx` arma un .xlsx mínimo en disco: los padrones reales traen datos personales de postulantes y no pueden comitearse como fixture.
