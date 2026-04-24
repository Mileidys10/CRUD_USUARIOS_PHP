<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/Infrastructure/Config/DependencyInjection.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'];
$currentUserName = $_SESSION['user_name'];

$message = "";
$error = "";

// Procesar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                $command = new CreateUserCommand(
                    uniqid(), 
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['password'],
                    $_POST['role']
                );
                $createUserService->execute($command);
                $message = "¡Usuario creado con éxito!";
                break;

            case 'update':
                $command = new UpdateUserCommand(
                    $_POST['id'],
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['password'] ?? '',
                    $_POST['role'],
                    $_POST['status']
                );
                $updateUserService->execute($command);
                $message = "¡Usuario actualizado con éxito!";
                break;

            case 'delete':
                if ($currentUserRole !== 'ADMIN') throw new Exception("No tienes permisos para eliminar usuarios.");
                $command = new DeleteUserCommand($_POST['id']);
                $deleteUserService->execute($command);
                $message = "Usuario eliminado.";
                break;

            case 'toggle_status':
                if ($currentUserRole !== 'ADMIN') throw new Exception("Solo administradores pueden cambiar el estado.");
                
                $userId = new UserId($_POST['id']);
                $user = $getUserByIdService->execute(new GetUserByIdQuery($_POST['id']));
                
                $newStatus = ($user->status() === UserStatusEnum::ACTIVE) ? UserStatusEnum::PENDING : UserStatusEnum::ACTIVE;
                
                $command = new UpdateUserCommand(
                    $user->id()->value(),
                    $user->name()->value(),
                    $user->email()->value(),
                    '', 
                    $user->role(),
                    $newStatus
                );
                $updateUserService->execute($command);
                $message = "Estado del usuario actualizado a " . $newStatus;
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$users = $getAllUsersService->execute(new GetAllUsersQuery());

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión de Usuarios</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --secondary: #64748b;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(circle at top right, #1e1b4b, transparent),
                              radial-gradient(circle at bottom left, #1e1b4b, transparent);
            color: var(--text);
            margin: 0;
            min-height: 100vh;
            padding-top: 80px;
        }

        /* Navbar */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            z-index: 1000;
        }

        .nav-logo { font-weight: 600; font-size: 1.2rem; color: #818cf8; display: flex; align-items: center; gap: 10px; }
        .nav-user { display: flex; align-items: center; gap: 20px; }
        .user-info { text-align: right; }
        .user-name { display: block; font-weight: 500; font-size: 0.9rem; }
        .user-role { display: block; font-size: 0.75rem; color: #94a3b8; }
        .logout-btn { 
            color: #f87171; 
            text-decoration: none; 
            font-size: 0.9rem; 
            display: flex; 
            align-items: center; 
            gap: 5px; 
            padding: 8px 15px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1); }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-section { margin-bottom: 30px; }
        h1 { font-weight: 600; margin: 0; color: #f8fafc; font-size: 1.8rem; }
        p { color: #94a3b8; margin: 5px 0 0; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        @media (max-width: 1024px) { .dashboard-grid { grid-template-columns: 1fr; } }

        /* Card common */
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
        }

        .card-title { font-weight: 600; margin-bottom: 25px; color: #cbd5e1; display: flex; align-items: center; gap: 10px; }

        /* Form Styles */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #94a3b8; }
        input, select {
            width: 100%;
            padding: 12px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            box-sizing: border-box;
            transition: all 0.3s;
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

        /* Table Styles */
        .table-card { padding: 0; overflow: hidden; }
        .table-header { padding: 25px 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 30px; color: #94a3b8; font-weight: 500; font-size: 0.85rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        td { padding: 20px 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.9rem; }
        
        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-active { background: rgba(34, 197, 94, 0.1); color: #4ade80; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; }
        .status-blocked { background: rgba(239, 68, 68, 0.1); color: #f87171; }

        .role-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            text-transform: uppercase;
        }

        .actions { display: flex; gap: 10px; }
        .action-btn {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
            transition: color 0.3s;
        }
        .btn-edit:hover { color: var(--primary); }
        .btn-delete:hover { color: var(--danger); }
        .btn-toggle:hover { color: var(--warning); }

        .alert { padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #4ade80; border-left: 4px solid #22c55e; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #f87171; border-left: 4px solid #ef4444; }

        /* Modal */
        #editModal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #1e293b;
            width: 100%;
            max-width: 450px;
            border-radius: 24px;
            padding: 40px;
            position: relative;
        }
        .close-modal { position: absolute; right: 30px; top: 30px; font-size: 1.5rem; color: #94a3b8; cursor: pointer; }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">
        <i class="fas fa-shield-halved"></i>
        <span>UserVault <strong>PRO</strong></span>
    </div>
    <div class="nav-user">
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($currentUserName); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($currentUserRole); ?></span>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Salir
        </a>
    </div>
</nav>

<div class="container">
    <div class="header-section">
        <h1>Gestión de Usuarios</h1>
        <p>Administra el acceso y roles de tu plataforma hexagonal.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- Sidebar: Crear -->
        <div class="card">
            <div class="card-title"><i class="fas fa-user-plus"></i> Registrar Usuario</div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="name" required placeholder="Ej. Juan Pérez">
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" required placeholder="juan@ejemplo.com">
                </div>
                <div class="form-group">
                    <label>Contraseña Inicial</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Rol Asignado</label>
                    <select name="role">
                        <option value="MEMBER">Usuario Estándar</option>
                        <option value="ADMIN">Administrador</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Crear Cuenta</button>
            </form>
        </div>

        <!-- Main: Lista -->
        <div class="card table-card">
            <div class="table-header">
                <div class="card-title" style="margin-bottom: 0;"><i class="fas fa-users"></i> Directorio de Usuarios</div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="4" style="text-align:center; color:#64748b; padding: 40px;">No hay usuarios registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($user->name()->value()); ?></div>
                                        <div style="font-size: 0.8rem; color: #94a3b8;"><?php echo htmlspecialchars($user->email()->value()); ?></div>
                                    </td>
                                    <td><span class="role-badge"><?php echo htmlspecialchars($user->role()); ?></span></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'status-pending';
                                            if ($user->status() === UserStatusEnum::ACTIVE) $statusClass = 'status-active';
                                            if ($user->status() === UserStatusEnum::BLOCKED) $statusClass = 'status-blocked';
                                        ?>
                                        <span class="status-pill <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($user->status()); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button onclick="openEditModal('<?php echo $user->id()->value(); ?>', '<?php echo htmlspecialchars($user->name()->value()); ?>', '<?php echo htmlspecialchars($user->email()->value()); ?>', '<?php echo $user->role(); ?>', '<?php echo $user->status(); ?>')" class="action-btn btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($currentUserRole === 'ADMIN'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Alternar estado?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?php echo $user->id()->value(); ?>">
                                                    <button type="submit" class="action-btn btn-toggle" title="Activar/Desactivar">
                                                        <i class="fas fa-power-off"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $user->id()->value(); ?>">
                                                    <button type="submit" class="action-btn btn-delete" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edición -->
<div id="editModal">
    <div class="modal-content">
        <i class="fas fa-times close-modal" onclick="closeEditModal()"></i>
        <div class="card-title"><i class="fas fa-user-edit"></i> Editar Usuario</div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="form-group">
                <label>Nueva Contraseña (dejar en blanco para mantener)</label>
                <input type="password" name="password" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="role" id="edit_role">
                    <option value="MEMBER">Usuario Estándar</option>
                    <option value="ADMIN">Administrador</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status" id="edit_status">
                    <option value="ACTIVE">Activo</option>
                    <option value="PENDING">Pendiente</option>
                    <option value="BLOCKED">Bloqueado</option>
                    <option value="INACTIVE">Inactivo</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, email, role, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_status').value = status;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Cerrar modal al hacer click fuera
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeEditModal();
    }
}
</script>

</body>
</html>
