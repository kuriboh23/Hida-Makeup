<?php
/**
 * HIDA.MAKEUP - Product image resolver
 *
 * Product photos live in assets/images/products/. Drop a correctly named file
 * in there and it is picked up automatically everywhere - no database edit
 * needed. Until then, the URL stored in the products table is used.
 *
 * Naming, for the product with slug "rouge-a-levres-mat" and id 8:
 *
 *   rouge-a-levres-mat.jpg      -> main image
 *   rouge-a-levres-mat-1.jpg    -> 2nd gallery image
 *   rouge-a-levres-mat-2.png    -> 3rd gallery image
 *   (8.jpg, 8-1.jpg ... work the same way, using the product id)
 *
 * Accepted formats: JPG, PNG, WebP, GIF.
 */

require_once __DIR__ . '/config.php';

const PRODUCT_IMAGE_DIR          = 'assets/images/products';
const PRODUCT_IMAGE_MAX_BYTES    = 3145728; // 3 MB
const PRODUCT_IMAGE_MIN_SIDE     = 200;     // px
const PRODUCT_IMAGE_GALLERY_SLOTS = 6;

/**
 * Accepted mime type => canonical extension.
 */
function product_image_mimes(): array {
    return [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
}

/**
 * Absolute path of the product image folder.
 */
function product_image_path(): string {
    return dirname(__DIR__) . '/' . PRODUCT_IMAGE_DIR;
}

/**
 * Browser URL for a file inside the product image folder.
 */
function product_image_url_for(string $filename): string {
    return BASE_PATH . PRODUCT_IMAGE_DIR . '/' . str_replace('%2F', '/', rawurlencode($filename));
}

/**
 * Every usable image in the folder, keyed by filename without its extension.
 * Cached per request because a catalogue page asks for it many times.
 */
function product_image_files(): array {
    if (isset($GLOBALS['hida_product_image_files'])) {
        return $GLOBALS['hida_product_image_files'];
    }

    $files = [];
    $dir = product_image_path();

    if (is_dir($dir)) {
        $extensions = array_values(product_image_mimes());

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($extension, $extensions, true)) {
                $files[pathinfo($entry, PATHINFO_FILENAME)] = $entry;
            }
        }
    }

    $GLOBALS['hida_product_image_files'] = $files;

    return $files;
}

/**
 * Forget the cached folder listing (call after writing or deleting a file).
 */
function product_image_flush(): void {
    unset($GLOBALS['hida_product_image_files']);
}

/**
 * Safe base filename for a product: the slug when it is URL-safe, else the id.
 */
function product_image_key(string $slug, int $id): string {
    $slug = strtolower(trim($slug));

    if ($slug !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        return $slug;
    }

    return (string)$id;
}

/**
 * Find a local file for a product.
 *
 * @param int $slot 0 = main image, 1..N = gallery slots.
 */
function product_image_find(string $slug, int $id, int $slot = 0): ?string {
    $files = product_image_files();
    $suffix = $slot > 0 ? '-' . $slot : '';

    foreach ([product_image_key($slug, $id), (string)$id] as $base) {
        $candidate = $base . $suffix;

        if (isset($files[$candidate])) {
            return $files[$candidate];
        }
    }

    return null;
}

/**
 * Neutral stand-in shown when a product has neither a local photo nor a URL.
 * Inline SVG, so it never renders as a broken image and needs no request.
 */
function product_image_placeholder(): string {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 500">'
         . '<rect width="400" height="500" fill="#f5e7e4"/>'
         . '<circle cx="200" cy="225" r="46" fill="none" stroke="#c9a7a3" stroke-width="8"/>'
         . '<path d="M168 232l24 24 44-52" fill="none" stroke="#c9a7a3" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<text x="200" y="320" text-anchor="middle" font-family="sans-serif" font-size="22" fill="#a98b87">Photo à venir</text>'
         . '</svg>';

    return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}

/**
 * Main image URL for a product: local file first, then the stored URL.
 */
function product_image_url(?string $stored, string $slug, int $id): string {
    $local = product_image_find($slug, $id, 0);

    if ($local !== null) {
        return product_image_url_for($local);
    }

    $stored = trim((string)$stored);

    return $stored !== '' ? $stored : product_image_placeholder();
}

