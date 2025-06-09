# Configuración de Laravel con Sail en WSL

Esta guía te explica cómo instalar y configurar un proyecto Laravel usando Laravel Sail en Windows Subsystem for Linux (WSL), incluyendo MySQL y phpMyAdmin para la gestión de bases de datos. ¡Sigue estos pasos para poner en marcha tu proyecto `controlParental`!

## Requisitos previos
- **WSL**: Asegúrate de tener WSL (por ejemplo, Ubuntu) instalado en tu sistema Windows.
- **Docker**: Instala Docker Desktop y configúralo para que funcione con WSL.
- **Composer**: Instala Composer en tu entorno WSL.
- Una terminal en WSL (por ejemplo, Ubuntu) para ejecutar los comandos.

## Pasos para la configuración

1. **Verificar la instalación de Docker**
   Comprueba si Docker está instalado y en ejecución:
   ```bash
   docker --version
   ```
    - Deberías ver una versión como `Docker version 20.10.0` o similar. Si no, instala Docker Desktop y habilita la integración con WSL.

2. **Crear un nuevo proyecto Laravel**
   Usa Composer para crear un proyecto Laravel llamado `controlParental`:
   ```bash
   composer create-project laravel/laravel controlParental
   ```

3. **Navegar al directorio del proyecto**
   Entra en la carpeta de tu proyecto:
   ```bash
   cd controlParental/
   ```

4. **Instalar Laravel Sail**
   Añade Laravel Sail como una dependencia de desarrollo:
   ```bash
   composer require laravel/sail --dev
   ```

5. **Configurar Sail**
   Ejecuta el comando Artisan para instalar Sail y selecciona los servicios (por ejemplo, MySQL):
   ```bash
   php artisan sail:install
   ```
    - Cuando se te pida, elige `mysql` y otros servicios que necesites (por ejemplo, redis, mailpit).

6. **Modificar el archivo .env**
   Actualiza el archivo `.env` para configurar el nombre de la base de datos y las credenciales. Abre `.env` en un editor de texto (por ejemplo, `nano .env`) y ajusta estas líneas:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=controlparental
   DB_USERNAME=sail
   DB_PASSWORD=password
   ```
    - **Nota**: Reemplaza `controlparental` con el nombre deseado para tu base de datos y actualiza `DB_PASSWORD` para mayor seguridad en producción.

7. **Añadir phpMyAdmin a Docker Compose**
   Edita el archivo `docker-compose.yml` en la raíz del proyecto para incluir phpMyAdmin. Agrega lo siguiente bajo la sección `services`:
   ```yaml
   phpmyadmin:
       image: phpmyadmin/phpmyadmin
       restart: unless-stopped
       ports:
           - '8888:80'
       environment:
           PMA_HOST: mysql
           PMA_USER: ${DB_USERNAME}
           PMA_PASSWORD: ${DB_PASSWORD}
       depends_on:
           - mysql
       networks:
           - sail
   ```
    - Esto configura phpMyAdmin para ejecutarse en el puerto `8888` y conectarse al servicio MySQL.

8. **Iniciar los contenedores de Sail**
   Lanza los contenedores de Docker en modo detached (en segundo plano):
   ```bash
   ./vendor/bin/sail up -d
   ```
    - Esto inicia MySQL, phpMyAdmin y otros servicios seleccionados en segundo plano.

9. **Acceder al contenedor de MySQL**
   Entra en el contenedor de MySQL para configurar la base de datos:
   ```bash
   docker exec -it controlparental-mysql-1 bash
   ```
    - **Nota**: Reemplaza `controlparental-mysql-1` con el nombre o ID real del contenedor. Encuéntralo con:
      ```bash
      docker ps
      ```

10. **Configurar la base de datos**
    Dentro del contenedor, inicia sesión en MySQL:
    ```bash
    mysql -u root -p
    ```
    - Ingresa la contraseña (por defecto es `password` a menos que la hayas cambiado en `.env`).
      Crea la base de datos y otorga privilegios:
    ```sql
    CREATE DATABASE controlparental;
    GRANT ALL PRIVILEGES ON controlparental.* TO 'sail'@'%';
    FLUSH PRIVILEGES;
    ```
    - Sal de MySQL con `exit` y del contenedor con `exit`.

11. **Reiniciar los contenedores**
    Detén y reinicia los contenedores para aplicar los cambios:
    ```bash
    ./vendor/bin/sail down
    ./vendor/bin/sail up -d
    ```

12. **Ejecutar las migraciones**
    Ejecuta las migraciones de Laravel para crear las tablas en la base de datos `controlparental`:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## Acceder a tu aplicación
- **Aplicación Laravel**: Visita `http://localhost` en tu navegador.
- **phpMyAdmin**: Accede a `http://localhost:8888` para gestionar tu base de datos.
    - Inicia sesión con `DB_USERNAME` (por ejemplo, `sail`) y `DB_PASSWORD` (por ejemplo, `password`) desde tu archivo `.env`.

## Notas
- **Seguridad**: Actualiza `DB_PASSWORD` en `.env` a un valor fuerte y único para producción.
- **Conflictos de puertos**: Si el puerto `8888` o `80` está en uso, cámbialos en `docker-compose.yml` (por ejemplo, `8889:80`).
- **Solución de problemas**: Revisa los logs de los contenedores con `./vendor/bin/sail logs` si surgen problemas.

¡Ahora tu proyecto Laravel con Sail está listo en WSL, con MySQL y phpMyAdmin configurados!
