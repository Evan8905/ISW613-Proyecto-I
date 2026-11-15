USE db_sigel;

INSERT INTO roles (nombre) 
VALUES     
    ('Administrador'),
    ('Operador');

INSERT INTO usuarios (rol_id, nombre, correo, password_hash, estado, intentos_fallidos) 
VALUES     
    (1,'Evan','evan@correoadmin.com','$2y$10$bF59mlTn2qqiWwnzHGXjTeRi.jketGcwJvSEizZq5EgCmPkZe/opG',1,0),
    (2,'Efren','efren@correooperador.com','$2y$10$bF59mlTn2qqiWwnzHGXjTeRi.jketGcwJvSEizZq5EgCmPkZe/opG',1,0);


INSERT INTO permisos (clave) 
VALUES     
    ('usuarios.gestionar'),
    ('categorias.gestionar'),
    ('equipos.gestionar'),
    ('equipos.consultar'),
    ('prestamos.gestionar'),
    ('reportes.consultar');


INSERT INTO rol_permiso (rol_id, permiso_id) 
VALUES     
    (1,1),
    (1,2),
    (1,3),
    (1,4),
    (1,5),
    (1,6),
    (2,4),
    (2,5),
    (2,6);