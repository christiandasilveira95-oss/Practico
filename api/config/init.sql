-- ========================================================
-- SCRIPT DE CREACIÓN: LOFI-LIBRARY (Biblioteca Virtual)
-- Práctico 2 y 3 - Base de datos, login, roles y catálogo
-- ========================================================
-- Se ejecuta automáticamente la primera vez que se levanta el contenedor
-- de MySQL (docker-entrypoint-initdb.d). También se puede importar a mano
-- desde phpMyAdmin (http://localhost:8081).

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS lofi_library CHARACTER SET utf8mb4;
USE lofi_library;

-- Permite volver a ejecutar el script sin errores (borra y recrea las tablas)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS LIBROS;
DROP TABLE IF EXISTS CATEGORIAS;
DROP TABLE IF EXISTS AUTORES;
DROP TABLE IF EXISTS USUARIOS;
DROP TABLE IF EXISTS ROLES;
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- 1. CREACIÓN DE TABLAS (DDL)
-- ========================================================

CREATE TABLE ROLES (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(30) NOT NULL UNIQUE,   -- 'Administrador' o 'Usuario'
    descripcion VARCHAR(150)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE USUARIOS (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,       -- se usa como nombre de usuario al iniciar sesión
    password_hash VARCHAR(255) NOT NULL,      -- NUNCA la contraseña en texto plano: hash de password_hash()
    id_rol INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,     -- 1 = puede ingresar, 0 = cuenta bloqueada
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL,
    FOREIGN KEY (id_rol) REFERENCES ROLES(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE AUTORES (
    id_autor INT AUTO_INCREMENT PRIMARY KEY,
    nombre_autor VARCHAR(100) NOT NULL UNIQUE,
    nacionalidad VARCHAR(60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE CATEGORIAS (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(150)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE LIBROS (
    id_libro INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    id_autor INT NOT NULL,
    id_categoria INT NOT NULL,
    imagen VARCHAR(255),                      -- ruta relativa a la raíz del sitio (ej: assets/img/portada.png)
    precio DECIMAL(10, 2),
    disponible TINYINT(1) NOT NULL DEFAULT 1, -- 1 = Disponible, 0 = No disponible
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_alta INT NULL,                 -- administrador que cargó el libro
    FOREIGN KEY (id_autor) REFERENCES AUTORES(id_autor),
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIAS(id_categoria),
    FOREIGN KEY (id_usuario_alta) REFERENCES USUARIOS(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- 2. POBLACIÓN DE DATOS (DML - INSERTS)
-- ========================================================

INSERT INTO ROLES (nombre_rol, descripcion) VALUES
('Administrador', 'Gestiona el catálogo desde el dashboard de administración'),
('Usuario', 'Cliente de la biblioteca: navega el catálogo y accede a su sección personal');

-- Contraseñas de prueba (guardadas como hash bcrypt):
--   admin@lofilibrary.com    -> Admin123!
--   usuario@lofilibrary.com  -> Usuario123!
INSERT INTO USUARIOS (nombre, email, password_hash, id_rol) VALUES
('Administrador Lofi', 'admin@lofilibrary.com',   '$2y$10$doG.LukJpUFwpeWrFoKIuunH3mpWE2bpyPWT63Ij7MtTu76oH7PyO', 1),
('Usuario Demo',       'usuario@lofilibrary.com', '$2y$10$U1JjbolgepyW6a69mG4PxOZWLiIxp8u83hdGwqPHnW9cwYGXTQQAK', 2);

INSERT INTO AUTORES (nombre_autor, nacionalidad) VALUES
('Rebecca Yarros',      'Estados Unidos'),
('J. R. R. Tolkien',    'Reino Unido'),
('Andrzej Sapkowski',   'Polonia'),
('George R. R. Martin', 'Estados Unidos');

INSERT INTO CATEGORIAS (nombre_categoria, descripcion) VALUES
('Fantasía épica',       'Mundos imaginarios, guerras y grandes travesías'),
('Romantasía',           'Fantasía con una historia romántica como eje central'),
('Ciencia ficción',      'Futuros posibles, tecnología y exploración espacial'),
('Misterio y suspenso',  'Intriga, investigación y giros inesperados'),
('Terror',               'Historias para pasar un buen susto'),
('Clásicos',             'Obras fundamentales de la literatura');

-- Catálogo inicial: los mismos 10 libros que estaban escritos a mano en el index.html del Práctico 1.
-- Los precios son valores de ejemplo (en pesos uruguayos) y se pueden editar desde el dashboard.
INSERT INTO LIBROS (titulo, descripcion, id_autor, id_categoria, imagen, precio, disponible, id_usuario_alta) VALUES
('Alas de sangre',
 'Violet Sorrengail debía vivir entre libros, pero es obligada a entrar al Colegio de Guerra de Basgiath, donde solo sobreviven quienes logran vincularse con un dragón.',
 1, 2, 'assets/img/alas de sangre.webp', 1190.00, 1, 1),
('Alas de hierro',
 'Segundo libro de la saga Empíreo. Violet sobrevive al primer año, pero los secretos del reino y las nuevas pruebas la ponen contra las cuerdas.',
 1, 2, 'assets/img/alas de hierro.jpg', 1290.00, 1, 1),
('Alas de ónix',
 'Tercer libro de la saga Empíreo. Violet y sus aliados buscan apoyo lejos de casa mientras la amenaza sobre Navarre sigue creciendo.',
 1, 2, 'assets/img/alas de onix.jpg', 1390.00, 1, 1),
('El Señor de los Anillos',
 'La travesía de Frodo y la Compañía del Anillo para destruir el Anillo Único en las llamas del Monte del Destino.',
 2, 1, 'assets/img/El senor de los anillos.png', 1490.00, 1, 1),
('Tiempo de odio',
 'Segunda novela de la saga del brujo. El Cónclave de Thanedd rompe la unión de los magos y Geralt debe proteger a Ciri.',
 3, 1, 'assets/img/Tiempo de Odio.png', 990.00, 1, 1),
('Juego de tronos',
 'Primer libro de Canción de hielo y fuego. Las grandes casas de Poniente se disputan el Trono de Hierro mientras una amenaza antigua despierta en el Norte.',
 4, 1, 'assets/img/Juego de Tronos.png', 1350.00, 1, 1),
('Bautismo de fuego',
 'Tercera novela de la saga del brujo. Geralt reúne compañeros inesperados y cruza un territorio arrasado por la guerra en busca de Ciri.',
 3, 1, 'assets/img/Bautismo de fuego.png', 990.00, 1, 1),
('El último deseo',
 'Primer libro de la saga del brujo: relatos que presentan a Geralt de Rivia, a Yennefer y a un mundo donde los monstruos no son lo más peligroso.',
 3, 1, 'assets/img/El Ultimo Deseo.png', 950.00, 1, 1),
('La espada del destino',
 'Segundo volumen de relatos del brujo. Aparece Ciri, la niña a la que el destino ata a Geralt.',
 3, 1, 'assets/img/La Espada del Destino.png', 950.00, 1, 1),
('La sangre de los elfos',
 'Primera novela de la saga del brujo. Geralt protege a Ciri y la prepara para un destino que muchos quieren controlar.',
 3, 1, 'assets/img/La Sangre de los Elfos.png', 990.00, 1, 1);
