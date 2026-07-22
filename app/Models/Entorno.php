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
        'headerInforme',
        'footerInforme',
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
        'e_AnchoPapel',
        'e_AnchoEtiq',
        'e_AltoEtiq',
        'e_CantCol',
        'e_GapX',
        'e_GapY',
        'e_MarginTop',
        'e_MarginBottom',
        'e_MarginLeft',
        'e_MarginRight',
        'e_FontLinea1',
        'e_FontLinea2',
        'e_FontLinea3',
        'e_FontLinea4',
        'e_MaxLargoLinea2',
        'e_MaxLargoLinea3',
        'e_Borde',
        'afipFormatoImpresion',
    ];
}