/**
 * Gallery image URLs for a product.
 *
 * Local files take over completely once any exist; otherwise the fallback is
 * the stored main image followed by the gallery_images JSON column.
 */
function product_gallery_urls(array $product): array {
    $slug = (string)($product['slug'] ?? '');
    $id   = (int)($product['id'] ?? 0);

    $urls = [];
    for ($slot = 0; $slot <= PRODUCT_IMAGE_GALLERY_SLOTS; $slot++) {
        $file = product_image_find($slug, $id, $slot);

        if ($file !== null) {
            $urls[] = product_image_url_for($file);
        }
    }

    if ($urls) {
        return $urls;
    }

    $urls[] = product_image_url($product['main_image'] ?? null, $slug, $id);

    $decoded = json_decode((string)($product['gallery_images'] ?? ''), true);
    if (is_array($decoded)) {
        foreach ($decoded as $url) {
            if (is_string($url) && $url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }
    }

    return $urls;
}

/**
 * Does this product already have its own uploaded photo?
 */
function product_has_local_image(string $slug, int $id): bool {
    return product_image_find($slug, $id, 0) !== null;
}

/**
 * Remove the local image of a product (all formats and gallery slots).
 *
 * @return int Number of files deleted.
 */
function product_image_delete(string $slug, int $id, ?int $slot = null): int {
    $dir = product_image_path();
    if (!is_dir($dir)) {
        return 0;
    }

    $keys = array_unique([product_image_key($slug, $id), (string)$id]);
    $deleted = 0;

    foreach ($keys as $base) {
        $suffixes = [''];
        if ($slot === null) {
            for ($i = 1; $i <= PRODUCT_IMAGE_GALLERY_SLOTS; $i++) {
                $suffixes[] = '-' . $i;
            }
        } elseif ($slot > 0) {
            $suffixes = ['-' . $slot];
        }

        foreach ($suffixes as $suffix) {
            foreach (product_image_mimes() as $extension) {
                $path = $dir . '/' . $base . $suffix . '.' . $extension;

                if (is_file($path) && @unlink($path)) {
                    $deleted++;
                }
            }
        }
    }

    product_image_flush();

    return $deleted;
}

/**
 * Save an uploaded product photo.
 *
 * @return array{0: ?string, 1: ?string} [stored filename, error message]
 */
function product_image_upload(array $file, string $slug, int $id, int $slot = 0): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return [null, "Téléversement invalide."];
    }

    if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null]; // Nothing was chosen: not an error.
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return [null, "Le téléversement a échoué (code " . (int)$file['error'] . ")."];
    }

    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
        return [null, "Fichier invalide."];
    }

    if ((int)($file['size'] ?? 0) > PRODUCT_IMAGE_MAX_BYTES) {
        return [null, "Image trop lourde (3 Mo maximum)."];
    }

    // Trust the file's real content, never the name the browser sent.
    $info = @getimagesize($file['tmp_name']);
    $mimes = product_image_mimes();

    if ($info === false || !isset($info['mime'], $mimes[$info['mime']])) {
        return [null, "Format non supporté (JPG, PNG, WebP ou GIF)."];
    }

    if ((int)$info[0] < PRODUCT_IMAGE_MIN_SIDE || (int)$info[1] < PRODUCT_IMAGE_MIN_SIDE) {
        return [null, "Image trop petite (200 × 200 px minimum)."];
    }

    $dir = product_image_path();
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return [null, "Dossier " . PRODUCT_IMAGE_DIR . " introuvable ou non inscriptible."];
    }

    $base   = product_image_key($slug, $id) . ($slot > 0 ? '-' . $slot : '');
    $target = $dir . '/' . $base . '.' . $mimes[$info['mime']];

    // Drop the previous file for this slot so one slot holds one image.
    foreach ($mimes as $extension) {
        $previous = $dir . '/' . $base . '.' . $extension;

        if (is_file($previous) && $previous !== $target) {
            @unlink($previous);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return [null, "Impossible d'enregistrer l'image."];
    }

    @chmod($target, 0644);
    product_image_flush();

    return [basename($target), null];
}
