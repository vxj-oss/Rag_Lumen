<?php

namespace App\Support;

use RuntimeException;

/**
 * Limpia la foto/escaneo de una firma: quita el fondo (papel amarillento o gris)
 * dejándolo transparente, conserva solo el trazo y recorta los márgenes para que
 * la firma quede ajustada y se apoye bien sobre la línea del documento.
 */
class SignatureCleaner
{
    /** Alto máximo en píxeles del resultado; las firmas escaneadas suelen ser enormes. */
    private const MAX_HEIGHT = 300;

    /** @return string contenido de un PNG con fondo transparente */
    public static function clean(string $binary): string
    {
        $src = @imagecreatefromstring($binary);

        if ($src === false) {
            throw new RuntimeException('No se pudo leer la imagen de la firma.');
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // Luminosidad del papel: percentil alto de todos los píxeles (ignora el trazo, que es oscuro).
        $paso = max(1, (int) floor(min($w, $h) / 120));
        $lums = [];
        for ($y = 0; $y < $h; $y += $paso) {
            for ($x = 0; $x < $w; $x += $paso) {
                $lums[] = self::luminosidad(imagecolorat($src, $x, $y));
            }
        }
        sort($lums);
        $papel = $lums[(int) (count($lums) * 0.85)] ?: 255;

        // Por encima de este nivel es papel (transparente); por debajo de $tinta, trazo pleno.
        $umbralPapel = $papel * 0.74;
        $tinta = $papel * 0.42;

        $out = imagecreatetruecolor($w, $h);
        imagesavealpha($out, true);
        imagealphablending($out, false);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));

        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $lum = self::luminosidad($rgb);

                if ($lum >= $umbralPapel) {
                    continue;
                }

                $opacidad = $lum <= $tinta ? 1.0 : ($umbralPapel - $lum) / ($umbralPapel - $tinta);

                // Trazo oscuro y uniforme (azul tinta muy oscuro) para que se lea bien impreso.
                $r = (int) ((($rgb >> 16) & 0xFF) * 0.35);
                $g = (int) ((($rgb >> 8) & 0xFF) * 0.35);
                $b = (int) (($rgb & 0xFF) * 0.35);
                $alfa = 127 - (int) round(127 * $opacidad);

                imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, $alfa));

                if ($opacidad > 0.25) {
                    $minX = min($minX, $x);
                    $maxX = max($maxX, $x);
                    $minY = min($minY, $y);
                    $maxY = max($maxY, $y);
                }
            }
        }

        imagedestroy($src);

        if ($maxX <= $minX || $maxY <= $minY) {
            // No se detectó trazo: devolver la imagen original convertida a PNG sin tocarla.
            ob_start();
            imagepng($out);
            imagedestroy($out);

            return (string) ob_get_clean();
        }

        $margen = (int) round(max($maxX - $minX, $maxY - $minY) * 0.02) + 2;
        $minX = max(0, $minX - $margen);
        $minY = max(0, $minY - $margen);
        $cw = min($w - $minX, $maxX - $minX + 1 + $margen * 2);
        $ch = min($h - $minY, $maxY - $minY + 1 + $margen * 2);

        $recorte = imagecreatetruecolor($cw, $ch);
        imagesavealpha($recorte, true);
        imagealphablending($recorte, false);
        imagefill($recorte, 0, 0, imagecolorallocatealpha($recorte, 0, 0, 0, 127));
        imagecopy($recorte, $out, 0, 0, $minX, $minY, $cw, $ch);
        imagedestroy($out);

        if ($ch > self::MAX_HEIGHT) {
            $nw = (int) round($cw * self::MAX_HEIGHT / $ch);
            $peq = imagescale($recorte, $nw, self::MAX_HEIGHT, IMG_BICUBIC);
            imagesavealpha($peq, true);
            imagedestroy($recorte);
            $recorte = $peq;
        }

        ob_start();
        imagepng($recorte, null, 6);
        imagedestroy($recorte);

        return (string) ob_get_clean();
    }

    private static function luminosidad(int $rgb): float
    {
        return 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
    }
}
