---
paths:
  - '{app/Models/**,database/migrations/**,database/factories/**,database/seeders/**}'
---

# Seeders

## Nomenclatura de tablas y columnas: prefijo tbl_ y sufijo por tabla
Toda tabla se llama `tbl_<entidad>` en singular (`tbl_usuario`, `tbl_rol`). Cada columna lleva el sufijo abreviado de su tabla: `id_usu`, `nombre_usu`, `usuario_usu`, `estado_usu`; en `tbl_rol` es `_rol`. La clave primaria es `id_<sufijo>` y la foránea usa el mismo nombre que la PK de la tabla referenciada (`tbl_usuario.id_rol` → `tbl_rol.id_rol`), por lo que las relaciones de Eloquent deben declarar explícitamente ambas claves.

En los modelos hay que fijar `$table`, `$primaryKey`, y sobrescribir `getAuthPassword()` cuando la columna de contraseña no se llame `password` (Usuario usa `clave_usu`). Las tablas propias de Laravel (`sessions`, `cache`, `jobs`, `password_reset_tokens`) conservan su nombre original.

Los nombres del dominio van en español sin tildes en el código PHP (identificadores, comentarios de estructura); las tildes solo aparecen en el texto visible al usuario.
