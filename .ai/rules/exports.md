---
paths:
  - 'app/Exports/**'
---

# Exports

## El Excel de resultados ordena en PHP, no en SQL
`ResultadosExport` usa `FromCollection` y no `FromQuery` con lotes, a propósito: el orden alfabético tiene que salir igual en cualquier motor y ordenar por SQL lo deja a merced de la colación. SQLite compara bytes y manda «ÁLVAREZ» detrás de «ZÚÑIGA»; MySQL con una colación acentuada no. Se ordena en PHP con `Str::ascii`, como el resto de los listados de apellidos del proyecto.

El precio es tener la jornada en memoria. Se acepta porque son cientos o pocos miles de filas; si algún export tuviera que recorrer el padrón de colegios (26 mil), ahí sí toca `FromQuery` con `WithChunkReading` y asumir la colación del motor.

Las columnas que el sistema no captura salen vacías en vez de omitirse, para que el archivo conserve la forma de la plantilla de la Dirección: el ubigeo del colegio (el padrón del MINEDU no lo trae) y los datos de pago, preparación y estudios previos, que la ficha de inscripción no pide.
