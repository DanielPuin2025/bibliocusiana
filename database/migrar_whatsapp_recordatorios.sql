USE biblioteca_db;

ALTER TABLE estudiantes
    ADD COLUMN telefono_whatsapp VARCHAR(20) NULL AFTER correo;

ALTER TABLE estudiantes
    ADD COLUMN whatsapp_consentimiento BOOLEAN NOT NULL DEFAULT FALSE AFTER telefono_whatsapp;

CREATE TABLE IF NOT EXISTS notificaciones_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL,
    dias_antes TINYINT UNSIGNED NOT NULL,
    enviado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY un_whatsapp_por_prestamo (prestamo_id, dias_antes),
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE
);