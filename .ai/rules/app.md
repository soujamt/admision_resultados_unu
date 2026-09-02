---
paths:
  - '{app/**,tests/**}'
---

# App

## sortBy con arreglo trata las closures como comparadores
`$coleccion->sortBy([fn (...) => $valor, fn (...) => $otro])` no ordena por esos valores: `sortByMany` llama a cada closure como comparador (`$prop($a, $b)`) y usa lo que devuelve como resultado de la comparacion, asi que una closure de un solo argumento devuelve siempre lo mismo y la coleccion queda en su orden original, sin error alguno.

Para ordenar por varias claves con logica propia, arma una sola clave concatenada y pasala en una unica closure (`sortBy(fn ($x) => $aula.str_pad($asiento, 4, '0', STR_PAD_LEFT).$nombre)`), rellenando los numeros con ceros para que ordenen como numeros. El arreglo de comparaciones solo sirve con nombres de columna: `sortBy([['nombre', 'asc'], ['edad', 'desc']])`.
