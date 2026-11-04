<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Retratos privados: validação de conteúdo, orientação e remoção de metadados. */
class ImagemPerfil
{
    public const REGRA = 'file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096';

    public function guardar(UploadedFile $foto, string $pasta): string
    {
        $info = getimagesize($foto->getRealPath());
        abort_unless($info && $info[0] * $info[1] <= 12000000, 422, 'A imagem deve ter no máximo 12 milhões de pixels.');
        $origem = imagecreatefromstring($foto->get());
        abort_unless($origem, 422, 'Imagem inválida.');
        if ($info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($foto->getRealPath());
            $orientacao = (int) ($exif['Orientation'] ?? 1);
            if (in_array($orientacao, [2, 4, 5, 7], true)) imageflip($origem, IMG_FLIP_HORIZONTAL);
            $angulo = [3=>180, 4=>180, 5=>90, 6=>-90, 7=>-90, 8=>90][$orientacao] ?? 0;
            if ($angulo) {
                $rotacionada = imagerotate($origem, $angulo, 0);
                imagedestroy($origem);
                $origem = $rotacionada;
            }
        }
        $destino = imagecreatetruecolor(384, 384);
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
        $lado = min(imagesx($origem), imagesy($origem));
        imagecopyresampled($destino, $origem, 0, 0, (int)((imagesx($origem)-$lado)/2), (int)((imagesy($origem)-$lado)/2), 384, 384, $lado, $lado);
        ob_start(); imagewebp($destino, null, 85); $bytes = ob_get_clean();
        imagedestroy($origem); imagedestroy($destino);
        $caminho = $pasta.'/'.Str::uuid().'.webp';
        abort_unless(Storage::disk('local')->put($caminho, $bytes), 503, 'Não foi possível salvar a imagem.');
        return $caminho;
    }
}
