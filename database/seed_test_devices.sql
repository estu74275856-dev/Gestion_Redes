USE gestion_redes;

INSERT INTO dispositivos
    (dis_id, dis_nombre, dis_tipo, dis_ip, dis_ubicacion, dis_stat)
SELECT
    'DTEST', 'Router de prueba', 'RT', '127.0.0.2', 'Laboratorio', 'I'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM dispositivos
    WHERE dis_id = 'DTEST' OR dis_ip = '127.0.0.2'
);

INSERT INTO dispositivos
    (dis_id, dis_nombre, dis_tipo, dis_ip, dis_ubicacion, dis_stat)
SELECT
    'DDEMO', 'Switch de prueba', 'SW', '192.0.2.10', 'Mesa de pruebas', 'I'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM dispositivos
    WHERE dis_id = 'DDEMO' OR dis_ip = '192.0.2.10'
);
