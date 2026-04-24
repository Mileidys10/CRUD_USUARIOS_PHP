<?php
declare(strict_types=1);
require_once __DIR__ . '/../Ports/In/CreateUserUseCase.php';
require_once __DIR__ . '/../Ports/Out/SaveUserPort.php';
require_once __DIR__ . '/../Ports/Out/GetUserByEmailPort.php';
require_once __DIR__ . '/Mappers/UserApplicationMapper.php';
require_once __DIR__ . '/../../Domain/Exceptions/UserAlreadyExistsException.php';
require_once __DIR__ . '/../../Domain/ValueObjects/UserEmail.php';


require_once __DIR__ . '/../Ports/Out/NotificationPort.php';

final class CreateUserService implements CreateUserUseCase
{
    private SaveUserPort $saveUserPort;
    private GetUserByEmailPort $getUserByEmailPort;
    private NotificationPort $notificationPort;

    public function __construct(
        SaveUserPort $saveUserPort,
        GetUserByEmailPort $getUserByEmailPort,
        NotificationPort $notificationPort
    ) {
        $this->saveUserPort = $saveUserPort;
        $this->getUserByEmailPort = $getUserByEmailPort;
        $this->notificationPort = $notificationPort;
    }

    public function execute(CreateUserCommand $command): UserModel
    {
        $email = new UserEmail($command->getEmail());
        $existingUser = $this->getUserByEmailPort->getByEmail($email);
        
        if ($existingUser !== null) {
            throw UserAlreadyExistsException::becauseEmailAlreadyExists($email->value());
        }

        $user = UserApplicationMapper::fromCreateCommandToModel($command);
        $savedUser = $this->saveUserPort->save($user);

      
        $this->notificationPort->sendEmail(
            $savedUser->email(),
            "Bienvenido a la plataforma",
            "Hola " . $savedUser->name()->value() . ", tu cuenta ha sido creada con éxito."
        );

        return $savedUser;
    }
}
