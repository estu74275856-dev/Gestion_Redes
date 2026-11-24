CREATE DATABASE IF NOT EXISTS gestion_redes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestion_redes;

CREATE TABLE IF NOT EXISTS dispositivos (
    dis_id CHAR(5) NOT NULL,
    dis_nombre VARCHAR(100) NOT NULL,
    dis_tipo CHAR(2) NOT NULL,
    dis_ip VARCHAR(50) NOT NULL,
    dis_ubicacion VARCHAR(120) DEFAULT NULL,
    dis_mac VARCHAR(20) DEFAULT NULL,
    dis_sisop VARCHAR(100) DEFAULT NULL,
    dis_obs VARCHAR(250) DEFAULT NULL,
    dis_stat CHAR(1) NOT NULL DEFAULT 'I',
    dis_feing DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dis_femod DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (dis_id),
    UNIQUE KEY uq_dispositivos_ip (dis_ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoreos (
    mon_id CHAR(8) NOT NULL,
    dis_id CHAR(5) NOT NULL,
    mon_conexion VARCHAR(20) NOT NULL,
    mon_latencia DECIMAL(10,2) DEFAULT NULL,
    mon_ucpu DECIMAL(5,2) DEFAULT NULL,
    mon_umem DECIMAL(5,2) DEFAULT NULL,
    mon_fec DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (mon_id),
    KEY idx_monitoreos_dis_id (dis_id),
    KEY idx_monitoreos_mon_fec (mon_fec),
    CONSTRAINT fk_monitoreos_dispositivo
        FOREIGN KEY (dis_id) REFERENCES dispositivos (dis_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incidencias (
    incid_id CHAR(10) NOT NULL,
    dis_id CHAR(5) NOT NULL,
    incid_titulo VARCHAR(150) NOT NULL,
    incid_desc VARCHAR(250) NOT NULL,
    incid_tipo CHAR(2) NOT NULL,
    incid_prioridad CHAR(2) NOT NULL DEFAULT 'MD',
    incid_stat CHAR(1) NOT NULL DEFAULT 'A',
    incid_origen CHAR(1) NOT NULL DEFAULT 'U',
    incid_feing DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    incid_fesol DATETIME DEFAULT NULL,
    PRIMARY KEY (incid_id),
    KEY idx_incidencias_dis_id (dis_id),
    KEY idx_incidencias_incid_stat (incid_stat),
    CONSTRAINT fk_incidencias_dispositivo
        FOREIGN KEY (dis_id) REFERENCES dispositivos (dis_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
