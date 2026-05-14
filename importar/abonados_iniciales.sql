-- ═══════════════════════════════════════════════════════════════════
-- JAAP – Importación inicial del padrón de abonados
-- Generado: 2026-05-14
-- Fuente: documento físico escaneado (3 zonas)
--
-- Total: 141 abonados
--   · Tunas:          52 (AB-0001 – AB-0052)
--   · Cerro de Pasco: 49 (AB-0053 – AB-0101)
--   · Porvenir:       40 (AB-0102 – AB-0141)
--
-- NOTAS:
--   · DNIs que empiezan con '999' son PROVISIONALES (no figuran en el
--     documento). Actualiza con el DNI real del titular.
--   · Entradas omitidas:
--       - Tunas #44 (tachado en el original)
--       - Tunas #51 (Local Comunal Comedor Nueva Esperanza)
--       - Pasco #37 (Iglesia Gloria de Dios)
--       - Pasco #39 (Iglesia Sanidad y Milagros)
--       - Porvenir #19 (Local de Vaso de Leche)
--       - Porvenir #36 (Iglesia La Voz de Cristo)
--   · Los nombres están transcritos del documento manuscrito.
--     Ejecutar verificar_nombres.php para validar contra migo.pe.
-- ═══════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────
-- ZONA: TUNAS  (52 abonados · AB-0001 – AB-0052)
-- ─────────────────────────────────────────────────────────────────────
INSERT INTO `abonados`
    (`codigo`,    `dni`,       `nombres`,            `apellidos`,           `zona`,   `estado`,  `fecha_inscripcion`, `observaciones`,                                            `creado_por`)
