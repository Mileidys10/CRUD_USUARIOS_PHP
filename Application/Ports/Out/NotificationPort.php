<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/ValueObjects/UserEmail.php';

interface NotificationPort
{
    public function sendEmail(UserEmail $email, string $subject, string $body): void;
}
