"""Migra el inventario del archivo Excel a MySQL de XAMPP.

Instalación (una sola vez):
    pip install pandas openpyxl mysql-connector-python

Ejecución desde la raíz del proyecto:
    python database/migrar_inventario_excel.py
"""

from pathlib import Path
import math
import sys

import mysql.connector
import pandas as pd


ARCHIVO_EXCEL = Path(__file__).with_name("INVENTARIO DE BIBLIOTECA 2026.xlsx")
NOMBRE_TABLA = "inventario_biblioteca"
IMAGEN_ESTANDAR = "assets/portadas/portada-estandar.svg"
CONFIG_MYSQL = {
    "host": "localhost",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "biblioteca_db",
    "charset": "utf8mb4",
}

COLUMNAS_REQUERIDAS = [
    "Item", "Codigo", "Articulo", "Editorial", "Ubicacion",
    "Estado_Bueno", "Estado_Regular", "Estado_Malo", "Cantidad",
]


def es_vacio(valor):
    return valor is None or (isinstance(valor, float) and math.isnan(valor)) or pd.isna(valor)


def texto(valor):
    """Convierte valores de Excel a texto, conservando códigos sin '.0'."""
    if es_vacio(valor):
        return None
    if isinstance(valor, float) and valor.is_integer():
        return str(int(valor))
    resultado = str(valor).strip()
    return resultado or None


def entero(valor):
    if es_vacio(valor) or str(valor).strip() == "":
        return 0
    try:
        return int(float(valor))
    except (TypeError, ValueError):
        return 0


def crear_tabla(cursor):
    cursor.execute(f"""
        CREATE TABLE IF NOT EXISTS {NOMBRE_TABLA} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item INT NULL,
            codigo VARCHAR(100) NULL,
            articulo VARCHAR(255) NOT NULL,
            editorial VARCHAR(255) NULL,
            ubicacion VARCHAR(255) NULL,
            estado_bueno INT UNSIGNED NOT NULL DEFAULT 0,
            estado_regular INT UNSIGNED NOT NULL DEFAULT 0,
            estado_malo INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad INT UNSIGNED NOT NULL DEFAULT 0,
            imagen VARCHAR(255) NOT NULL DEFAULT '{IMAGEN_ESTANDAR}',
            fecha_migracion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_inventario_codigo (codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    """)


def leer_inventario():
    if not ARCHIVO_EXCEL.is_file():
        raise FileNotFoundError(f"No se encontró el archivo: {ARCHIVO_EXCEL}")

    # La fila 5 contiene los encabezados, por eso se omiten las cuatro primeras.
    datos = pd.read_excel(ARCHIVO_EXCEL, skiprows=4, dtype=object)
    datos.columns = [str(columna).strip() for columna in datos.columns]
    faltantes = set(COLUMNAS_REQUERIDAS) - set(datos.columns)
    if faltantes:
        raise ValueError("Faltan columnas requeridas: " + ", ".join(sorted(faltantes)))

    datos = datos[COLUMNAS_REQUERIDAS].dropna(how="all")
    # No se insertan filas vacías o filas sin título de libro.
    return datos[datos["Articulo"].map(texto).notna()]


def migrar():
    inventario = leer_inventario()
    conexion = mysql.connector.connect(**CONFIG_MYSQL)
    cursor = conexion.cursor()
    crear_tabla(cursor)

    sentencia = f"""
        INSERT INTO {NOMBRE_TABLA} (
            item, codigo, articulo, editorial, ubicacion,
            estado_bueno, estado_regular, estado_malo, cantidad, imagen
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            item = VALUES(item), articulo = VALUES(articulo),
            editorial = VALUES(editorial), ubicacion = VALUES(ubicacion),
            estado_bueno = VALUES(estado_bueno),
            estado_regular = VALUES(estado_regular),
            estado_malo = VALUES(estado_malo), cantidad = VALUES(cantidad),
            imagen = VALUES(imagen)
    """

    filas = []
    for _, libro in inventario.iterrows():
        filas.append((
            entero(libro["Item"]) or None,
            texto(libro["Codigo"]),
            texto(libro["Articulo"]),
            texto(libro["Editorial"]),
            texto(libro["Ubicacion"]),
            entero(libro["Estado_Bueno"]),
            entero(libro["Estado_Regular"]),
            entero(libro["Estado_Malo"]),
            entero(libro["Cantidad"]),
            IMAGEN_ESTANDAR,
        ))

    cursor.executemany(sentencia, filas)
    conexion.commit()
    print(f"Migración completada: {len(filas)} filas procesadas en '{NOMBRE_TABLA}'.")
    cursor.close()
    conexion.close()


if __name__ == "__main__":
    try:
        migrar()
    except (FileNotFoundError, ValueError, mysql.connector.Error) as error:
        print(f"Error de migración: {error}", file=sys.stderr)
        sys.exit(1)
