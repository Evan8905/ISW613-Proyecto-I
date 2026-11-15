# Configuración del entorno de desarrollo local — SIGEL

## 1. Descripción

**SIGEL** es un proyecto académico desarrollado para el curso ISW-613 de la Universidad Técnica Nacional (UTN).

Esta guía describe los pasos necesarios para configurar el entorno de desarrollo local utilizando **XAMPP, Apache, PHP y MySQL** en Windows.

El proyecto utiliza un dominio virtual local (`sigel.test`) y se ejecuta directamente desde la carpeta donde se clona el repositorio, sin necesidad de copiarlo a `C:\xampp\htdocs`.

## 2. Requisitos previos

Antes de comenzar, asegúrese de contar con:

- Windows 10 u 11.
- Git instalado.
- XAMPP con Apache, PHP y MySQL.
- Acceso al repositorio del proyecto.
- Permisos de administrador para modificar archivos de configuración del sistema.

**Entorno de referencia:** PHP 8.3.36. Se recomienda utilizar una versión compatible con el proyecto.

## 3. Clonar el repositorio

Abra una terminal (CMD, PowerShell o Git Bash) y ubíquese en el directorio donde desea almacenar el proyecto.

Ejemplo:

```cmd
cd %USERPROFILE%\Documents
```

Clone el repositorio:

```bash
git clone <URL_DEL_REPOSITORIO>
```

Ingrese al directorio del proyecto:

```bash
cd ISW613-Proyecto-I
```

> Reemplace `<URL_DEL_REPOSITORIO>` por la URL SSH o HTTPS del repositorio. Si el repositorio es privado, necesitará permisos de acceso.

**Importante:** el proyecto puede almacenarse en cualquier ubicación del equipo. No es obligatorio utilizar `htdocs`.

## 4. Iniciar los servicios de XAMPP

1. Abra **XAMPP Control Panel**.
2. Inicie el servicio **Apache**.
3. Inicie el servicio **MySQL** cuando el proyecto requiera conectarse a la base de datos.
4. Verifique que los servicios se encuentren en ejecución.

Puertos predeterminados:

| Servicio | Puerto |
|---|---|
| Apache HTTP | 80 |
| Apache HTTPS | 443 |
| MySQL | 3306 |

Para esta configuración utilizaremos únicamente HTTP por el puerto 80.

## 5. Configurar el VirtualHost de Apache

El VirtualHost permite acceder al proyecto mediante un dominio local, en lugar de utilizar `localhost` y una ruta adicional.

### 5.1. Abrir el archivo de configuración

Ubique el archivo:

```text
C:\xampp\apache\conf\extra\httpd-vhosts.conf
```

Ábralo con un editor de texto que tenga permisos de administrador.

### 5.2. Agregar el VirtualHost

Agregue al final del archivo:

```apache
<VirtualHost *:80>
    ServerName sigel.test

    DocumentRoot "C:/RUTA/DEL/PROYECTO/ISW613-Proyecto-I"

    <Directory "C:/RUTA/DEL/PROYECTO/ISW613-Proyecto-I">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
</VirtualHost>
```

**Importante:** reemplace ambas apariciones de la ruta de ejemplo por la ubicación real del repositorio en su computadora.

Por ejemplo, si el repositorio se encuentra en:

```text
C:\Users\usuario\Documents\ISW613-Proyecto-I
```

La configuración debe ser:

```apache
<VirtualHost *:80>
    ServerName sigel.test

    DocumentRoot "C:/Users/usuario/Documents/ISW613-Proyecto-I"

    <Directory "C:/Users/usuario/Documents/ISW613-Proyecto-I">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
</VirtualHost>
```

Utilice barras `/` en las rutas de Apache.

## 6. Habilitar VirtualHosts en Apache

Abra:

```text
C:\xampp\apache\conf\httpd.conf
```

Busque la siguiente directiva:

```apache
Include conf/extra/httpd-vhosts.conf
```

Asegúrese de que esté habilitada.

Si aparece comentada:

