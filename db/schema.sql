-- ============================================================
-- FARMACIA Y PERFUMERÍA - Equipo 1
-- Adaptación del Kiosco Online con Mercado Pago
-- Base de datos: farmacia_online
-- ============================================================

CREATE DATABASE IF NOT EXISTS `farmacia_online`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `farmacia_online`;

-- ------------------------------------------------------------
-- Usuarios administradores
-- Usuario inicial: admin
-- Contraseña inicial: admin123
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Productos
-- requiere_receta = 1 indica medicamento bajo receta
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `categoria` VARCHAR(50) NOT NULL,
  `imagen_url` VARCHAR(500) NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `destacado` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_receta` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_productos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Órdenes
-- receta_archivo guarda la ruta privada de la imagen cargada
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `external_reference` VARCHAR(64) NOT NULL UNIQUE,
  `monto_total` DECIMAL(10,2) NOT NULL,
  `estado` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `mp_payment_id` VARCHAR(100) NULL,
  `mp_merchant_order_id` VARCHAR(100) NULL,
  `receta_archivo` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Ítems de cada orden
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orden_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  CONSTRAINT `fk_orden_items_orden`
    FOREIGN KEY (`orden_id`) REFERENCES `ordenes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orden_items_producto`
    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Usuario administrador inicial
-- Hash válido para: admin123
-- ------------------------------------------------------------
INSERT INTO `usuarios` (`username`, `password_hash`, `nombre`) VALUES
('admin', '$2y$12$7VHSR7BmNzCtYCrNiNGsluC1h9mf7aN2ZevbXdf/YJQBnu0ELQgyW', 'Administrador Farmacia')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- ------------------------------------------------------------
-- Productos de ejemplo
-- IMPORTANTE: son datos ficticios para un trabajo escolar.
-- ------------------------------------------------------------
INSERT INTO `productos`
(`nombre`, `descripcion`, `precio`, `categoria`, `imagen_url`, `stock`, `destacado`, `requiere_receta`) VALUES
('Paracetamol 500 mg x 20', 'Analgésico de venta libre. Producto de demostración para el catálogo.', 4200.00, 'medicamentos', 'public/img/medicamento.svg', 40, 1, 0),
('Ibuprofeno 400 mg x 20', 'Analgésico y antiinflamatorio de venta libre. Producto de demostración.', 5100.00, 'medicamentos', 'public/img/medicamento.svg', 35, 0, 0),
('Amoxicilina 500 mg x 21', 'Medicamento de demostración marcado como venta bajo receta médica.', 8900.00, 'medicamentos', 'public/img/receta.svg', 18, 1, 1),
('Cefalexina 500 mg x 16', 'Medicamento de demostración marcado como venta bajo receta médica.', 9700.00, 'medicamentos', 'public/img/receta.svg', 15, 0, 1),
('Protector Solar FPS 50 200 ml', 'Protección solar de amplio espectro para uso diario.', 14800.00, 'cuidado_personal', 'public/img/cuidado.svg', 22, 1, 0),
('Crema Hidratante Corporal 400 ml', 'Crema hidratante para piel normal a seca.', 9900.00, 'cuidado_personal', 'public/img/cuidado.svg', 28, 0, 0),
('Eau de Parfum Floral 50 ml', 'Fragancia floral suave de uso diario.', 26500.00, 'perfumeria', 'public/img/perfume.svg', 14, 1, 0),
('Body Splash Cítrico 200 ml', 'Fragancia corporal fresca con notas cítricas.', 12500.00, 'perfumeria', 'public/img/perfume.svg', 19, 0, 0),
('Shampoo Neutro 400 ml', 'Shampoo de limpieza suave para uso frecuente.', 7800.00, 'higiene', 'public/img/higiene.svg', 32, 0, 0),
('Jabón Líquido 250 ml', 'Jabón líquido para higiene diaria.', 4600.00, 'higiene', 'public/img/higiene.svg', 45, 0, 0),
('Pañales Talle M x 30', 'Pañales descartables para bebé, presentación de 30 unidades.', 18200.00, 'bebe', 'public/img/bebe.svg', 20, 1, 0),
('Toallitas Húmedas x 50', 'Toallitas húmedas suaves para higiene del bebé.', 5400.00, 'bebe', 'public/img/bebe.svg', 30, 0, 0)
ON DUPLICATE KEY UPDATE
  `descripcion` = VALUES(`descripcion`),
  `precio` = VALUES(`precio`),
  `categoria` = VALUES(`categoria`),
  `imagen_url` = VALUES(`imagen_url`),
  `stock` = VALUES(`stock`),
  `destacado` = VALUES(`destacado`),
  `requiere_receta` = VALUES(`requiere_receta`);
