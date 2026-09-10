-- ============================================================
-- Migración: módulo de Gastos
-- Ejecutar en la base de datos existente (no borra nada):
--   mysql -u root -p jaap_db < migracion_gastos.sql
-- ============================================================

-- Tabla: gasto_conceptos
-- Categorías de gasto (luz, lavado de pozo, compra de materiales, etc.)
CREATE TABLE IF NOT EXISTS `gasto_conceptos` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100)    NOT NULL,
  `descripcion` VARCHAR(255)    NULL,
  `activo`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gasto_concepto_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: gastos
-- Registro de egresos de la JAAP
CREATE TABLE IF NOT EXISTS `gastos` (
  `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `concepto_id`        INT UNSIGNED    NOT NULL,
  `descripcion`        VARCHAR(255)    NULL,
  `monto`              DECIMAL(10,2)   NOT NULL,
  `fecha_gasto`        DATE            NOT NULL,
  `metodo_pago`        ENUM('efectivo','transferencia','deposito','otro') NOT NULL DEFAULT 'efectivo',
  `proveedor`          VARCHAR(150)    NULL,
  `numero_comprobante` VARCHAR(50)     NULL,
  `observacion`        TEXT            NULL,
  `registrado_por`     INT UNSIGNED    NULL,
  `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gasto_concepto` (`concepto_id`),
  KEY `idx_gasto_fecha`    (`fecha_gasto`),
  CONSTRAINT `fk_gasto_concepto` FOREIGN KEY (`concepto_id`)    REFERENCES `gasto_conceptos`   (`id`),
  CONSTRAINT `fk_gasto_usuario`  FOREIGN KEY (`registrado_por`) REFERENCES `usuarios_sistema`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conceptos de gasto de ejemplo (se omiten si ya existen por el nombre)
INSERT IGNORE INTO `gasto_conceptos` (`nombre`, `descripcion`) VALUES
('Pago de luz',            'Recibo de energía eléctrica del pozo / planta'),
('Lavado de pozo',         'Mantenimiento y limpieza del pozo'),
('Compra de materiales',   'Tuberías, válvulas, accesorios y otros materiales'),
('Mantenimiento de red',   'Reparaciones y mantenimiento de la red de distribución'),
('Otro',                   'Gastos varios no clasificados');

-- ============================================================
-- Módulo: Otros Ingresos
-- Ingresos que no provienen de la cuota de abonados (pagos):
-- alquiler de local, saldo de caja anterior, donaciones, etc.
-- ============================================================

-- Tabla: ingreso_conceptos
CREATE TABLE IF NOT EXISTS `ingreso_conceptos` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100)    NOT NULL,
  `descripcion` VARCHAR(255)    NULL,
  `activo`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ingreso_concepto_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: ingresos_otros
CREATE TABLE IF NOT EXISTS `ingresos_otros` (
  `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `concepto_id`        INT UNSIGNED    NOT NULL,
  `descripcion`        VARCHAR(255)    NULL,
  `monto`              DECIMAL(10,2)   NOT NULL,
  `fecha_ingreso`      DATE            NOT NULL,
  `metodo_pago`        ENUM('efectivo','transferencia','deposito','otro') NOT NULL DEFAULT 'efectivo',
  `origen`             VARCHAR(150)    NULL COMMENT 'Quién paga / de dónde proviene el ingreso',
  `numero_comprobante` VARCHAR(50)     NULL,
  `observacion`        TEXT            NULL,
  `registrado_por`     INT UNSIGNED    NULL,
  `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ingreso_concepto` (`concepto_id`),
  KEY `idx_ingreso_fecha`    (`fecha_ingreso`),
  CONSTRAINT `fk_ingreso_concepto` FOREIGN KEY (`concepto_id`)    REFERENCES `ingreso_conceptos` (`id`),
  CONSTRAINT `fk_ingreso_usuario`  FOREIGN KEY (`registrado_por`) REFERENCES `usuarios_sistema`   (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conceptos de ingreso de ejemplo (se omiten si ya existen por el nombre)
INSERT IGNORE INTO `ingreso_conceptos` (`nombre`, `descripcion`) VALUES
('Saldo de caja anterior', 'Saldo / caja que queda del período anterior'),
('Alquiler de local',      'Ingreso por alquiler del local comunal'),
('Donaciones',             'Donaciones recibidas'),
('Otros ingresos',         'Ingresos varios no clasificados');
