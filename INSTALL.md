# Guía de Instalación - EsteliPOS

## Requisitos Previos

- **PHP:** 8.5 o superior
- **Composer:** 2.x o superior
- **Node.js:** 18.x o superior
- **NPM:** 9.x o superior
- **Base de datos:** MySQL 8.0+ o SQLite
- **Servidor web:** Apache o Nginx
- **Extensiones PHP:** 
  - mbstring
  - xml
  - ctype
  - json
  - pdo
  - pdo_mysql (para MySQL)
  - pdo_sqlite (para SQLite)
  - zip
  - gd
  - curl

## Instalación desde Cero (Desarrollo)

### 1. Clonar el Repositorio

```bash
git clone <url-del-repositorio> agroservicio
cd agroservicio
```

### 2. Instalar Dependencias de PHP

```bash
composer install
```

### 3. Instalar Dependencias de Node.js

```bash
npm install
```

### 4. Configurar Variables de Entorno

```bash
cp .env.example .env
```

Edita el archivo `.env` con tu configuración:

```env
APP_NAME="EsteliPOS"
APP_ENV=local
APP_KEY=base64:tu-clave-aqui
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estelipos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 5. Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 6. Ejecutar Migraciones

```bash
php artisan migrate
```

### 7. Ejecutar Seeders (Datos Iniciales)

```bash
php artisan db:seed --class=ConfigurationSeeder
```

### 8. Compilar Assets

```bash
npm run build
```

### 9. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

La aplicación estará disponible en `http://localhost:8000`

## Instalación desde Paquete ZIP (Producción)

### 1. Descargar el Paquete

Descarga el archivo `EsteliPOS-YYYYMMDD-hash-windows.zip` desde la carpeta `releases/`.

### 2. Extraer el Paquete

```bash
unzip EsteliPOS-YYYYMMDD-hash-windows.zip -d /ruta/de/instalacion
cd /ruta/de/instalacion/EsteliPOS
```

### 3. Configurar Variables de Entorno

```bash
cp .env.example .env
```

Edita el archivo `.env` con tu configuración de producción:

```env
APP_NAME="EsteliPOS"
APP_ENV=production
APP_KEY=base64:tu-clave-aqui
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=tu-host-mysql
DB_PORT=3306
DB_DATABASE=estelipos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña_segura
```

### 4. Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 5. Ejecutar Migraciones

```bash
php artisan migrate --force
```

### 6. Ejecutar Seeders

```bash
php artisan db:seed --class=ConfigurationSeeder --force
```

### 7. Optimizar la Aplicación

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Configurar Permisos (Linux)

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 9. Configurar Servidor Web

**Apache (VirtualHost):**

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /ruta/de/instalacion/EsteliPOS/public

    <Directory /ruta/de/instalacion/EsteliPOS/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/estelipos_error.log
    CustomLog ${APACHE_LOG_DIR}/estelipos_access.log combined
</VirtualHost>
```

**Nginx:**

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /ruta/de/instalacion/EsteliPOS/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Actualización desde Versión Anterior

### Usando el Script de Actualización

```bash
./deployment/update.sh /ruta/a/EsteliPOS-YYYYMMDD-hash-windows.zip
```

El script automáticamente:
1. Crea un backup de tu instalación actual
2. Extrae el nuevo paquete
3. Actualiza los archivos
4. Ejecuta las migraciones de base de datos
5. Limpia el cache
6. Optimiza la aplicación

### Actualización Manual

1. **Crear Backup:**
   ```bash
   cp .env backups/.env.backup
   cp database/database.sqlite backups/  # Si usas SQLite
   ```

2. **Extraer el Paquete:**
   ```bash
   unzip EsteliPOS-YYYYMMDD-hash-windows.zip
   cp -r EsteliPOS/app/ app/
   cp -r EsteliPOS/database/migrations/ database/
   cp -r EsteliPOS/resources/views/ resources/
   cp -r EsteliPOS/routes/ routes/
   cp -r EsteliPOS/public/build/ public/build/
   ```

3. **Ejecutar Migraciones:**
   ```bash
   php artisan migrate --force
   ```

4. **Limpiar Cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan optimize
   ```

## Usuario Administrador por Defecto

**Credenciales iniciales:**
- Email: `admin@admin.com`
- Contraseña: `admin123`

⚠️ **Importante:** Cambia la contraseña del administrador inmediatamente después del primer inicio de sesión.

## Configuración Inicial

1. **Iniciar sesión** con el usuario administrador
2. **Ir a Configuración** (menú lateral)
3. **Configurar General:**
   - Nombre de la empresa
   - Logo
   - Dirección
   - RUC
   - IVA
   - Moneda
   - Zona horaria

4. **Configurar Numeraciones:**
   - Facturas
   - Compras
   - Cotizaciones
   - Recibos
   - Ajustes

5. **Configurar Apariencia (opcional):**
   - Tema (claro/oscuro)
   - Color principal
   - Nombre del sistema

6. **Activar Módulos:**
   - Selecciona los módulos que deseas usar
   - Configura el orden de aparición en el menú

## Solución de Problemas

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Solución:** Verifica que MySQL esté ejecutándose y que las credenciales en `.env` sean correctas.

### Error: "No application encryption key has been specified"

**Solución:** Ejecuta `php artisan key:generate`

### Error: "Permission denied" en storage/

**Solución:** 
```bash
chmod -R 755 storage bootstrap/cache
```

### Error: "Module not found" al ejecutar composer

**Solución:** Ejecuta `composer install --no-dev --optimize-autoloader`

### Error: "Vite build failed"

**Solución:** Ejecuta `npm install` y luego `npm run build`

### Error: "Class not found" en modelos

**Solución:** Ejecuta `composer dump-autoload`

### Error: "Route not found"

**Solución:** Ejecuta `php artisan route:clear` y `php artisan config:clear`

## Docker (Opcional)

Si prefieres usar Docker para el desarrollo:

```bash
# Construir contenedor
docker-compose up -d

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Ejecutar seeders
docker-compose exec app php artisan db:seed --class=ConfigurationSeeder


# Ver logs
docker-compose logs -f
```

## Soporte

Para reportar problemas o solicitar ayuda, contacta al equipo de desarrollo o abre un issue en el repositorio.

## Notas de Seguridad

- Nunca commits el archivo `.env` al repositorio
- Usa contraseñas fuertes para la base de datos
- Mantén PHP y las dependencias actualizadas
- En producción, establece `APP_DEBUG=false`
- Configura HTTPS en producción
- Usa un firewall para proteger el servidor
- Realiza backups regulares de la base de datos

## Licencia

Este software es propiedad de EsteliPOS. Todos los derechos reservados.
