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

    public function __construct(Empleado $empleado)
    {
        $this->empleado = $empleado;
    }

    public function build()
    {
        return $this->subject('Corrección de registro de CV')
            ->view('emails.cv_rechazado');
    }
}
