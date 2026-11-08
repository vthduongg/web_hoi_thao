<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Gửi email qua SMTP (PHPMailer). Cấu hình trong .env: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM.
 *
 * Khi APP_ENV=testing, thư KHÔNG được gửi đi mà được lưu vào $sent để test đọc.
 */
final class Mailer
{
    /** @var list<array{to: string, subject: string, body: string}> thư đã "gửi" khi chạy test */
    public array $sent = [];

    private function __construct(private readonly bool $fake)
    {
    }

    public static function fromEnv(string $environment): self
    {
        return new self($environment === 'testing');
    }

    public static function fake(): self
    {
        return new self(true);
    }

    /** @throws \PHPMailer\PHPMailer\Exception khi không gửi được */
    public function send(string $to, string $subject, string $htmlBody): void
    {
        if ($this->fake) {
            $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $htmlBody];

            return;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = Env::get('SMTP_HOST');
        $mail->Port = (int) Env::get('SMTP_PORT', '587');
        $mail->SMTPAuth = Env::get('SMTP_USER') !== '';
        $mail->Username = Env::get('SMTP_USER');
        $mail->Password = Env::get('SMTP_PASS');
        $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(Env::get('SMTP_FROM'));
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));
        $mail->send();
    }
}
