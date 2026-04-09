<?php

namespace App\Mail;

use App\Models\Cv\Empleado;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CvRechazadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Empleado $empleado;
    public string $motivo;

    public function __construct(Empleado $empleado, string $motivo = '')
    {
        $this->empleado = $empleado;
        $this->motivo = trim($motivo) !== ''
            ? trim($motivo)
            : (string) ($empleado->motivo_rechazo_cv ?? '');
    }

    public function build()
    {
        return $this->subject('Corrección de registro de CV')
            ->view('emails.cv_rechazado');
    }
}