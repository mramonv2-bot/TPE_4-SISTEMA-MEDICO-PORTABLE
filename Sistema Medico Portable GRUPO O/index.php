<?php
// index.php
require_once 'db_config.php';
session_start();

// Configurar zona horaria si es necesario
date_default_timezone_set('America/Guayaquil'); 

$msg = "";
$msg_type = ""; 

// Captura de mensajes por URL
if (isset($_GET['success'])) {
    $msg = "Cita registrada correctamente.";
    $msg_type = "success";
}
if (isset($_GET['deleted'])) {
    $msg = "La cita fue eliminada del sistema.";
    $msg_type = "success";
}

// LOGIN
if (isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND password = ?");
    $stmt->execute([$_POST['user'], $_POST['pass']]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['usuario'] = $user['usuario'];
        header("Location: index.php");
        exit;
    } else {
        $msg = "Credenciales incorrectas. Inténtalo de nuevo.";
        $msg_type = "error";
    }
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// AGENDAR CITA
if (isset($_POST['agendar'])) {
    $nombre = trim($_POST['paciente_nombre']);
    $apellido = trim($_POST['paciente_apellido']);
    $cedula = trim($_POST['cedula']);
    $especialidad = $_POST['especialidad'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    $current_date = date('Y-m-d');
    $current_time = date('H:i');
    
    $errores = [];

    // Validaciones de formato
    if (empty($nombre) || empty($apellido)) {
        $errores[] = "Los nombres y apellidos son obligatorios.";
    }
    if (!preg_match('/^[0-9]+$/', $cedula)) {
        $errores[] = "La cédula debe contener únicamente números.";
    }
    
    // Validación de tiempo (solo futuro)
    if ($fecha < $current_date) {
        $errores[] = "No se pueden agendar citas en fechas pasadas.";
    } elseif ($fecha === $current_date && $hora <= $current_time) {
        $errores[] = "La hora de la cita debe ser posterior a la hora actual.";
    }

    // Validación estricta del rango de horas (8:00 AM a 6:00 PM)
    if ($hora < '08:00' || $hora > '18:00') {
        $errores[] = "El horario permitido para citas es de 08:00 a.m. a 06:00 p.m.";
    }

    if (empty($errores)) {
        // VALIDACIÓN ANTI-DUPLICADOS
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE cedula = ? AND fecha = ? AND hora = ?");
        $check_stmt->execute([$cedula, $fecha, $hora]);
        $existe_cita = $check_stmt->fetchColumn();

        if ($existe_cita > 0) {
            $msg = "El paciente con cédula $cedula ya tiene una cita agendada para el $fecha a las $hora.";
            $msg_type = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO citas 
                (paciente_nombre, paciente_apellido, cedula, especialidad, fecha, hora) 
                VALUES (?, ?, ?, ?, ?, ?)");

            $stmt->execute([$nombre, $apellido, $cedula, $especialidad, $fecha, $hora]);
            header("Location: index.php?success=1");
            exit;
        }
    } else {
        $msg = implode(" ", $errores);
        $msg_type = "error";
    }
}

// BORRAR UNA CITA INDIVIDUAL
if (isset($_POST['borrar_cita'])) {
    $id_cita = (int)$_POST['id_cita'];
    $stmt = $pdo->prepare("DELETE FROM citas WHERE id = ?");
    $stmt->execute([$id_cita]);
    header("Location: index.php?deleted=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl - Sistema de Citas</title>
    <link rel="icon" href="data:,">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Personalización de la barra de desplazamiento para el tema oscuro */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #09090b; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3f3f46; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen font-sans antialiased">

    <div class="bg-zinc-900 border-b border-zinc-800/60 text-[10px] text-zinc-500 py-1.5 px-6 flex flex-wrap justify-end gap-x-3 gap-y-1 tracking-wide">
        <span class="font-semibold uppercase text-zinc-400">Autores:</span>
        <span>Anzuategui Macías Alfredo Benjamín</span>
        <span>•</span>
        <span>Guzmán Guzmán Rene Guillermo</span>
        <span>•</span>
        <span>Jurado Montoya Maite Michelle</span>
        <span>•</span>
        <span>Lucas Pincay Cesar Enrique</span>
        <span>•</span>
        <span>Macías Napa Andy Sebastián</span>
        <span>•</span>
        <span>Ramón Valiente Manuel Francisco</span>
        <span>•</span>
        <span>Rodríguez Jama Fiorella Valentina</span>
    </div>

    <header class="bg-zinc-900/40 backdrop-blur-md text-zinc-100 shadow-sm py-4 px-6 mb-8 flex justify-between items-center border-b border-zinc-800">
        <h1 class="text-xl font-medium tracking-tight flex items-center gap-3">
            <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            MediControl <span class="text-[10px] border border-zinc-700 bg-zinc-800 py-0.5 px-2 rounded text-zinc-400 font-normal tracking-wider">v2.0</span>
        </h1>
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-sm font-medium text-zinc-300">
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Dr(a). <?= ucfirst(htmlspecialchars($_SESSION['usuario'])) ?>
                </div>
                <a href="?logout=1" class="border border-zinc-700 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition px-3 py-1.5 rounded-md text-sm font-medium shadow-sm">Cerrar Sesión</a>
            </div>
        <?php endif; ?>
    </header>

    <main class="max-w-6xl mx-auto px-4 pb-12">

        <?php if ($msg != ""): ?>
            <div class="mb-6 p-4 rounded-lg border flex items-center shadow-md text-sm font-medium backdrop-blur-sm
                <?= $msg_type === 'success' ? 'bg-zinc-900/80 border-zinc-700 text-zinc-200' : '' ?>
                <?= $msg_type === 'error' ? 'bg-red-950/30 border-red-900/50 text-red-200' : '' ?>
            ">
                <?php if($msg_type === 'success'): ?>
                    <svg class="w-5 h-5 mr-2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <?php else: ?>
                    <svg class="w-5 h-5 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?php endif; ?>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['usuario'])): ?>
            <div class="max-w-md mx-auto bg-zinc-900 rounded-xl shadow-xl border border-zinc-800 p-8 mt-12">
                <div class="text-center mb-8">
                    <h2 class="text-xl font-semibold text-zinc-100">Acceso al Sistema</h2>
                    <p class="text-sm text-zinc-400 mt-1">Panel de administración médica</p>
                </div>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 uppercase tracking-wide mb-1.5">Usuario</label>
                        <input type="text" name="user" class="w-full px-4 py-2 bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 placeholder-zinc-500 focus:bg-zinc-800/50 focus:outline-none focus:ring-2 focus:ring-zinc-600 focus:border-transparent transition text-sm" placeholder="Ingrese su usuario" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-400 uppercase tracking-wide mb-1.5">Contraseña</label>
                        <input type="password" name="pass" class="w-full px-4 py-2 bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 placeholder-zinc-500 focus:bg-zinc-800/50 focus:outline-none focus:ring-2 focus:ring-zinc-600 focus:border-transparent transition text-sm" placeholder="••••••••" required>
                    </div>
                    <button name="login" class="w-full bg-zinc-100 hover:bg-white text-zinc-900 font-medium py-2.5 rounded-lg transition text-sm mt-2 shadow-sm">
                        Iniciar Sesión
                    </button>
                </form>
            </div>

        <?php else: ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-1 bg-zinc-900 p-6 rounded-xl shadow-sm border border-zinc-800 h-fit">
                    <h3 class="text-base font-semibold text-zinc-200 mb-5 flex items-center gap-2 border-b border-zinc-800 pb-3">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nueva Cita
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Nombres</label>
                            <input type="text" name="paciente_nombre" class="w-full px-3 py-2 text-sm bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 focus:bg-zinc-800/50 focus:ring-2 focus:ring-zinc-600 focus:border-transparent outline-none transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Apellidos</label>
                            <input type="text" name="paciente_apellido" class="w-full px-3 py-2 text-sm bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 focus:bg-zinc-800/50 focus:ring-2 focus:ring-zinc-600 focus:border-transparent outline-none transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Cédula de Identidad</label>
                            <input type="text" name="cedula" pattern="[0-9]+" title="Solo se permiten números" class="w-full px-3 py-2 text-sm bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 focus:bg-zinc-800/50 focus:ring-2 focus:ring-zinc-600 focus:border-transparent outline-none transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1">Especialidad</label>
                            <select name="especialidad" class="w-full px-3 py-2 text-sm bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 focus:bg-zinc-800/50 focus:ring-2 focus:ring-zinc-600 focus:border-transparent outline-none transition">
                                <option value="Medicina General">Medicina General</option>
                                <option value="Cardiología">Cardiología</option>
                                <option value="Pediatría">Pediatría</option>
                                <option value="Odontología">Odontología</option>
                                <option value="Oftalmología">Oftalmología</option>
                                <option value="Traumatología">Traumatología</option>
                                <option value="Psicología">Psicología</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-400 mb-1">Fecha</label>
                                <input type="date" name="fecha" min="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 text-sm bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 focus:bg-zinc-800/50 focus:ring-2 focus:ring-zinc-600 focus:border-transparent outline-none transition" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-400 mb-1">Hora</label>
                                <input type="time" name="hora" min="08:00" max="18:00" class="w-full px-3 py-2 text-sm bg-zinc-800 rounded-lg border border-zinc-700 text-zinc-100 focus:bg-zinc-800/50 focus:ring-2 focus:ring-zinc-600 focus:border-transparent outline-none transition" required>
                            </div>
                        </div>
                        <p class="text-[11px] text-zinc-400 bg-zinc-950/50 p-2 rounded border border-zinc-800 text-center">
                            Horario de atención: 8:00 a.m. a 6:00 p.m.
                        </p>

                        <button name="agendar" class="w-full bg-zinc-100 hover:bg-white text-zinc-900 font-medium py-2 rounded-lg transition text-sm mt-4 shadow-sm">
                            Agendar Cita
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-zinc-900 rounded-xl shadow-sm border border-zinc-800 overflow-hidden">
                        <div class="p-5 border-b border-zinc-800 bg-zinc-900/60 flex justify-between items-center">
                            <h3 class="font-semibold text-zinc-200 text-base flex items-center gap-2">
                                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Registro de Citas
                            </h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-zinc-900 border-b border-zinc-800 text-zinc-400 uppercase text-[10px] font-bold tracking-wider">
                                        <th class="py-3 px-5">Paciente</th>
                                        <th class="py-3 px-5">Especialidad</th>
                                        <th class="py-3 px-5">Horario</th>
                                        <th class="py-3 px-5 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-800">
                                    <?php
                                    $stmt = $pdo->query("SELECT * FROM citas ORDER BY fecha ASC, hora ASC");
                                    $citas_count = 0;

                                    while ($row = $stmt->fetch()):
                                        $citas_count++;
                                        $esp = $row['especialidad'];
                                    ?>
                                    <tr class="hover:bg-zinc-800/30 transition group">
                                        <td class="py-3 px-5">
                                            <div class="font-medium text-zinc-200 text-sm"><?= htmlspecialchars($row['paciente_nombre'] . " " . $row['paciente_apellido']) ?></div>
                                            <div class="text-xs text-zinc-500 font-mono mt-0.5">CI: <?= htmlspecialchars($row['cedula']) ?></div>
                                        </td>
                                        <td class="py-3 px-5">
                                            <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-md bg-zinc-800 text-zinc-300 border border-zinc-700">
                                                <?= htmlspecialchars($esp) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-5">
                                            <div class="text-sm font-medium text-zinc-300"><?= htmlspecialchars($row['fecha']) ?></div>
                                            <div class="text-xs text-zinc-500"><?= htmlspecialchars($row['hora']) ?></div>
                                        </td>
                                        <td class="py-3 px-5 text-center">
                                            <form method="POST" onsubmit="return confirm('¿Confirma que desea cancelar esta cita?');" class="inline-block">
                                                <input type="hidden" name="id_cita" value="<?= $row['id'] ?>">
                                                <button type="submit" name="borrar_cita" class="text-zinc-500 hover:text-red-400 hover:bg-red-950/40 p-1.5 rounded transition" title="Cancelar cita">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>

                                    <?php if ($citas_count === 0): ?>
                                        <tr>
                                            <td colspan="4" class="py-12 text-center text-zinc-500 text-sm">
                                                <svg class="w-10 h-10 mx-auto text-zinc-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                                No hay citas médicas registradas en el sistema.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        <?php endif; ?>

    </main>

</body>
</html>