<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Pengirim email transaksional via SMTP (PHPMailer).
 *
 * Konfigurasi per-tenant dari tenants.settings.notifications.{mail_*},
 * fallback ke .env (MAIL_HOST/PORT/USERNAME/PASSWORD/FROM/FROM_NAME).
 */
class Mailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    public function __construct(array $cfg)
    {
        $this->host      = (string)($cfg['host'] ?? '');
        $this->port      = (int)($cfg['port'] ?? 587);
        $this->username  = (string)($cfg['username'] ?? '');
        $this->password  = (string)($cfg['password'] ?? '');
        $this->fromEmail = (string)($cfg['from'] ?? $this->username);
        $this->fromName  = (string)($cfg['from_name'] ?? 'Visi Tickets');
    }

    /**
     * Build dari settings tenant (+ fallback .env). Return null jika belum dikonfigurasi.
     * @param array<string,mixed>|null $tenantSettings
     */
    public static function fromConfig(?array $tenantSettings = null): ?self
    {
        $n = $tenantSettings['notifications'] ?? [];
        if (!is_array($n)) {
            $n = [];
        }

        $host     = trim((string)($n['mail_host'] ?? getenv('MAIL_HOST') ?: ''));
        $port     = (int)($n['mail_port'] ?? getenv('MAIL_PORT') ?: 587);
        $username = trim((string)($n['mail_username'] ?? getenv('MAIL_USERNAME') ?: ''));
        $password = (string)($n['mail_password'] ?? getenv('MAIL_PASSWORD') ?: '');
        $from     = trim((string)($n['mail_from'] ?? getenv('MAIL_FROM') ?: ''));
        $fromName = trim((string)($n['mail_from_name'] ?? getenv('MAIL_FROM_NAME') ?: 'Visi Tickets'));

        // Minimal perlu host + username + password untuk SMTP auth.
        if ($host === '' || $username === '' || $password === '') {
            return null;
        }
        if ($from === '') {
            $from = $username;
        }

        return new self([
            'host' => $host, 'port' => $port, 'username' => $username,
            'password' => $password, 'from' => $from, 'from_name' => $fromName,
        ]);
    }

    public static function isAvailable(): bool
    {
        return class_exists(PHPMailer::class);
    }

    /**
     * Kirim email HTML. Return ['ok'=>bool, 'error'=>string].
     * @return array{ok:bool,error:string}
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        if (!self::isAvailable()) {
            return ['ok' => false, 'error' => 'PHPMailer tidak tersedia (jalankan composer install)'];
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->Port       = $this->port;
            // 465 = SMTPS (implicit TLS), selain itu STARTTLS.
            $mail->SMTPSecure = $this->port === 465
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 20;

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody ?? strip_tags($htmlBody);

            $mail->send();
            return ['ok' => true, 'error' => ''];
        } catch (PHPMailerException $e) {
            return ['ok' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