VALUES
    ('AB-0001', '32910901', 'Reynaldo Wilmer',    'Orue Solano',         'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0002', '45367936', 'Eustaqia Maila',     'Avila Jara',          'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0003', '32909343', 'Esther Galvina',     'Briones Durand',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0004', '18020275', 'Francisco Valerio',  'Lazaro Zavaleta',     'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0005', '80268695', 'Roberto Carlos',     'Olivos Atilano',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0006', '41701683', 'Cenovia Grimaldina', 'Vasquez Miranda',     'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0007', '42694117', 'Veronica Antolina',  'Avila Jara',          'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0008', '32904932', 'Juan Jose',          'Atilano Huado',       'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0009', '32818009', 'Heber Manuel',       'Ibañez Mendez',       'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0010', '76471901', 'Yesmely Yaguel',     'Vazquez Miranda',     'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0011', '32839425', 'Lucia Grimanesa',    'Ojeda Chavez',        'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0012', '32829628', 'Jaime Lucas',        'Minaya Herrera',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0013', '33264436', 'Juan Manuel',        'Roncal Briceño',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0014', '32906900', 'Pepe Abel',          'Minaya Herrera',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0015', '44296755', 'Josafat Juan',       'Aranibar Chapeton',   'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0016', '76285086', 'Guadalupe Matilde',  'Cadillo Garcia',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0017', '32113621', 'Viante Carlos',      'Goñe Leandro',        'tunas',  'activo', '2026-05-14', 'DNI a verificar – dígito final incierto en manuscrito', 1),
    ('AB-0018', '32530927', 'Susana Fausta',      'Aguilar Solano',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0019', '32944624', 'Maria Magdalena',    'Cueva Reyes',         'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0020', '75272460', 'Jhonatan Elias',     'Pereda Cueva',        'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0021', '32836353', 'Gerardina',          'Meregido Rodriguez',  'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0022', '32844266', 'Maria Emiliana',     'Morales Trevejo',     'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0023', '46717657', 'Silas Lener',        'Vasquez Miranda',     'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0024', '41788094', 'Emilia Cristina',    'Vergaray Morales',    'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0025', '32938572', 'Noe',                'Loyola Minaya',       'tunas',  'activo', '2026-05-14', 'DNI a verificar – dígitos inciertos en manuscrito', 1),
    ('AB-0026', '32980109', 'Rildo',              'Loyola Minaya',       'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0027', '40497910', 'Olga Lidia',         'Atilano Huado',       'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0028', '42642215', 'Ruth Mical',         'Minaya Goñe',         'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0029', '76632493', 'Eduardo Julio',      'Huarac Leon',         'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0030', '76521854', 'Mauricio Jesus',     'Javes Lara',          'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0031', '46489391', 'Rodolfo',            'Margarin Peña',       'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0032', '32940079', 'Rosa Isabel',        'Atilano Moreno',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0033', '43774732', 'Wilmer Agusto',      'Vellota Lara',        'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0034', '41996997', 'Fabian Pascual',     'Condor Capillo',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0035', '32917254', 'Jorge Luis',         'Minaya Herrera',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0036', '76529820', 'Jose Oswaldo',       'Cotrina Zelada',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0037', '18089456', 'Andy William',       'Romero Manchay',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0038', '32781666', 'Juan Francisco',     'Liñan Coronel',       'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0039', '44225259', 'Mario',              'Matos Caldas',        'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0040', '40551031', 'Samuel Miguel',      'Minaya Herrera',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0041', '32941243', 'Maria Elizabeth',    'Minaya Herrera',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0042', '32763846', 'Julio Jacinto',      'Olivos Rosales',      'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0043', '45420212', 'Delia Doris',        'Olivos Atilano',      'tunas',  'activo', '2026-05-14', NULL, 1),
    /* #44 omitido – entrada tachada en el documento original */
    ('AB-0044', '44027488', 'Marisol Yunet',      'Barrios Principe',    'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0045', '44792180', 'Julio Cesar',        'Lazaro Valdez',       'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0046', '32733274', 'Asuncion Abel',      'Orue Solano',         'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0047', '32962130', 'Humberto Porfirio',  'Orue Solano',         'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0048', '32763900', 'Miguel',             'Orue Corales',        'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0049', '99900001', 'Marisol',            'Dominguez Salasar',   'tunas',  'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1),
    /* #51 omitido – Local Comunal Comedor Nueva Esperanza */
    ('AB-0050', '32976913', 'Cesar',              'Lazaro Reyes',        'tunas',  'activo', '2026-05-14', NULL, 1),
    ('AB-0051', '99900002', 'Carlos',             'Samome',              'tunas',  'activo', '2026-05-14', 'Sin DNI – nombre incompleto en el padrón', 1),
    ('AB-0052', '32733218', 'Gina Marita',        'Ibañez Uchalin',      'tunas',  'activo', '2026-05-14', NULL, 1);

-- ─────────────────────────────────────────────────────────────────────
-- ZONA: CERRO DE PASCO – Carrizales  (49 abonados · AB-0053 – AB-0101)
-- ─────────────────────────────────────────────────────────────────────
INSERT INTO `abonados`
    (`codigo`,    `dni`,       `nombres`,            `apellidos`,              `zona`,            `estado`,  `fecha_inscripcion`, `observaciones`,                                            `creado_por`)
VALUES
    ('AB-0053', '32908590', 'Orestes Alberto',    'Dulce Aguirre',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0054', '32927830', 'Margarita Rita',     'Pinedo Cotos',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0055', '32907615', 'Marina',             'Dulce Aguirre',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0056', '43670416', 'Yaquelin Estefani',  'Dulce Barrios',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0057', '40864988', 'Gilmer',             'Barrios Perez',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0058', '42789472', 'Keny Williams',      'Avila Ibañez',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0059', '48724992', 'Manuela',            'Haro Vasquez',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0060', '32875131', 'Martha Rosa',        'Cornelio Bermudes',      'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0061', '32816512', 'Nicolas',            'Dulce Marcelo',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0062', '32782597', 'Nestor Porfirio',    'Chavez Alvarez',         'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0063', '46453441', 'Ylder',              'Chavez Coronel',         'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0064', '18020968', 'Juana Magdalena',    'Evangelista Gutierrez',  'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0065', '46440146', 'Yudi Anali',         'Ibañez Cruz',            'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0066', '32799877', 'Edelmer Elpidio',    'Ibañez Mendez',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0067', '41665859', 'Yuliza Mardily',     'Ibañez Merogildo',       'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0068', '42743666', 'Reyna Margarita',    'Dulce Reyna',            'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0069', '32845717', 'Rosa Barbarita',     'Lazaro de Barrios',      'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0070', '48193819', 'Rosita Esther',      'Barrios Lazaro',         'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0071', '32835750', 'Carlos',             'Ayala Perez',            'cerro_de_pasco', 'activo', '2026-05-14', 'Inicial del segundo nombre no legible en el manuscrito', 1),
    ('AB-0072', '32809787', 'Angie del Pilar',    'Rodriguez Conde',        'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0073', '32966323', 'Damian Claudio',     'Roque Zutizal',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0074', '42636832', 'Noemi Edith',        'Roque Flores',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0075', '48190636', 'Elias Daniel',       'Roque Flores',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0076', '99900003', 'Daniela Janet',      'Dulce Barrios',          'cerro_de_pasco', 'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1),
    ('AB-0077', '32923893', 'Carlos Hilario',     'Avila Castro',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0078', '92783101', 'Javier Aristides',   'Vasquez Berney',         'cerro_de_pasco', 'activo', '2026-05-14', 'DNI a verificar – primer dígito incierto en manuscrito (9 ó 3)', 1),
    ('AB-0079', '19090023', 'Carlos Manuel',      'Evangelista Castillo',   'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0080', '48297784', 'Julio Cesar',        'Llanos Evangelista',     'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0081', '32944052', 'Ademir Julio',       'Condori Osco',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0082', '44100758', 'Eleazar',            'Barrios Lazaro',         'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0083', '45098845', 'Nataly Mariluz',     'Dulce Barrios',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0084', '32738102', 'Santos',             'Dulce Vasquez',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0085', '47558420', 'Jhony',              'Evangelista Castillo',   'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0086', '46594368', 'Tereza',             'Castillo Guevara',       'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0087', '99900004', 'Lady Mayra',         'Evangelista Castillo',   'cerro_de_pasco', 'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1),
    ('AB-0088', '32537816', 'Venancio Esteban',   'Cueva Valera',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    /* #37 omitido – Iglesia Gloria de Dios */
    ('AB-0089', '32770183', 'Victor Julio',       'Ibañez Minchola',        'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    /* #39 omitido – Iglesia Sanidad y Milagros */
    ('AB-0090', '47608810', 'Abner Elpidio',      'Perez Pelaez',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0091', '46342375', 'Luz Amanda',         'Lazaro Salgado',         'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0092', '48133099', 'Rosmery Reimi',      'Evangelista Castillo',   'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0093', '32821194', 'Pablo',              'Santos Muñoz',           'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0094', '45370297', 'Hugo Alonso',        'Romero Santos',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0095', '42593470', 'Cinthya Vanessa',    'Romero Santos',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0096', '42846244', 'Cesar Mercedes',     'Evangelista',            'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0097', '76916018', 'Estiven Milton',     'Barrios Evangelista',    'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0098', '80191087', 'Felicita',           'Rodriguez Calderon',     'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0099', '48996524', 'Victor Baltazar',    'Lujan',                  'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0100', '47151910', 'Anthony',            'Chauca Cornelio',        'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1),
    ('AB-0101', '76843188', 'Manuel',             'Dulce Barrios',          'cerro_de_pasco', 'activo', '2026-05-14', NULL, 1);

-- ─────────────────────────────────────────────────────────────────────
-- ZONA: PORVENIR – Carrizales  (40 abonados · AB-0102 – AB-0141)
-- ─────────────────────────────────────────────────────────────────────
INSERT INTO `abonados`
    (`codigo`,    `dni`,       `nombres`,            `apellidos`,           `zona`,       `estado`,  `fecha_inscripcion`, `observaciones`,                                            `creado_por`)
VALUES
    ('AB-0102', '19022885', 'Alvaro',             'Pelaez Castro',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0103', '41664047', 'Luis Alberto',       'Guzman Orbegozo',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0104', '19701829', 'Wilder',             'Saona Rodriguez',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0105', '44708221', 'Jose Gilmer',        'Cotrina Zelada',      'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0106', '32740453', 'Juan Jesus',         'Aguilar Solano',      'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0107', '73481407', 'Ysela Katherin',     'Rosas German',        'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0108', '26666234', 'Utilio',             'Chilon Bustamante',   'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0109', '19026112', 'Antero Benjamin',    'Pelaez Castro',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0110', '19680216', 'Santiago',           'Saona Rodriguez',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0111', '75814097', 'Karina',             'Angulo Zavaleta',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0112', '19024646', 'Agustin',            'Perez Gomez',         'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0113', '75279374', 'Danitea Anabela',    'Perez Pelaez',        'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0114', '27901346', 'Segundo Porfirio',   'Cotrina Saucedo',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0115', '48882990', 'Santos',             'Cuevas Cruz',         'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0116', '48568261', 'Marcial Lorenzo',    'Avila Cuevas',        'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0117', '32910198', 'Elena Esmeralda',    'Llerena Chavez',      'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0118', '32897582', 'Gaudencio',          'Capillo Vargas',      'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0119', '32807940', 'Maria',              'Vargas Cardenas',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    /* #19 omitido – Local de Vaso de Leche */
    ('AB-0120', '32956618', 'Angelina Maribel',   'Lara Ventura',        'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0121', '45533728', 'Luis Fermin',        'Capillo Jacinto',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0122', '70608237', 'Kevin Roy',          'Chilon Pelaez',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0123', '41051156', 'Osmer Adid',         'Saona Rodriguez',     'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0124', '41131901', 'Esperanza',          'Zuñiga Solano',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0125', '46390137', 'Dina Elena',         'Aponte Heredia',      'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0126', '40991180', 'Jose Michel',        'Flores Carmon',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0127', '70609408', 'Elvin Edith',        'Chilon Pelaez',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0128', '99900005', 'Carolina',           'Cardenas Ladauro',    'porvenir', 'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1),
    ('AB-0129', '99900006', 'Rosendo',            'Vargas Paredes',      'porvenir', 'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1),
    ('AB-0130', '45132836', 'Wilder Teofilo',     'Cuevas Cuevas',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0131', '43571879', 'Arquimides',         'Angulo Baulio',       'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0132', '19037011', 'Eiter Lenin',        'Marcos Rodriguez',    'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0133', '44383732', 'Jose Amaro',         'Rodriguez Meregildo', 'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0134', '32785625', 'Nidia Emely',        'Ibañez Meregildo',    'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0135', '43112583', 'Americo',            'Qurizada de la Cruz', 'porvenir', 'activo', '2026-05-14', 'Apellido a verificar – escritura incierta en el manuscrito', 1),
    /* #36 omitido – Iglesia La Voz de Cristo */
    ('AB-0136', '99900007', 'Nolberto',           'Vega Mendoza',        'porvenir', 'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1),
    ('AB-0137', '23085755', 'Mariela Angela',     'Capillo Castillo',    'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0138', '44371301', 'Humberto',           'Lazaro Reyes',        'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0139', '99900008', 'Jordan',             'Zavaleta Zavaleta',   'porvenir', 'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1),
    ('AB-0140', '77333496', 'Emely',              'Vilca Lara',          'porvenir', 'activo', '2026-05-14', NULL, 1),
    ('AB-0141', '99900009', 'Jose',               'Salvatierra Chilon',  'porvenir', 'activo', '2026-05-14', 'Sin DNI en el padrón – actualizar con documento de identidad', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════════
-- RESUMEN
--   Tunas:          52 abonados  (AB-0001 – AB-0052)
--   Cerro de Pasco: 49 abonados  (AB-0053 – AB-0101)
--   Porvenir:       40 abonados  (AB-0102 – AB-0141)
--   TOTAL:         141 abonados
--
--   DNIs provisionales (999xxxxx): 9 abonados
--   DNIs a verificar con migo.pe:  4 abonados
--
-- Ejecuta verificar_nombres.php para validar todos los nombres.
-- ═══════════════════════════════════════════════════════════════════