```apache
#Include conf/extra/httpd-vhosts.conf
```

Elimine el símbolo `#` y guarde los cambios.

## 7. Registrar el dominio local en Windows

Windows necesita asociar el dominio `sigel.test` con la computadora local.

### 7.1. Abrir el archivo hosts

Ejecute CMD como administrador:

```cmd
notepad C:\Windows\System32\drivers\etc\hosts
```

### 7.2. Registrar el dominio

Agregue al final del archivo:

```text
127.0.0.1 sigel.test
```

Guarde los cambios.

Esta configuración indica que las solicitudes a `sigel.test` deben dirigirse al servidor local.

## 8. Validar la configuración de Apache

Abra CMD y ejecute:

```cmd
C:\xampp\apache\bin\httpd.exe -t
```

Si la configuración es correcta, aparecerá:

```text
Syntax OK
```

Después, desde XAMPP Control Panel:

1. Detenga Apache.
2. Inicie Apache nuevamente.
3. Compruebe que el servicio se encuentre en ejecución.

Si el dominio no se resuelve, ejecute:

```cmd
ipconfig /flushdns
```

## 9. Acceder al proyecto

Abra el navegador e ingrese:

**http://sigel.test**

Apache buscará el archivo `index.php` o `index.html` dentro de la carpeta configurada como `DocumentRoot`.

Para una prueba inicial, puede utilizar el siguiente contenido en `index.php`:

```php
<?php

echo "SIGEL funcionando correctamente";
echo "<br>PHP versión: " . phpversion();

?>
```

Resultado esperado:

```text
SIGEL funcionando correctamente
PHP versión: 8.3.36
```

La versión de PHP mostrada puede variar según la instalación.

## 10. Configuración de HTTPS

El proyecto utiliza el dominio reservado para pruebas `.test`, por lo que es posible trabajar mediante HTTP sin instalar certificados SSL.

URL de desarrollo:

```text
http://sigel.test
```

Es normal que el navegador muestre el indicador **Not secure / No es seguro**, debido a que HTTP no utiliza cifrado.

Esta configuración está destinada exclusivamente al desarrollo local.

No debe utilizarse para ambientes productivos ni para transmitir información sensible sin protección adicional.

## 11. Solución de problemas frecuentes

| Problema | Posible solución |
|---|---|
| `sigel.test` no abre | Verificar el archivo `hosts` y que Apache esté ejecutándose. |
| Apache no inicia | Verificar conflictos con el puerto 80 y revisar los logs. |
| Error `403 Forbidden` | Revisar la ruta y los permisos del directorio configurado. |
| Error `404 Not Found` | Verificar el `DocumentRoot` y la existencia del archivo solicitado. |
| Se muestra la página de XAMPP | Comprobar que el VirtualHost esté habilitado y que `ServerName` sea correcto. |
| Los cambios no aparecen | Reiniciar Apache y recargar el navegador. |
| Error de configuración de Apache | Ejecutar `httpd.exe -t` y revisar el mensaje. |

## 12. Consideraciones importantes

- Cada desarrollador debe configurar el VirtualHost con la ruta correspondiente a su equipo.
- No se deben incluir rutas personales de Windows en archivos de configuración compartidos del proyecto.
- No es necesario modificar ni copiar archivos dentro de `htdocs`.
- El dominio `sigel.test` funciona únicamente en las computadoras donde se configure.
- Los archivos `hosts` y `httpd-vhosts.conf` son configuraciones locales y no forman parte del repositorio.
- Apache debe estar ejecutándose para acceder al proyecto.
- MySQL debe estar ejecutándose cuando se utilicen funcionalidades que requieran base de datos.
- La configuración de conexión a la base de datos y sus credenciales debe documentarse por separado, sin publicar contraseñas reales.

---

**Proyecto:** SIGEL  
**Curso:** ISW-613  
**Universidad:** Universidad Técnica Nacional (UTN)  
**Entorno:** Windows + XAMPP + Apache + PHP + MySQL  
**Dominio local:** `http://sigel.test`