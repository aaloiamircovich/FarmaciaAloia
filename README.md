# FarmaciaAloia

# Farmacia Salud+ — Farmacia y Perfumería (Equipo 1)

Adaptación escolar del proyecto **Kiosco Online con Mercado Pago** a una **Farmacia y Perfumería**.

## Desafío técnico implementado

Los productos con `requiere_receta = 1`:

1. muestran una etiqueta **Rx / Requiere receta**;
2. al intentar agregarlos, abren un modal para subir una imagen;
3. solo aceptan **JPG, PNG o WEBP** de hasta **5 MB**;
4. la receta se guarda en `uploads/recetas/` con nombre aleatorio;
5. el checkout queda bloqueado si el carrito contiene un medicamento bajo receta y no existe una receta válida;
6. `api/create_preference.php` vuelve a verificar la receta en el servidor antes de generar la preferencia de Mercado Pago;
7. el administrador puede ver la receta asociada a la orden desde una ruta protegida.

## Tecnologías

- PHP 8.x
- MySQL / MySQLi
- HTML + CSS + JavaScript Vanilla
- Composer + `vlucas/phpdotenv`
- Mercado Pago Checkout Pro vía API REST

## Estructura

```text
Antigravity/
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   └── view_prescription.php
├── api/
│   ├── admin/
│   │   ├── delete_product.php
│   │   ├── get_orders.php
│   │   ├── login.php
│   │   └── save_product.php
│   ├── create_preference.php
│   ├── get_products.php
│   ├── upload_prescription.php
│   └── webhook.php
├── config/
│   ├── auth.php
│   ├── database.php
│   ├── payment_sync.php
│   └── session.php
├── db/
│   └── schema.sql
├── public/
│   ├── css/styles.css
│   ├── img/*.svg
│   ├── js/api.js
│   ├── js/app.js
│   ├── js/cart.js
│   ├── success.php
│   ├── pending.php
│   └── failure.php
├── uploads/recetas/
├── .env.example
├── composer.json
├── env.php
└── index.php
```

## Inicio rápido

1. Copiar el proyecto a `C:\xampp\htdocs\2026\Antigravity`.
2. Iniciar Apache y MySQL en XAMPP.
3. Abrir CMD/PowerShell dentro de la carpeta y ejecutar `composer install`.
4. Ejecutar `copy .env.example .env` y completar las credenciales de Mercado Pago en `.env`.
5. Importar `db/schema.sql` desde phpMyAdmin.
6. Abrir `http://localhost/2026/Antigravity/index.php`.
7. Panel admin: `http://localhost/2026/Antigravity/admin/index.php`.
   - Usuario: `admin`
   - Contraseña: `admin123`

> Este proyecto es demostrativo y educativo. Los productos y precios iniciales son datos de ejemplo.

## Deploy en Railway

El repositorio incluye un `Dockerfile` preparado para Railway y un inicializador idempotente de MySQL (`scripts/init_db.php`). Consultá `RAILWAY.md` para los pasos y variables exactas.
