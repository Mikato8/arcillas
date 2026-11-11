<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Logger;

/**
 * Envío de correo con dos modos:
 *   - smtp: cliente SMTP mínimo (SSL/STARTTLS + AUTH LOGIN), sin dependencias.
 *   - log:  escribe el correo en storage/logs/mail.log (desarrollo).
 *
 * Los leads SIEMPRE se guardan en MySQL; si el correo falla, la operación
 * del usuario no se pierde y el error queda en el log.
 */
final class Mailer
{
    /**
     * @param array<int,string> $replyTo [email, nombre]
     */
    public static function enviar(string $to, string $asunto, string $html, ?string $replyToEmail = null, ?string $replyToNombre = null): bool
    {
        $driver = strtolower((string) Env::get('MAIL_DRIVER', 'log'));
        $from = (string) Env::get('SMTP_FROM', 'contacto@arcillas.mx');
        $fromName = (string) Env::get('SMTP_FROM_NAME', 'Arcillas MX');

        if ($driver !== 'smtp') {
            return self::registrarEnLog($to, $asunto, $html, $from, $replyToEmail);
        }

        $host = (string) Env::get('SMTP_HOST', 'smtp.hostinger.com');
        $port = (int) Env::get('SMTP_PORT', 465);
        $secure = strtolower((string) Env::get('SMTP_SECURE', 'ssl'));
        $user = (string) Env::get('SMTP_USER', '');
        $pass = (string) Env::get('SMTP_PASS', '');
        $fromNameEnc = self::encodeHeader($fromName);

        try {
            $remote = $secure === 'ssl' ? 'ssl://' . $host . ':' . $port : 'tcp://' . $host . ':' . $port;
            $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
            if (!$socket) {
                throw new \RuntimeException('No se pudo abrir el socket SMTP: ' . $errstr . ' (' . $errno . ')');
            }
            stream_set_timeout($socket, 15);

            self::esperar($socket, [220]);
            self::comando($socket, 'EHLO arcillas.mx', [250]);

            if ($secure === 'tls' || $secure === 'starttls') {
                self::comando($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo iniciar TLS.');
                }
                self::comando($socket, 'EHLO arcillas.mx', [250]);
            }

            if ($user !== '') {
                self::comando($socket, 'AUTH LOGIN', [334]);
                self::comando($socket, base64_encode($user), [334]);
                self::comando($socket, base64_encode($pass), [235]);
            }

            self::comando($socket, 'MAIL FROM:<' . $from . '>', [250]);
            self::comando($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::comando($socket, 'DATA', [354]);

            $frontera = '=_arcillas_' . bin2hex(random_bytes(8));
            $texto = self::textoPlano($html);

            $headers = [
                'Date: ' . date('r'),
                'From: ' . $fromNameEnc . ' <' . $from . '>',
                'To: <' . $to . '>',
                'Subject: ' . self::encodeHeader($asunto),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@arcillas.mx>',
                'X-Mailer: ArcillasMX/1.0',
                'Auto-Submitted: auto-generated',
                'X-Auto-Response-Suppress: All',
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $frontera . '"',
            ];
            if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: ' . ($replyToNombre !== null ? self::encodeHeader($replyToNombre) . ' ' : '') . '<' . $replyToEmail . '>';
            }

            $cuerpo = '--' . $frontera . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($texto), 76, "\r\n")
                . '--' . $frontera . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html), 76, "\r\n")
                . '--' . $frontera . "--\r\n";

            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $cuerpo . "\r\n.\r\n");
            self::esperar($socket, [250]);
            self::comando($socket, 'QUIT', [221], false);
            fclose($socket);
            Logger::info('SMTP enviado', ['to' => $to, 'asunto' => $asunto]);
            return true;
        } catch (\Throwable $e) {
            Logger::error('SMTP: ' . $e->getMessage(), ['to' => $to, 'asunto' => $asunto]);
            return false;
        }
    }

    private static function registrarEnLog(string $to, string $asunto, string $html, string $from, ?string $replyToEmail): bool
    {
        $dir = APP_STORAGE . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $registro = str_repeat('─', 70) . "\n"
            . 'Fecha: ' . date('c') . "\n"
            . 'De: ' . $from . "\n"
            . 'Para: ' . $to . "\n"
            . 'Responder a: ' . ($replyToEmail ?? '—') . "\n"
            . 'Asunto: ' . $asunto . "\n\n"
            . strip_tags(str_replace(['<br>', '</p>'], "\n", $html)) . "\n";
        @file_put_contents($dir . '/mail.log', $registro, FILE_APPEND | LOCK_EX);
        return true;
    }

    /** @param resource $socket */
    private static function comando($socket, string $linea, array $esperados, bool $leer = true): void
    {
        fwrite($socket, $linea . "\r\n");
        if ($leer) {
            self::esperar($socket, $esperados);
        }
    }

    /** @param resource $socket @param array<int,int> $esperados */
    private static function esperar($socket, array $esperados): void
    {
        $respuesta = '';
        while (($linea = fgets($socket, 515)) !== false) {
            $respuesta .= $linea;
            if (strlen($linea) < 4 || $linea[3] === ' ') {
                break;
            }
        }
        $codigo = (int) substr(trim($respuesta), 0, 3);
        if (!in_array($codigo, $esperados, true)) {
            throw new \RuntimeException('Respuesta SMTP inesperada: ' . trim($respuesta));
        }
    }

    /** Convierte el HTML del correo en una alternativa de texto plano. */
    private static function textoPlano(string $html): string
    {
        $texto = preg_replace('#<(br|/p|/tr|/h[1-6])[^>]*>#i', "\n", $html) ?? $html;
        $texto = str_replace(['</td>', '</th>'], ' · ', $texto);
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace("/[ \t]+/", ' ', $texto) ?? $texto;
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;
        return trim($texto);
    }

    private static function encodeHeader(string $texto): string
    {
        return '=?UTF-8?B?' . base64_encode($texto) . '?=';
    }

    /**
     * Plantilla HTML sobria y compatible con clientes de correo.
     *
     * @param array<string,string> $filas
     */
    public static function plantilla(string $titulo, string $intro, array $filas, string $ctaTexto = '', string $ctaUrl = ''): string
    {
        $filasHtml = '';
        foreach ($filas as $etiqueta => $valor) {
            if ($valor === '') {
                continue;
            }
            $filasHtml .= '<tr><td style="padding:6px 0;color:#6b6257;font-size:13px;width:38%;vertical-align:top">' . htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:6px 0;color:#2c2823;font-size:14px">' . nl2br(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')) . '</td></tr>';
        }
        $cta = $ctaTexto !== '' ? '<p style="margin:24px 0 0"><a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#556B2F;color:#fff;padding:11px 20px;border-radius:8px;text-decoration:none;font-size:14px">' . htmlspecialchars($ctaTexto, ENT_QUOTES, 'UTF-8') . '</a></p>' : '';

        return '<!doctype html><html lang="es"><head><meta charset="utf-8"></head>'
            . '<body style="margin:0;background:#f7f5f0;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f5f0;padding:24px 12px"><tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e7e0d4">'
            . '<tr><td style="background:#3d3228;padding:20px 26px;color:#f7f5f0;font-size:15px;letter-spacing:.08em;text-transform:uppercase">ARCILLAS MX</td></tr>'
            . '<tr><td style="padding:26px">'
            . '<h1 style="margin:0 0 8px;font-size:21px;color:#2c2823">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p style="margin:0 0 18px;color:#5d554b;font-size:14px;line-height:1.6">' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $filasHtml . '</table>'
            . $cta
            . '</td></tr>'
            . '<tr><td style="padding:16px 26px;background:#f7f5f0;color:#8b8276;font-size:12px">'
            . 'ARCILLAS PROTEÍNAS Y DESARROLLOS GARCÍA · RFC APD200702M63 · arcillas.mx'
            . '</td></tr></table></td></tr></table></body></html>';
    }
}
