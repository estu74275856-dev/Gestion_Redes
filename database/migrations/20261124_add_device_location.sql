USE gestion_redes;

SET @has_dis_ubicacion := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'dispositivos'
      AND COLUMN_NAME = 'dis_ubicacion'
);

SET @migration_sql := IF(
    @has_dis_ubicacion = 0,
    'ALTER TABLE dispositivos ADD COLUMN dis_ubicacion VARCHAR(120) DEFAULT NULL AFTER dis_ip',
    'SELECT 1'
);

PREPARE add_device_location FROM @migration_sql;
EXECUTE add_device_location;
DEALLOCATE PREPARE add_device_location;
