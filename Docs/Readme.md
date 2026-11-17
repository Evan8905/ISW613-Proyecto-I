Proyecto 1 ISW-613 
Aqui se va a Documentar requisitos de instalación, existe una tarea a nivel de jira para eso

La contraseña hasheada inicial se generó usando linea de comandos para los primeros usuarios

La contraseña es pw1234

# SIGEL — Sistema de Gestión de Préstamos de Equipos de Laboratorio

## Descripción

SIGEL es una aplicación web desarrollada como parte del Proyecto 1 del curso ISW-613 de la Universidad Técnica Nacional.

El sistema permite administrar usuarios, categorías, equipos y préstamos de equipos de laboratorio, utilizando PHP y MySQL.

## Tecnologías utilizadas

- PHP 8.x
- MySQL / MariaDB
- Apache (XAMPP)
- Bootstrap 5
- HTML y CSS
- PDO para la conexión a la base de datos

## Funcionalidades implementadas

- Inicio y cierre de sesión.
- Control de acceso mediante roles de Administrador y Operador.
- Registro, consulta, edición y eliminación lógica de usuarios.
- Registro, consulta, edición y eliminación lógica de categorías.
- Registro, consulta, edición y eliminación lógica de equipos.
- Asignación de categorías a equipos.
- Registro de solicitudes de préstamo con múltiples equipos.
- Validación de fechas, anticipación mínima y traslapes.
- Aprobación, entrega, cancelación y devolución de préstamos.
- Validaciones de seguridad mediante sesiones, CSRF y consultas preparadas.

**Pendiente:** gestión de imágenes, paginación y otras funcionalidades aún no finalizadas.

## Instalación y configuración

1. Instalar XAMPP con Apache, PHP y MySQL.
2. Clonar el repositorio en una carpeta local.
3. Crear la base de datos ejecutando `Docs/database/schema.sql`.
4. Ejecutar `Docs/database/seed.sql` para cargar los datos iniciales.
5. Configurar las credenciales de MySQL en `config/config.local.php`, según las variables utilizadas por `config/database.php`.
6. Configurar un VirtualHost en Apache para que el dominio `sigel.test` apunte a la carpeta `public/` del proyecto.
7. Agregar la siguiente entrada al archivo `hosts` de Windows:

   `127.0.0.1 sigel.test`

8. Reiniciar Apache y acceder a:

   `http://sigel.test`

### Ejemplo de VirtualHost

```apache
<VirtualHost *:80>
    ServerName sigel.test
    DocumentRoot "C:/ruta/del/proyecto/public"

    <Directory "C:/ruta/del/proyecto/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Se debe reemplazar la ruta del ejemplo por la ubicación real del proyecto.

## Credenciales de prueba

Las cuentas de prueba se encuentran en el archivo `seed.sql`.

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | Completar con el correo del seed | Completar |
| Operador | Completar con el correo del seed | Completar |

## Estructura del proyecto

```text
config/          Configuración y conexión a MySQL
includes/        Sesiones, autenticación y funciones reutilizables
public/          Páginas PHP y recursos públicos
public/actions/  Procesamiento de formularios
Docs/database/   Scripts de base de datos
```

## Seguridad

He implementado consultas preparadas mediante PDO, almacenamiento seguro de contraseñas, validación de sesiones, protección CSRF, escape de contenido HTML y controles de acceso según el rol del usuario.

## Estado del proyecto

El proyecto se encuentra en desarrollo. Las funcionalidades descritas corresponden a los módulos implementados hasta el momento.