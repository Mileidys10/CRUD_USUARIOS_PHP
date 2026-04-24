<?php
declare(strict_types=1);

require_once __DIR__ . '/../Adapters/Persistence/MySQL/Repository/UserRepositoryMySQL.php';
require_once __DIR__ . '/../Adapters/Persistence/MySQL/Mapper/UserPersistenceMapper.php';
require_once __DIR__ . '/../Adapters/Notification/PhpMailerAdapter.php';
require_once __DIR__ . '/../../Application/Services/CreateUserService.php';
require_once __DIR__ . '/../../Application/Services/UpdateUserService.php';
require_once __DIR__ . '/../../Application/Services/DeleteUserService.php';
require_once __DIR__ . '/../../Application/Services/GetUserByIdService.php';
require_once __DIR__ . '/../../Application/Services/GetAllUsersService.php';
require_once __DIR__ . '/../../Application/Services/LoginService.php';
require_once __DIR__ . '/../../Application/Services/Dto/Queries/GetAllUsersQuery.php';
require_once __DIR__ . '/../../Application/Services/Dto/Queries/GetUserByIdQuery.php';
require_once __DIR__ . '/../../Application/Services/Dto/Commands/UpdateUserCommand.php';
require_once __DIR__ . '/../../Application/Services/Dto/Commands/DeleteUserCommand.php';
require_once __DIR__ . '/../../Application/Services/Dto/Commands/LoginCommand.php';

$dsn = 'mysql:host=localhost;dbname=gestion_usuarios_db;charset=utf8mb4';
$username = 'root'; 
$password = '';     

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}


$mapper = new UserPersistenceMapper();
$userRepository = new UserRepositoryMySQL($pdo, $mapper); 
$notificationAdapter = new PhpMailerAdapter();


$createUserService = new CreateUserService($userRepository, $userRepository, $notificationAdapter);
$updateUserService = new UpdateUserService($userRepository, $userRepository, $userRepository);
$deleteUserService = new DeleteUserService($userRepository, $userRepository);
$getUserByIdService = new GetUserByIdService($userRepository);
$getAllUsersService = new GetAllUsersService($userRepository);
$loginService = new LoginService($userRepository);
