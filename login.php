<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/Infrastructure/Config/DependencyInjection.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $command = new LoginCommand($_POST['email'], $_POST['password']);
        $user = $loginService->execute($command);
        
        $_SESSION['user_id'] = $user->id()->value();
        $_SESSION['user_name'] = $user->name()->value();
        $_SESSION['user_role'] = $user->role();
        
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestión de Usuarios</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.8);
            --text: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(circle at top right, #1e1b4b, transparent),
                              radial-gradient(circle at bottom left, #1e1b4b, transparent);
            color: var(--text);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        h1 { font-weight: 600; text-align: center; margin-bottom: 30px; color: #818cf8; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #94a3b8; }
        input {
            width: 100%;
            padding: 12px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            box-sizing: border-box;
            transition: all 0.3s;
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        button:hover { background: var(--primary-hover); }

        .alert {
            padding: 12px;
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h1>Bienvenido</h1>
    
    <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="email" required placeholder="admin@ejemplo.com">
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit">Iniciar Sesión</button>
    </form>
</div>

</body>
</html>
