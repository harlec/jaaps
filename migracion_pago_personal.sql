-- Agrega el concepto de gasto "Pago personal".
--   mysql -u root -p jaap_db < migracion_pago_personal.sql
INSERT IGNORE INTO `gasto_conceptos` (`nombre`, `descripcion`) VALUES
('Pago personal', 'Pago de sueldos o retribuciones al personal');
