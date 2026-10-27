<?php

namespace App\Exceptions\Cv;

use RuntimeException;

class CargaMasivaNoEncontradaException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No se encontro la vista previa de la carga.');
    }
}
