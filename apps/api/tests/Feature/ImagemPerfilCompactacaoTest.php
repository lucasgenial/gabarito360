<?php
namespace Tests\Feature;

use App\Services\ImagemPerfil;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagemPerfilCompactacaoTest extends TestCase
{
    public function test_textura_detalhada_respeita_orcamento_e_saida_privada(): void
    {
        Storage::fake('local');$i=imagecreatetruecolor(512,512);
        for($y=0;$y<512;$y++)for($x=0;$x<512;$x++)imagesetpixel($i,$x,$y,(($x*73856093)^($y*19349663))&0xffffff);
        ob_start();imagepng($i);$b=ob_get_clean();imagedestroy($i);
        $p=app(ImagemPerfil::class)->guardar(UploadedFile::fake()->createWithContent('ruido.png',$b),'usuarios/fotos');
        $final=Storage::disk('local')->get($p);$info=getimagesizefromstring($final);
        $this->assertSame([384,384],array_slice($info,0,2));$this->assertSame('image/webp',$info['mime']);
        $this->assertLessThanOrEqual(ImagemPerfil::MAX_BYTES,strlen($final));$this->assertStringStartsWith('usuarios/fotos/',$p);
        $this->assertCount(1,Storage::disk('local')->allFiles());
    }

    public function test_foto_jpeg_de_celular_e_orientada_antes_do_recorte(): void
    {
        if(!function_exists('exif_read_data'))$this->markTestSkipped('Extensão EXIF indisponível neste runtime.');
        Storage::fake('local');$i=imagecreatetruecolor(300,200);imagefilledrectangle($i,0,0,149,199,0xff0000);imagefilledrectangle($i,150,0,299,199,0x0000ff);
        ob_start();imagejpeg($i,null,95);$b=ob_get_clean();imagedestroy($i);
        $exif="Exif\0\0II\x2a\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
        $b=substr($b,0,2)."\xff\xe1".pack('n',strlen($exif)+2).$exif.substr($b,2);
        $p=app(ImagemPerfil::class)->guardar(UploadedFile::fake()->createWithContent('camera.jpg',$b),'usuarios/fotos');
        $final=Storage::disk('local')->get($p);$i=imagecreatefromstring($final);
        $topo=imagecolorsforindex($i,imagecolorat($i,192,40));$base=imagecolorsforindex($i,imagecolorat($i,192,344));imagedestroy($i);
        $this->assertGreaterThan(200,$topo['red']);$this->assertLessThan(30,$topo['blue']);
        $this->assertGreaterThan(200,$base['blue']);$this->assertLessThan(30,$base['red']);$this->assertStringNotContainsString('Exif',$final);
    }
}
