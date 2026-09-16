# Deploy en Railway

El proyecto está preparado para Railway mediante `Dockerfile`.

## Servicios necesarios

1. Un servicio de aplicación conectado a este repositorio GitHub.
2. Un servicio MySQL dentro del mismo proyecto Railway.
3. Recomendado: un Volume montado en `/data` para conservar recetas y sesiones entre deploys.

## Variables del servicio de aplicación

Si el servicio MySQL se llama `MySQL`, agregá estas variables como referencias:

```text
MYSQLHOST=${{MySQL.MYSQLHOST}}
MYSQLPORT=${{MySQL.MYSQLPORT}}
MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
MYSQLUSER=${{MySQL.MYSQLUSER}}
MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}

MP_ACCESS_TOKEN=TU_ACCESS_TOKEN_DE_MERCADO_PAGO
MP_PUBLIC_KEY=TU_PUBLIC_KEY_DE_MERCADO_PAGO

PRESCRIPTION_UPLOAD_DIR=/data/recetas
SESSION_SAVE_PATH=/data/sessions
```

No hace falta definir `BASE_URL` en Railway: la aplicación usa automáticamente `RAILWAY_PUBLIC_DOMAIN`.

## Deploy

- Railway detecta el `Dockerfile` automáticamente.
- Al iniciar, `scripts/init_db.php` crea/actualiza las tablas y carga los productos iniciales.
- La aplicación escucha el `$PORT` asignado por Railway.
- Generá un dominio público desde **Settings > Networking > Generate Domain**.

## Volume

Para persistir las recetas:

1. Agregá un Volume al servicio de la aplicación.
2. Mount path: `/data`.
3. Definí `PRESCRIPTION_UPLOAD_DIR=/data/recetas` y `SESSION_SAVE_PATH=/data/sessions`.

## Admin

Ruta: `/admin/index.php`

- Usuario: `admin`
- Contraseña: `admin123`

Cambiar estas credenciales antes de usar el proyecto fuera de una demostración escolar.
