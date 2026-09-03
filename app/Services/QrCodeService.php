<?php

namespace App\Services;

/**
 * Standalone Pure PHP QR Code Generator Service for DomPDF & Web
 * Generates crisp base64 Data URIs without external dependencies.
 */
class QrCodeService
{
    /**
     * Generate Base64 Data URI for a QR Code
     *
     * @param string $text
     * @param int $moduleSize Pixel size of each QR module
     * @param int $margin Quiet zone margin in modules
     * @return string data:image/png;base64,...
     */
    public static function generatePngBase64(string $text, int $moduleSize = 6, int $margin = 2): string
    {
        $matrix = self::encodeToMatrix($text);
        $matrixSize = count($matrix);
        $imgSize = ($matrixSize + ($margin * 2)) * $moduleSize;

        $img = imagecreatetruecolor($imgSize, $imgSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 15, 23, 42); // Slate-900

        imagefill($img, 0, 0, $white);

        for ($r = 0; $r < $matrixSize; $r++) {
            for ($c = 0; $c < $matrixSize; $c++) {
                if ($matrix[$r][$c]) {
                    $x1 = ($c + $margin) * $moduleSize;
                    $y1 = ($r + $margin) * $moduleSize;
                    $x2 = $x1 + $moduleSize - 1;
                    $y2 = $y1 + $moduleSize - 1;
                    imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    private static function encodeToMatrix(string $text): array
    {
        return (new QrMatrixEncoder())->encode($text);
    }
}

class QrMatrixEncoder
{
    private array $matrix = [];
    private int $size = 25;
    private int $version = 2;

    public function encode(string $data): array
    {
        $len = strlen($data);
        if ($len <= 14) {
            $this->version = 1;
            $this->size = 21;
        } elseif ($len <= 26) {
            $this->version = 2;
            $this->size = 25;
        } elseif ($len <= 42) {
            $this->version = 3;
            $this->size = 29;
        } else {
            $this->version = 4;
            $this->size = 33;
        }

        $this->matrix = array_fill(0, $this->size, array_fill(0, $this->size, null));

        $this->addFinderPattern(0, 0);
        $this->addFinderPattern($this->size - 7, 0);
        $this->addFinderPattern(0, $this->size - 7);

        $this->addSeparators();

        if ($this->version >= 2) {
            $alignPos = [
                2 => [6, 18],
                3 => [6, 22],
                4 => [6, 26],
            ];
            $pos = $alignPos[$this->version];
            foreach ($pos as $r) {
                foreach ($pos as $c) {
                    if ($this->matrix[$r][$c] === null) {
                        $this->addAlignmentPattern($r - 2, $c - 2);
                    }
                }
            }
        }

        for ($i = 8; $i < $this->size - 8; $i++) {
            $val = ($i % 2 === 0);
            if ($this->matrix[6][$i] === null) $this->matrix[6][$i] = $val;
            if ($this->matrix[$i][6] === null) $this->matrix[$i][6] = $val;
        }

        $this->matrix[4 * $this->version + 9][8] = true;

        $bitStream = $this->createBitStream($data);
        $this->placeDataBits($bitStream);

        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->matrix[$r][$c] === null) {
                    $this->matrix[$r][$c] = false;
                }
            }
        }

        return $this->matrix;
    }

    private function addFinderPattern(int $row, int $col): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                if ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                    $this->matrix[$row + $r][$col + $c] = true;
                } else {
                    $this->matrix[$row + $r][$col + $c] = false;
                }
            }
        }
    }

    private function addSeparators(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->setIfValid(7, $i, false);
            $this->setIfValid($i, 7, false);
            $this->setIfValid($this->size - 8, $i, false);
            $this->setIfValid($this->size - 1 - $i, 7, false);
            $this->setIfValid(7, $this->size - 1 - $i, false);
            $this->setIfValid($i, $this->size - 8, false);
        }
    }

    private function addAlignmentPattern(int $row, int $col): void
    {
        for ($r = 0; $r < 5; $r++) {
            for ($c = 0; $c < 5; $c++) {
                if ($r === 0 || $r === 4 || $c === 0 || $c === 4 || ($r === 2 && $c === 2)) {
                    $this->matrix[$row + $r][$col + $c] = true;
                } else {
                    $this->matrix[$row + $r][$col + $c] = false;
                }
            }
        }
    }

    private function setIfValid(int $r, int $c, bool $val): void
    {
        if ($r >= 0 && $r < $this->size && $c >= 0 && $c < $this->size) {
            $this->matrix[$r][$c] = $val;
        }
    }

    private function createBitStream(string $data): array
    {
        $bits = [0, 1, 0, 0];
        $len = strlen($data);
        $charCountBits = ($this->version <= 9) ? 8 : 16;
        for ($i = $charCountBits - 1; $i >= 0; $i--) {
            $bits[] = ($len >> $i) & 1;
        }

        for ($i = 0; $i < $len; $i++) {
            $byte = ord($data[$i]);
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($byte >> $b) & 1;
            }
        }

        $totalDataBits = $this->getDataCapacity() * 8;
        $terminatorLen = min(4, $totalDataBits - count($bits));
        for ($i = 0; $i < $terminatorLen; $i++) {
            $bits[] = 0;
        }

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $padBytes = [0b11101100, 0b00010001];
        $padIdx = 0;
        while (count($bits) < $totalDataBits) {
            $p = $padBytes[$padIdx % 2];
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($p >> $b) & 1;
            }
            $padIdx++;
        }

        $dataBytes = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $val = 0;
            for ($b = 0; $b < 8; $b++) {
                $val = ($val << 1) | $bits[$i + $b];
            }
            $dataBytes[] = $val;
        }

        $eccBytes = $this->calculateEcc($dataBytes);
        $allBytes = array_merge($dataBytes, $eccBytes);

        $finalBits = [];
        foreach ($allBytes as $byte) {
            for ($b = 7; $b >= 0; $b--) {
                $finalBits[] = ($byte >> $b) & 1;
            }
        }

        return $finalBits;
    }

    private function getDataCapacity(): int
    {
        $capacities = [1 => 16, 2 => 28, 3 => 44, 4 => 64];
        return $capacities[$this->version] ?? 28;
    }

    private function calculateEcc(array $data): array
    {
        $eccCount = [1 => 10, 2 => 16, 3 => 26, 4 => 36][$this->version] ?? 16;
        $poly = $this->getGeneratorPolynomial($eccCount);
        $msg = array_merge($data, array_fill(0, $eccCount, 0));

        for ($i = 0; $i < count($data); $i++) {
            $coef = $msg[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($poly); $j++) {
                    $msg[$i + $j] ^= $this->gfMul($poly[$j], $coef);
                }
            }
        }

        return array_slice($msg, count($data));
    }

    private function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) return 0;
        $gfLog = $this->getGfLog();
        $gfExp = $this->getGfExp();
        return $gfExp[($gfLog[$x] + $gfLog[$y]) % 255];
    }

    private function getGeneratorPolynomial(int $degree): array
    {
        $poly = [1];
        $gfExp = $this->getGfExp();
        for ($i = 0; $i < $degree; $i++) {
            $factor = [1, $gfExp[$i]];
            $newPoly = array_fill(0, count($poly) + 1, 0);
            for ($j = 0; $j < count($poly); $j++) {
                $newPoly[$j] ^= $this->gfMul($poly[$j], $factor[0]);
                $newPoly[$j + 1] ^= $this->gfMul($poly[$j], $factor[1]);
            }
            $poly = $newPoly;
        }
        return $poly;
    }

    private array $gfExpCache = [];
    private array $gfLogCache = [];

    private function initGfTables(): void
    {
        if (!empty($this->gfExpCache)) return;
        $this->gfExpCache = array_fill(0, 512, 0);
        $this->gfLogCache = array_fill(0, 256, 0);
        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            $this->gfExpCache[$i] = $val;
            $this->gfExpCache[$i + 255] = $val;
            $this->gfLogCache[$val] = $i;
            $val <<= 1;
            if ($val & 0x100) {
                $val ^= 0x11d;
            }
        }
    }

    private function getGfExp(): array
    {
        $this->initGfTables();
        return $this->gfExpCache;
    }

    private function getGfLog(): array
    {
        $this->initGfTables();
        return $this->gfLogCache;
    }

    private function placeDataBits(array $bits): void
    {
        $bitIdx = 0;
        $totalBits = count($bits);
        $up = true;

        for ($i = 0; $i < 9; $i++) {
            $this->setIfValid(8, $i, true);
            $this->setIfValid($i, 8, true);
            $this->setIfValid(8, $this->size - 1 - $i, true);
            $this->setIfValid($this->size - 1 - $i, 8, true);
        }

        for ($col = $this->size - 1; $col > 0; $col -= 2) {
            if ($col === 6) $col--;

            $rows = $up ? range($this->size - 1, 0, -1) : range(0, $this->size - 1);
            foreach ($rows as $r) {
                foreach ([$col, $col - 1] as $c) {
                    if ($this->matrix[$r][$c] === null) {
                        $bit = ($bitIdx < $totalBits) ? (bool)$bits[$bitIdx++] : false;
                        $mask = (($r + $c) % 2 === 0);
                        $this->matrix[$r][$c] = $bit ^ $mask;
                    }
                }
            }
            $up = !$up;
        }

        $formatBits = [1,0,1,0,1,0,0,0,0,0,1,0,0,1,0];
        $fIdx = 0;
        $coordsTopLeft = [[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
        foreach ($coordsTopLeft as $coord) {
            $this->matrix[$coord[0]][$coord[1]] = (bool)$formatBits[$fIdx++];
        }

        $fIdx = 0;
        for ($i = 0; $i < 7; $i++) {
            $this->matrix[$this->size - 1 - $i][8] = (bool)$formatBits[$fIdx++];
        }
        for ($i = 0; $i < 8; $i++) {
            $this->matrix[8][$this->size - 8 + $i] = (bool)$formatBits[$fIdx++];
        }
    }
}
