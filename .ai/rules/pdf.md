---
paths:
  - '{app/Services/Admision/SorteadorAulasService.php,app/Models/AsignacionExamen.php,resources/views/pdf/padron-aula.blade.php}'
---

# Pdf

## El sorteo usa inscripciones, no el TXT del examen
La distribución y el sorteo de aulas ocurren antes de leer las fichas ópticas. Las asignaciones se vinculan a tbl_inscripcion mediante id_ins y deben incluir a los inscritos del proceso; tbl_examen_postulante se usa después para importar el padrón del escáner, respuestas y resultados.
