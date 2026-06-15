<?php

namespace App\Support\Export;

/**
 * Escritor de arquivos ZIP em PHP puro, usando o método "store" (sem
 * compressão). Existe porque a extensão `zip`/`ZipArchive` não está disponível
 * em todos os ambientes, mas o formato XLSX (um ZIP de XMLs) precisa ser gerado
 * de forma autossuficiente.
 *
 * O formato implementado segue a especificação APPNOTE.TXT (PKWARE): cada
 * entrada recebe um local file header seguido do conteúdo; ao final são
 * escritos o central directory e o end-of-central-directory record.
 */
class StoredZipWriter
{
    /** @var list<array{name: string, data: string, crc: int, offset: int}> */
    private array $entries = [];

    private string $buffer = '';

    // Data/hora DOS fixa (1980-01-01 00:00) para saída determinística.
    private const DOS_TIME = 0;

    private const DOS_DATE = 0x21;

    public function add(string $name, string $data): void
    {
        $crc = crc32($data);
        $length = strlen($data);
        $offset = strlen($this->buffer);

        $this->buffer .= pack('V', 0x04034B50) // assinatura local header
            .pack('v', 20)                      // versão necessária
            .pack('v', 0)                       // flags
            .pack('v', 0)                       // método: store
            .pack('v', self::DOS_TIME)
            .pack('v', self::DOS_DATE)
            .pack('V', $crc)
            .pack('V', $length)                 // tamanho comprimido
            .pack('V', $length)                 // tamanho original
            .pack('v', strlen($name))
            .pack('v', 0)                        // extra field
            .$name
            .$data;

        $this->entries[] = ['name' => $name, 'data' => $data, 'crc' => $crc, 'offset' => $offset];
    }

    public function finish(): string
    {
        $central = '';

        foreach ($this->entries as $entry) {
            $length = strlen($entry['data']);

            $central .= pack('V', 0x02014B50) // assinatura central directory
                .pack('v', 20)                 // versão usada
                .pack('v', 20)                 // versão necessária
                .pack('v', 0)                  // flags
                .pack('v', 0)                  // método: store
                .pack('v', self::DOS_TIME)
                .pack('v', self::DOS_DATE)
                .pack('V', $entry['crc'])
                .pack('V', $length)
                .pack('V', $length)
                .pack('v', strlen($entry['name']))
                .pack('v', 0)                  // extra
                .pack('v', 0)                  // comentário
                .pack('v', 0)                  // disco inicial
                .pack('v', 0)                  // atributos internos
                .pack('V', 0)                  // atributos externos
                .pack('V', $entry['offset'])
                .$entry['name'];
        }

        $eocd = pack('V', 0x06054B50)        // assinatura EOCD
            .pack('v', 0)                     // disco atual
            .pack('v', 0)                     // disco do central directory
            .pack('v', count($this->entries)) // entradas neste disco
            .pack('v', count($this->entries)) // entradas totais
            .pack('V', strlen($central))      // tamanho do central directory
            .pack('V', strlen($this->buffer)) // offset do central directory
            .pack('v', 0);                    // comentário

        return $this->buffer.$central.$eocd;
    }
}
