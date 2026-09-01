---
paths:
  - '{app/Services/Admision/AlmacenFotos.php,app/Models/Inscripcion.php,app/Models/Proceso.php}'
---

# Models

## Las fotos de postulantes van al disco privado, separadas por proceso
Las fotos son datos personales (buena parte, de menores) y la Ley 29733 que cita el reglamento no admite exponerlas por URL directa. Se guardan en el disco `local` (storage/app/private) bajo `procesos/{codigo_pro}/fotos/{numero_documento}.{ext}` y `foto_ins` almacena esa ruta relativa. Nunca las muevas a `public`: sírvelas por una ruta autenticada usando `AlmacenFotos::contenido()`. La carpeta por proceso es lo que evita que la foto de 2027-I pise la de 2027-II del mismo postulante.
