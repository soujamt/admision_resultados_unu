---
paths:
  - '{app/Services/Admision/SorteadorAulasService.php,app/Services/Admision/PadronPostulantesPdf.php,app/Models/AsignacionExamen.php,resources/views/pdf/padron-postulantes.blade.php}'
---

# Pdf

## El sorteo usa inscripciones, no el TXT del examen
La distribución y el sorteo de aulas ocurren antes de leer las fichas ópticas. Las asignaciones se vinculan a tbl_inscripcion mediante id_ins y deben incluir a los inscritos del proceso; tbl_examen_postulante se usa después para importar el padrón del escáner, respuestas y resultados.

## El padrón de postulantes es una sola lista alfabética, no una por aula
El formato que publica la Dirección de Admisión es un único listado de toda la jornada ordenado por apellidos y nombres, con el pabellón, el aula y la carpeta al costado de cada nombre. Se busca por apellido y de ahí se lee dónde sentarse, así que no se agrupa por aula ni se emite un PDF por cada una. Las columnas son N°, apellidos y nombres, pabellón, aula y carpeta: sin documento.

## Ordenar apellidos exige `Str::ascii`
Comparado byte a byte, «ÁLVAREZ» cae después de «BENITES» y «ÑAHUI» después de «ZÚÑIGA», porque en UTF-8 las vocales acentuadas y la eñe van por encima de la «Z». La clave vive en `AsignacionExamen::claveAlfabetica()`: normaliza con `Str::ascii(mb_strtoupper(...))` antes de comparar y desempata por documento, para que un padrón que se publica salga siempre en el mismo orden. La usan el padrón general y el del aula, y cualquier listado alfabético de postulantes que venga después tiene que usarla también, no rehacerla.
