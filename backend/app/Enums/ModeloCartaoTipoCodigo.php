<?php

namespace App\Enums;

enum ModeloCartaoTipoCodigo: string
{
    case WITHOUT_CODE = 'sem_codigo';
    case QR_CODE = 'qr_code';
    case BARCODE = 'codigo_barras';
    case OCR_TEXT = 'ocr_texto';
}
