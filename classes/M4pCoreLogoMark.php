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
 * Logo files of the theme stored under img/m4p_logo/: the sign shown on phones and in the low header, and the
 * light logo for dark backgrounds (footer).
 */
class M4pCoreLogoMark
{
    public const KEY = 'LOGO_MARK';
    public const REMOVE_KEY = 'LOGO_MARK_REMOVE';
    public const LIGHT_KEY = 'LOGO_LIGHT';
    public const LIGHT_REMOVE_KEY = 'LOGO_LIGHT_REMOVE';
    public const NEWSLETTER_KEY = 'NEWSLETTER_IMAGE';
    public const NEWSLETTER_REMOVE_KEY = 'NEWSLETTER_IMAGE_REMOVE';
    /** Setting that holds the file => the switch that removes it, per `store` of a file field. */
    public const STORES = [
        'logo_mark' => [self::KEY, self::REMOVE_KEY],
        'logo_light' => [self::LIGHT_KEY, self::LIGHT_REMOVE_KEY],
        'newsletter_image' => [self::NEWSLETTER_KEY, self::NEWSLETTER_REMOVE_KEY],
    ];
    /** Stored file name prefix per setting. */
    private const PREFIX = [self::KEY => 'mark', self::LIGHT_KEY => 'light', self::NEWSLETTER_KEY => 'newsletter'];
    private const DIR = 'm4p_logo/';
    private const TYPES = ['image/png' => 'png', 'image/webp' => 'webp', 'image/jpeg' => 'jpg'];
    private const MAX_BYTES = 1048576;

    /**
     * Stores an uploaded file or removes the current one when asked to.
     *
     * @param array<string, mixed>|null $upload one entry of $_FILES
     * @param bool $remove
     *
     * @return bool false when the upload is not an image the field accepts
     */
    public static function save(?array $upload, bool $remove, string $key = self::KEY): bool
    {
        M4pCoreSettings::set(self::removeKey($key), 0);

        if ($upload !== null && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            return self::store($upload, $key);
        }

        if ($remove) {
            self::delete((string) M4pCoreSettings::get($key, ''));
            M4pCoreSettings::set($key, '');
        }

        return true;
    }

    public static function url(string $key = self::KEY): string
    {
        $file = (string) M4pCoreSettings::get($key, '');
        if ($file === '' || !is_file(_PS_IMG_DIR_ . self::DIR . $file)) {
            return '';
        }

        return _PS_IMG_ . self::DIR . $file;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    public static function size(string $key): ?array
    {
        $file = (string) M4pCoreSettings::get($key, '');
        $size = $file !== '' && is_file(_PS_IMG_DIR_ . self::DIR . $file) ? @getimagesize(_PS_IMG_DIR_ . self::DIR . $file) : false;

        return $size ? ['width' => (int) $size[0], 'height' => (int) $size[1]] : null;
    }

    /**
     * @param array<string, mixed> $upload
     */
    private static function store(array $upload, string $key): bool
    {
        $tmp = (string) ($upload['tmp_name'] ?? '');
        if ((int) $upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || filesize($tmp) > self::MAX_BYTES) {
            return false;
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset(self::TYPES[$mime]) || @getimagesize($tmp) === false) {
            return false;
        }

        $dir = _PS_IMG_DIR_ . self::DIR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }
        if (!is_file($dir . 'index.php')) {
            copy(_PS_IMG_DIR_ . 'index.php', $dir . 'index.php');
        }

        $file = (self::PREFIX[$key] ?? 'logo') . '-' . substr(sha1_file($tmp), 0, 12) . '.' . self::TYPES[$mime];
        if (!move_uploaded_file($tmp, $dir . $file)) {
            return false;
        }

        $previous = (string) M4pCoreSettings::get($key, '');
        M4pCoreSettings::set($key, $file);
        if ($previous !== $file) {
            self::delete($previous);
        }

        return true;
    }

    private static function removeKey(string $key): string
    {
        foreach (self::STORES as [$fileKey, $removeKey]) {
            if ($fileKey === $key) {
                return $removeKey;
            }
        }

        return self::REMOVE_KEY;
    }

    private static function delete(string $file): void
    {
        if ($file !== '' && basename($file) === $file && is_file(_PS_IMG_DIR_ . self::DIR . $file)) {
            unlink(_PS_IMG_DIR_ . self::DIR . $file);
        }
    }
}
