import pandas as pd
import mysql.connector

# 1. Cargar el inventario omitiendo los encabezados institucionales
excel_file = 'INVENTARIO DE BIBLIOTECA 2026.xlsx'
df = pd.read_excel(excel_file, skiprows=4)

# Renombrar columnas según el orden detectado en el archivo
df.columns = [
    'Item', 'Codigo', 'Articulo', 'Editorial', 
    'Ubicacion', 'Estado_Bueno', 'Estado_Regular', 
    'Estado_Malo', 'Cantidad'
]

# 2. Conectar a la base de datos MySQL en XAMPP
try:
    db = mysql.connector.connect(
        host="localhost",
        user="root",
        password="", # Déjalo vacío si no cambiaste la clave de XAMPP
    )
    cursor = db.cursor()

    # Crear la base de datos y la tabla si no existen
    cursor.execute("CREATE DATABASE IF NOT EXISTS biblioteca;")
    cursor.execute("USE biblioteca;")
    
    tabla_sql = """
    CREATE TABLE IF NOT EXISTS libros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50),
        titulo VARCHAR(255),
        editorial VARCHAR(150),
        ubicacion VARCHAR(100),
        estado VARCHAR(50),
        cantidad INT,
        imagen VARCHAR(255) DEFAULT 'default.jpg'
    );
    """
    cursor.execute(tabla_sql)

    # 3. Iterar sobre las filas e insertar la información
    for _, row in df.iterrows():
        titulo = str(row['Articulo'])
        if pd.isna(row['Articulo']) or titulo == 'nan':
            continue  # Omitir filas vacías

        codigo = str(row['Codigo']) if pd.notna(row['Codigo']) else ''
        editorial = str(row['Editorial']) if pd.notna(row['Editorial']) else ''
        ubicacion = str(row['Ubicacion']) if pd.notna(row['Ubicacion']) else ''
        
        # Determinar estado del libro
        estado = 'Bueno'
        if row['Estado_Regular'] == 'X':
            estado = 'Regular'
        elif row['Estado_Malo'] == 'X':
            estado = 'Malo'

        cantidad = int(row['Cantidad']) if pd.notna(row['Cantidad']) else 1

        insert_sql = """
        INSERT INTO libros (codigo, titulo, editorial, ubicacion, estado, cantidad, imagen)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        valores = (codigo, titulo, editorial, ubicacion, estado, cantidad, 'default.jpg')
        cursor.execute(insert_sql, valores)

    db.commit()
    print("Migración completada con éxito en la base de datos 'biblioteca'.")

except mysql.connector.Error as err:
    print(f"Error en la base de datos: {err}")
finally:
    if 'db' in locals() and db.is_connected():
        cursor.close()
        db.close()