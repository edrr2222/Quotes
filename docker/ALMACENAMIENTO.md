El disco del Web Service de Render es EFÍMERO por defecto: cada vez que se reinicia o se hace
un nuevo deploy, lo que esté en `storage/app` (planos subidos, PDF generados) se pierde. Hay
dos formas de resolverlo — usa la que te convenga según el plan que tengas en Render:

## Opción A — Render Disks (más simple, requiere plan pago)
1. En el dashboard del Web Service → "Disks" → agregar un disco, montado en `/var/www/html/storage/app`.
2. No se necesita ningún cambio de código: Laravel sigue usando el disco `local` de siempre.

## Opción B — Almacenamiento S3-compatible (funciona en el plan gratuito)
Usa Cloudflare R2, AWS S3, o Backblaze B2 (todos hablan el mismo protocolo S3).

1. `composer require league/flysystem-aws-s3-v3` (además de lo que ya trae Laravel).
2. En `.env` / variables de entorno de Render:
   ```
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=...
   AWS_SECRET_ACCESS_KEY=...
   AWS_DEFAULT_REGION=auto
   AWS_BUCKET=cotizador-construccion
   AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com   # si usas R2
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```
3. No hay que tocar `PlanoController`, `PlanoAnalyzerService` ni `DocumentoController`: todos
   usan `Storage::` (el disco por defecto), así que el cambio de `local` a `s3` es solo de
   configuración.

Para la práctica/demo, la Opción B es la recomendada porque funciona en el plan gratuito de
Render y evita perder los planos de los proyectos de prueba en cada deploy.
