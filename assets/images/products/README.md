# Product photos

Drop your product photos in this folder and they appear on the site
automatically — **no database change needed**. A local file always wins over the
image URL stored in the `products` table.

## Naming

Use the product **slug** (preferred) or its **id**. For the product
`Rouge à Lèvres Mat Velvet Teddy` (slug `rouge-a-levres-mat-velvet-teddy`, id `8`):

| File | Used as |
|---|---|
| `rouge-a-levres-mat-velvet-teddy.jpg` | main image |
| `rouge-a-levres-mat-velvet-teddy-1.jpg` | gallery image 2 |
| `rouge-a-levres-mat-velvet-teddy-2.jpg` | gallery image 3 |
| `8.jpg`, `8-1.jpg`, `8-2.jpg` | same thing, using the id |

- Slugs are the last part of a product URL, e.g. `product.php?slug=rouge-a-levres-mat-velvet-teddy`.
- Up to 6 gallery slots (`-1` to `-6`) are read.
- If **no** local file exists for a product, the URL in the database is used.
- If neither exists, a neutral "Photo à venir" tile is shown.

## Rules

- Formats: **JPG, PNG, WebP, GIF**.
- Maximum size: **3 MB** per image.
- Minimum: **200 × 200 px**.
- If a product has several files in different formats for the same slot, the
  one you uploaded last wins (slots hold a single image).
- You can also upload straight from the admin panel (`/admin/index.php`), which
  writes here for you and validates the file.

## Recommended

- Portrait photos, roughly **4:5** (e.g. 800 × 1000 px), so product cards crop neatly.
- Neutral or light backgrounds for a consistent catalogue look.
