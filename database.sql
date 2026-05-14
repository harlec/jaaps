-- ============================================================
-- SISTEMA JUNTA ADMINISTRADORA DE AGUA POTABLE (JAAP)
-- Base de datos MySQL
-- Versión: 1.0
-- Fecha: 2026-05-14
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:00"; -- Hora Perú

CREATE DATABASE IF NOT EXISTS `jaap_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `jaap_db`;

-- ============================================================
-- Tabla: usuarios_sistema
-- Usuarios administradores del sistema (no son abonados)
-- ============================================================
CREATE TABLE `usuarios_sistema` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nombre`     VARCHAR(100)    NOT NULL,
  `email`      VARCHAR(100)    NOT NULL,
  `password`   VARCHAR(255)    NOT NULL,
  `rol`        ENUM('admin','cajero','viewer') NOT NULL DEFAULT 'cajero',
  `activo`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: abonados
-- Usuarios / suscriptores del servicio de agua
-- ============================================================
CREATE TABLE `abonados` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `codigo`            VARCHAR(20)     NOT NULL COMMENT 'Código interno del abonado',
  `dni`               VARCHAR(8)      NOT NULL,
  `nombres`           VARCHAR(100)    NOT NULL,
  `apellidos`         VARCHAR(100)    NOT NULL,
  `fecha_nacimiento`  DATE            NULL,
  `departamento`      VARCHAR(100)    NOT NULL DEFAULT '',
  `provincia`         VARCHAR(100)    NOT NULL DEFAULT '',
  `distrito`          VARCHAR(100)    NOT NULL DEFAULT '',
  `direccion`         TEXT            NULL,
  `zona`              ENUM('porvenir','tunas','cerro_de_pasco') NOT NULL DEFAULT 'porvenir',
  `profesion`         VARCHAR(100)    NULL,
  `actividad`         VARCHAR(100)    NULL,
  `grado_instruccion` ENUM(
    'sin_instruccion','primaria_incompleta','primaria_completa',
    'secundaria_incompleta','secundaria_completa',
    'tecnico','universitario','posgrado'
  ) NOT NULL DEFAULT 'sin_instruccion',
  `estado_civil`      ENUM('soltero','casado','conviviente','viudo','divorciado') NOT NULL DEFAULT 'soltero',
  `telefono`          VARCHAR(15)     NULL,
  `email`             VARCHAR(100)    NULL,
  `numero_hijos`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `fecha_inscripcion` DATE            NULL,
  `estado`            ENUM('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo',
  `observaciones`     TEXT            NULL,
  `creado_por`        INT UNSIGNED    NULL,
  `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dni`    (`dni`),
  UNIQUE KEY `uk_codigo` (`codigo`),
  KEY `idx_zona`   (`zona`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_abonado_creado` FOREIGN KEY (`creado_por`) REFERENCES `usuarios_sistema` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: hijos
-- Datos de hijos de cada abonado
-- ============================================================
CREATE TABLE `hijos` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `abonado_id`       INT UNSIGNED NOT NULL,
  `nombres`          VARCHAR(100) NOT NULL,
  `fecha_nacimiento` DATE         NULL,
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hijos_abonado` (`abonado_id`),
  CONSTRAINT `fk_hijo_abonado` FOREIGN KEY (`abonado_id`) REFERENCES `abonados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: conceptos
-- Tipos de cobro: tarifa mensual, inscripción, otros
-- ============================================================
CREATE TABLE `conceptos` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100)    NOT NULL,
  `descripcion` TEXT            NULL,
  `monto`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `tipo`        ENUM('tarifa_mensual','inscripcion','multa','reconexion','otro') NOT NULL DEFAULT 'otro',
  `activo`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: periodos_cobro
-- Períodos de facturación (semestral o anual)
-- ============================================================
CREATE TABLE `periodos_cobro` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`              VARCHAR(100) NOT NULL,
  `anio`                YEAR         NOT NULL,
  `semestre`            ENUM('1','2','anual') NOT NULL DEFAULT '1',
  `meses`               TINYINT UNSIGNED NOT NULL DEFAULT 6 COMMENT 'Cantidad de meses incluidos',
  `fecha_inicio`        DATE         NOT NULL,
  `fecha_fin`           DATE         NOT NULL,
  `fecha_vencimiento`   DATE         NOT NULL,
  `monto_total`         DECIMAL(10,2) NOT NULL COMMENT 'Monto base del período',
  `estado`              ENUM('pendiente','activo','cerrado') NOT NULL DEFAULT 'pendiente',
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_periodo` (`anio`, `semestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: pagos
-- Historial de pagos de los abonados
-- ============================================================
CREATE TABLE `pagos` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `abonado_id`    INT UNSIGNED    NOT NULL,
  `concepto_id`   INT UNSIGNED    NOT NULL,
  `periodo_id`    INT UNSIGNED    NULL,
  `numero_recibo` VARCHAR(20)     NULL COMMENT 'Número de recibo generado',
  `monto`         DECIMAL(10,2)   NOT NULL,
  `descuento`     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `interes`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `monto_total`   DECIMAL(10,2)   NOT NULL COMMENT 'monto - descuento + interes',
  `fecha_pago`    DATE            NOT NULL,
  `metodo_pago`   ENUM('efectivo','transferencia','deposito','otro') NOT NULL DEFAULT 'efectivo',
  `referencia`    VARCHAR(100)    NULL COMMENT 'Nro operación / voucher',
  `observacion`   TEXT            NULL,
  `registrado_por` INT UNSIGNED   NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_recibo` (`numero_recibo`),
  KEY `idx_abonado_pago`  (`abonado_id`),
  KEY `idx_fecha_pago`    (`fecha_pago`),
  KEY `idx_periodo_pago`  (`periodo_id`),
  CONSTRAINT `fk_pago_abonado`  FOREIGN KEY (`abonado_id`)  REFERENCES `abonados`        (`id`),
  CONSTRAINT `fk_pago_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `conceptos`       (`id`),
  CONSTRAINT `fk_pago_periodo`  FOREIGN KEY (`periodo_id`)  REFERENCES `periodos_cobro`  (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pago_usuario`  FOREIGN KEY (`registrado_por`) REFERENCES `usuarios_sistema` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: inscripciones
-- Registro formal de ingreso de un abonado al servicio
-- ============================================================
CREATE TABLE `inscripciones` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `abonado_id`        INT UNSIGNED  NOT NULL,
  `numero_solicitud`  VARCHAR(20)   NULL,
  `fecha_inscripcion` DATE          NOT NULL,
  `monto`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado`            ENUM('pendiente','aprobada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `observacion`       TEXT          NULL,
  `registrado_por`    INT UNSIGNED  NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inscripcion_abonado` (`abonado_id`),
  CONSTRAINT `fk_inscripcion_abonado` FOREIGN KEY (`abonado_id`) REFERENCES `abonados` (`id`),
  CONSTRAINT `fk_inscripcion_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios_sistema` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Usuario administrador por defecto
-- Password: Admin2026! (bcrypt)
INSERT INTO `usuarios_sistema` (`nombre`, `email`, `password`, `rol`) VALUES
('Administrador', 'admin@jaap.pe', '$2y$12$0NfB3AkuiFIIQ3O8V0G4H.p5a1RrXL1nKhtL5gKPdDXkjlnwVmzX2', 'admin');

-- Conceptos base
INSERT INTO `conceptos` (`nombre`, `descripcion`, `monto`, `tipo`) VALUES
('Tarifa Mensual de Agua', 'Cuota mensual por servicio de agua potable', 12.00, 'tarifa_mensual'),
('Inscripción / Conexión',  'Pago único de inscripción al padrón de abonados', 0.00, 'inscripcion'),
('Multa por Mora',          'Cargo por pago fuera de fecha de vencimiento', 0.00, 'multa'),
('Reconexión de Servicio',  'Costo por reconexión tras suspensión', 0.00, 'reconexion');

-- Período semestral 1 – 2026
INSERT INTO `periodos_cobro`
  (`nombre`, `anio`, `semestre`, `meses`, `fecha_inicio`, `fecha_fin`, `fecha_vencimiento`, `monto_total`, `estado`)
VALUES
('Semestre 1 – 2026', 2026, '1', 6, '2026-01-01', '2026-06-30', '2026-03-31', 72.00, 'activo'),
('Semestre 2 – 2026', 2026, '2', 6, '2026-07-01', '2026-12-31', '2026-09-30', 72.00, 'pendiente');
