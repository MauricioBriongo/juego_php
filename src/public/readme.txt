En la última revisión tuvimos problemas con xammp debido a una corrupción en la base de datos, por lo que hubo detalles que no pudimos mejorar:

Sabemos que las contraseñas se guardan encriptadas/hasheadas pero por falta de tiempo y experiencia no lo hemos hecho.

Usamos JWT de firebase, hicimos que el token mismo nos entregara nombre de usuario y id, porque era requerido en muchos endpoints.

Para el endpoint GET /cartas?atributo={atributo}&nombre={nombre} interpretamos que atributo era un String (ej. fuego)

