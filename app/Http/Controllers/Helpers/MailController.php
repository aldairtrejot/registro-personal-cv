<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Throwable;

class MailController extends Controller
{
    protected ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function sendMail($data): bool
    {
        $this->lastError = null;

        $mail = new PHPMailer(true);

        try {
            $host = trim((string) env('MAIL_HOST', ''));
            $port = (int) env('MAIL_PORT', 587);
            $username = trim((string) env('MAIL_USERNAME', ''));
            $password = (string) env('MAIL_PASSWORD', '');
            $fromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
            $fromName = trim((string) env('MAIL_FROM_NAME', ''));
            $authType = strtoupper(trim((string) env('MAIL_AUTH_TYPE', 'LOGIN')));
            $encryptionRaw = strtolower(trim((string) env('MAIL_ENCRYPTION', 'tls')));
            $timeout = (int) env('MAIL_TIMEOUT', 30);

            $subject = trim((string) ($data['affair'] ?? ''));
            $toEmail = trim((string) ($data['mail'] ?? ''));
            $toName  = trim((string) ($data['name'] ?? '_RECIPIENT'));
            $htmlBody = (string) ($data['content'] ?? '');

            if ($host === '') {
                throw new \RuntimeException('MAIL_HOST no está configurado.');
            }

            if ($username === '') {
                throw new \RuntimeException('MAIL_USERNAME no está configurado.');
            }

            if ($password === '') {
                throw new \RuntimeException('MAIL_PASSWORD no está configurado.');
            }

            if ($fromAddress === '') {
                throw new \RuntimeException('MAIL_FROM_ADDRESS no está configurado.');
            }

            if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('El correo destino no es válido.');
            }

            if ($subject === '') {
                throw new \RuntimeException('El asunto del correo está vacío.');
            }

            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->AuthType = $authType;
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = $timeout;
            $mail->SMTPKeepAlive = false;
            $mail->SMTPAutoTLS = true;

            if ($encryptionRaw === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryptionRaw === 'tls' || $encryptionRaw === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
            }

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            if (filter_var(env('MAIL_SMTP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function ($str, $level) {
                    Log::debug('PHPMailer SMTP debug', [
                        'level' => $level,
                        'message' => $str,
                    ]);
                };
            }

            $mail->setFrom($fromAddress, $fromName !== '' ? $fromName : $fromAddress);
            $mail->Sender = $fromAddress;
            $mail->addAddress($toEmail, $toName);

            if (!empty($data['reply_to']) && filter_var($data['reply_to'], FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($data['reply_to']);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $htmlBody)));

            $mail->send();

            Log::info('Correo enviado correctamente', [
                'to' => $toEmail,
                'subject' => $subject,
                'host' => $host,
                'port' => $port,
                'auth_type' => $authType,
                'encryption' => $encryptionRaw,
                'from' => $fromAddress,
                'username' => $username,
            ]);

            return true;
        } catch (Exception|Throwable $e) {
            $this->lastError = $e->getMessage();

            Log::error('Error PHPMailer al enviar correo', [
                'to' => $data['mail'] ?? null,
                'subject' => $data['affair'] ?? null,
                'host' => env('MAIL_HOST'),
                'port' => env('MAIL_PORT'),
                'username' => env('MAIL_USERNAME'),
                'from' => env('MAIL_FROM_ADDRESS'),
                'auth_type' => env('MAIL_AUTH_TYPE', 'LOGIN'),
                'encryption' => env('MAIL_ENCRYPTION'),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}