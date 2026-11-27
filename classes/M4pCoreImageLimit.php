<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Keeps every file of a product image under the size limit: the original becomes a real JPEG no larger than the
 * longest side set here, and each file above the limit is encoded again at a lower quality until it fits.
 */
class M4pCoreImageLimit
{
    public const MAX_KB = 'IMAGE_MAX_KB';
    public const MAX_PX = 'IMAGE_MAX_PX';

    public const DEFAULT_MAX_KB = 200;
    public const DEFAULT_MAX_PX = 1440;

    private const ORIGINAL_QUALITY = 85;
    private const MIN_QUALITY = 40;
    private const QUALITY_STEP = 5;

    public static function maxBytes(): int
    {
        return max(0, (int) M4pCoreSettings::get(self::MAX_KB, self::DEFAULT_MAX_KB)) * 1000;
    }

    /**
     * @return string[] files of the image still above the limit at the lowest quality
     */
    public static function apply(int $idImage): array
    {
        $limit = self::maxBytes();
        if ($idImage <= 0 || $limit === 0) {
            return [];
        }

        $base = _PS_PRODUCT_IMG_DIR_ . Image::getImgFolderStatic($idImage) . $idImage;
        $over = [];
        if (is_file($base . '.jpg') && !self::fitOriginal($base . '.jpg', $limit)) {
            $over[] = $base . '.jpg';
        }
        $thumbnails = array_merge(glob($base . '-*.jpg') ?: [], glob($base . '-*.webp') ?: []);
        foreach ($thumbnails as $file) {
            if (filesize($file) > $limit && !self::fitThumbnail($file, $limit)) {
                $over[] = $file;
            }
        }

        return $over;
    }

    private static function fitOriginal(string $file, int $limit): bool
    {
        $info = @getimagesize($file);
        if (!$info) {
            return false;
        }
        $maxSide = max(1, (int) M4pCoreSettings::get(self::MAX_PX, self::DEFAULT_MAX_PX));
        [$width, $height] = $info;
        $isJpeg = $info[2] === IMAGETYPE_JPEG;
        if ($isJpeg && max($width, $height) <= $maxSide && filesize($file) <= $limit) {
            return true;
        }

        $source = @imagecreatefromstring((string) file_get_contents($file));
        if (!$source) {
            return false;
        }
        $scale = min(1, $maxSide / max($width, $height));
        $image = self::flatten($source, (int) round($width * $scale), (int) round($height * $scale));

        return self::write($file, $image, 'jpg', self::ORIGINAL_QUALITY, $limit);
    }

    private static function fitThumbnail(string $file, int $limit): bool
    {
        $format = strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'webp' ? 'webp' : 'jpg';
        $image = $format === 'webp' ? @imagecreatefromwebp($file) : @imagecreatefromstring((string) file_get_contents($file));
        if (!$image) {
            return false;
        }
        $quality = (int) Configuration::get($format === 'webp' ? 'PS_WEBP_QUALITY' : 'PS_JPEG_QUALITY') ?: 90;

        return self::write($file, $image, $format, $quality - self::QUALITY_STEP, $limit);
    }

    /**
     * The image at the given size on a white ground, so transparent PNG edges do not turn black in a JPEG.
     *
     * @param resource|GdImage $source
     *
     * @return resource|GdImage
     */
    private static function flatten($source, int $width, int $height)
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 255, 255, 255));
        imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

        return $image;
    }

    /**
     * Writes the highest quality from $quality down that fits; at the lowest quality the file is written anyway.
     *
     * @param resource|GdImage $image
     */
    private static function write(string $file, $image, string $format, int $quality, int $limit): bool
    {
        $quality = min(100, max(self::MIN_QUALITY, $quality));
        do {
            $data = self::encode($image, $format, $quality);
            $fits = strlen($data) <= $limit;
            $quality -= self::QUALITY_STEP;
        } while (!$fits && $quality >= self::MIN_QUALITY);

        $temporary = $file . '.m4p-tmp';
        if (file_put_contents($temporary, $data) === false || !rename($temporary, $file)) {
            @unlink($temporary);

            return false;
        }

        return $fits;
    }

    /**
     * @param resource|GdImage $image
     */
    private static function encode($image, string $format, int $quality): string
    {
        ob_start();
        if ($format === 'webp') {
            imagewebp($image, null, $quality);
        } else {
            imageinterlace($image, true);
            imagejpeg($image, null, $quality);
        }

        return (string) ob_get_clean();
    }
}
