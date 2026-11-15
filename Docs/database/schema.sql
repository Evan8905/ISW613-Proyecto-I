CREATE DATABASE IF NOT EXISTS db_sigel;

USE db_sigel;

CREATE TABLE roles (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE 
);

CREATE TABLE usuarios (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1, 
    intentos_fallidos INT NOT NULL DEFAULT 0,

    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE categorias (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    dias_max_prestamo INT NOT NULL,
    eliminado_en DATETIME NULL
);

CREATE TABLE equipos (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    categoria_id INT NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    estado VARCHAR(30) NOT NULL,
    version INT NOT NULL DEFAULT 1,
    eliminado_en DATETIME NULL,

    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

CREATE TABLE equipo_imagenes (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    equipo_id INT NOT NULL,
    ruta VARCHAR(255) NOT NULL,

    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
);

CREATE TABLE prestamos (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    operador_id INT NOT NULL,
    estado VARCHAR(30) NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id)
);

CREATE TABLE prestamo_detalle (

    prestamo_id INT NOT NULL,
    equipo_id INT NOT NULL,
    fecha_devolucion DATETIME NULL,

    PRIMARY KEY (prestamo_id, equipo_id),
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
);

CREATE TABLE auth_tokens (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    selector VARCHAR(64) NOT NULL UNIQUE,
    validador_hash VARCHAR(255) NOT NULL,
    expira_en DATETIME NOT NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE bitacora (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR (100) NOT NULL,
    entidad_id INT NULL,
    valores_antes JSON NULL,
    valores_despues JSON NULL,
    creado_en DATETIME NOT NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE permisos (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    clave VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE rol_permiso (
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,

    PRIMARY KEY(rol_id, permiso_id),
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    FOREIGN KEY (permiso_id) REFERENCES permisos(id)
);