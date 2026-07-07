<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entorno extends Model
{
    protected $table = 'entorno';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'formulas',
        'nombreListaPrecio',
        'listaPreciosPdf',
        'carpeta',
        'logo',
        'fondo',
        'direLabo',
        'teleLabo',
        'emailLabo',
        'colorInforme',
        'texto1footerIzq',
        'texto2footerIzq',
        'texto1footerCentro',
        'texto2footerCentro',
        'texto1footerDer',
        'texto2footerDer',
        'firmaIzq',
        'firmaCentro',
        'firmaDer',
        'ctaEnvioMail',
        'passEnvioMail',
        'fromMail',
        'nombrePieMail',
        'direccionPieMail',
        'telefonoPieMail',
        'emailPieMail',
    ];
}
