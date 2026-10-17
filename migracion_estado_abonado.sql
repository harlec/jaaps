-- Agrega los estados "cortado" y "suspension_temporal" a abonados.
-- El antiguo "suspendido" pasa a "suspension_temporal".
--   mysql -u root -p jaap_db < migracion_estado_abonado.sql
ALTER TABLE `abonados`
  MODIFY `estado` ENUM('activo','inactivo','suspendido','cortado','suspension_temporal') NOT NULL DEFAULT 'activo';
UPDATE `abonados` SET `estado` = 'suspension_temporal' WHERE `estado` = 'suspendido';
ALTER TABLE `abonados`
  MODIFY `estado` ENUM('activo','inactivo','cortado','suspension_temporal') NOT NULL DEFAULT 'activo';
