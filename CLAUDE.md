# CLAUDE.md

Contexto para Claude Code (u otra sesión de Claude) trabajando en este repositorio.

## Qué es esto

Sistema interno (con login) para automatizar la elaboración de cotizaciones de obras de
construcción (piscinas, remodelaciones, obra civil en general) a partir de planos en PDF.
Reemplaza el flujo manual de: leer planos → calcular cantidades → armar tablas de Excel/Word →
exportar PDF, que hoy se hace a mano por cada proyecto.

Cliente/caso de uso real: A.R.R Construcciones Reparaciones y Mantenimiento de Edificaciones
S.A.S (NIT 901799990), como parte de la práctica 3 con la Universidad Uniempresarial (Compensar).

La app corre de dos formas con el mismo código:
- **Local/portable**: SQLite, sin depender de internet (útil para desarrollo).
- **Desplegada en Render**: PostgreSQL + Docker (`Dockerfile`, `render.yaml`), que es el
  destino real para que A.R.R Construcciones la use. Ver README.md sección 5.

En ambos casos, el análisis automático de planos con IA (Anthropic) es opcional y depende de
que `ANTHROPIC_API_KEY` esté configurada.

## Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: React 18 vía Inertia.js (no es una API separada; Inertia sirve las páginas
  React directamente desde las rutas de Laravel, sin necesidad de un SPA independiente)
- **Auth**: Laravel Breeze (stack React + Inertia) — login/registro/recuperación de contraseña
- **Base de datos**: SQLite en local (`database/database.sqlite`) / PostgreSQL en Render
  (`DB_CONNECTION=pgsql`). **En producción, la base es compartida con otras dos apps**
  (Door To Door y MyBarberShop) sobre la misma instancia de Render (plan free = una sola
  base por cuenta) — este proyecto vive aislado en su propio schema (`cotizador`) y su
  propio rol de Postgres (`cotizador_user`), sin acceso a los schemas de las otras apps.
  Ver `sql/001_schema_setup.sql` (el script que crea rol+schema, se corre a mano una sola
  vez) y `app/Console/Commands/PrepareSchema.php` (`db:prepare-schema`, corre en cada
  deploy antes de `migrate` porque el schema debe existir antes de que Laravel cree su
  tabla de control de migraciones). Las migraciones en sí no cambian: Postgres las aplica
  dentro del schema que indique el `search_path` de la conexión, de forma transparente.
- **Despliegue**: `Dockerfile` (PHP-FPM + Nginx + build de assets en una etapa previa) +
  `render.yaml` (blueprint: solo el web service — la base NO se crea desde este blueprint,
  es la instancia compartida ya existente). Ver `docker/ALMACENAMIENTO.md` para
  el problema del disco efímero de Render (planos y PDF generados necesitan un disco montado
  o un bucket S3-compatible, no el filesystem local del contenedor).
- **PDF**: `barryvdh/laravel-dompdf` sobre vistas Blade (no genera los PDF desde React)
- **Análisis de planos**: llamadas server-side a la API de Anthropic (`ANTHROPIC_API_KEY` en
  `.env`) para leer los PDF de planos (convertidos a imagen) y extraer cotas/metadatos

## Cómo se instaló este repo (importante para entender qué falta)

Este directorio contiene el **código de negocio ya escrito** (migraciones, modelos,
controladores, servicios, páginas React, plantillas PDF) pero NO contiene el esqueleto base
de Laravel/Breeze (vendor/, node_modules/, config/, bootstrap/, public/index.php, etc.),
porque el entorno donde se generó no tenía acceso a Packagist. Ver `README.md` para los
pasos exactos: se crea un Laravel nuevo con Breeze+React, y estos archivos se copian encima.

Si estás retomando este proyecto y el esqueleto base ya existe, ignora esta sección.

## Modelo de dominio

```
Proyecto (1) ──< Plano (N)                planos PDF subidos, clasificados por tipo
Proyecto (1) ──< Cotizacion (N)           una cotización puede tener varias versiones
Cotizacion (1) ──< EspecificacionItem (N) filas del alcance del trabajo (sin precios)
Cotizacion (1) ──< ManoObraItem (N)       filas de la tabla de mano de obra
Cotizacion (1) ──< MaterialItem (N)       filas de la tabla de materiales (obra civil/hidráulico/eléctrico)
Cotizacion (1) ──< Documento (N)          los 3 PDF generados (ver más abajo)
```

- **Proyecto**: cliente, ubicación (municipio/departamento — importa para el % de transporte),
  descripción, estado.
- **Plano**: archivo, tipo (`arquitectonico|estructural|hidraulico|electrico`), y un campo
  `analisis_json` donde queda el resultado de `PlanoAnalyzerService` (cotas, profundidades,
  materiales mencionados, texto crudo extraído).
- **Cotizacion**: pertenece a un proyecto, tiene los porcentajes de la cotización (AIU,
  transporte, IVA, factor prestacional) como columnas editables — **nunca hardcodear estos
  porcentajes en el código**, son parámetros del negocio y cambian por proyecto.
- **ManoObraItem**: cargo, número de personas, jornal básico, jornales, tarifa ARL/día.
  El costo total se calcula en `ManoObraCalculatorService`, no en el modelo ni en el front.
- **MaterialItem**: categoría (`obra_civil|hidraulico|electrico`), descripción, unidad,
  cantidad, valor unitario. El total es `cantidad * valor_unitario`, calculado en el backend.
- **EspecificacionItem**: `categoria` (texto libre, no enum — a veces el alcance no calza en
  obra_civil/hidraulico/electrico, ej. "Localización y trazado") + `descripcion`. No tiene
  precio: es solo el "qué hay que hacer", no el "cuánto cuesta".
- **Documento**: registro de cada PDF generado (tipo + ruta en storage), para poder
  regenerar/descargar sin recalcular. `tipo` es uno de exactamente estos 3 (ver más abajo):
  `especificacion`, `especificacion_mano_obra`, `especificacion_materiales`.

## Reglas de negocio a respetar

1. **Los cálculos de dinero viven en `app/Services/`, no en los modelos ni en React.**
   React solo muestra lo que el backend calculó; si necesitas un "total en vivo" mientras el
   usuario edita, replica la fórmula en el componente pero la fuente de verdad al guardar es
   siempre el backend.
2. **Factor prestacional / ARL**: es un porcentaje configurable por cotización
   (`cotizaciones.factor_prestacional`), no una constante en código. Referencia usada al
   construir el sistema: ~52% sobre el jornal básico para riesgo ARL clase V (construcción),
   pero el usuario debe poder cambiarlo por proyecto.
3. **AIU (Administración, Imprevistos, Utilidad)** e IVA son igualmente porcentajes editables
   en `cotizaciones`, no constantes.
4. **Nunca inventes precios de materiales en el código.** Los `MaterialItem` los ingresa el
   usuario (o los pre-llena una plantilla editable); no hay una tabla de "precios de mercado"
   embebida que se dé por autoritativa.
5. El sistema genera **exactamente 3 documentos** (requisito explícito de A.R.R
   Construcciones, no un límite técnico — ver README.md sección 1 si más adelante piden sumar
   el APU o un acta de inicio):
   - `especificacion`: solo el alcance del trabajo (`EspecificacionItem`), sin precios.
   - `especificacion_mano_obra`: el mismo alcance + la tabla de mano de obra.
   - `especificacion_materiales`: el mismo alcance + la tabla de materiales.
   La sección de especificación es un parcial Blade compartido
   (`resources/views/pdf/_especificacion-seccion.blade.php`), incluido en los 3 — no la
   dupliques copiando el HTML en cada plantilla.

6. **Nunca hardcodear el nombre del schema** (`cotizador`) en queries, migraciones o
   Blade/React. Si algún código necesita saberlo explícitamente, se lee de
   `config('database.connections.pgsql.app_schema')` — igual que hace
   `PrepareSchema.php` — nunca como un string literal repetido en varios sitios.

Flujo:
1. Se sube un PDF a un `Proyecto`.
2. El servicio convierte cada página a imagen (usa el binario `pdftoppm` vía `Process`, ya
   que es más simple y no requiere la extensión Imagick).
3. Envía la(s) imagen(es) a la API de Anthropic (`claude-sonnet-4-6`, modelo de visión) con
   un prompt que pide devolver JSON: tipo de plano, cotas encontradas, profundidades,
   materiales/equipos mencionados en las leyendas.
4. Guarda ese JSON en `planos.analisis_json` y lo muestra en `Planos/Show.jsx` para que el
   usuario lo revise y copie las cantidades relevantes a la cotización — **el sistema no
   genera la cotización automáticamente a partir del plano sin revisión humana**, porque la
   lectura de cotas en planos complejos (formas irregulares, cortes) puede fallar o ser
   incompleta. Es un asistente, no un oráculo.

Si el usuario no configura `ANTHROPIC_API_KEY`, la funcionalidad de análisis se desactiva
silenciosamente (el botón "Analizar plano" queda deshabilitado con un tooltip) — la carga y
organización manual de planos sigue funcionando sin la API.

## Comandos frecuentes

```bash
php artisan serve                  # servidor local
npm run dev                        # Vite en modo desarrollo (HMR)
npm run build                      # build de producción de los assets
php artisan migrate:fresh --seed   # reiniciar BD SQLite con datos de ejemplo
php artisan test                   # tests (Pest, si se agregan)
```

## Convenciones

- Nombres de tablas, rutas y variables de negocio **en español** (`proyectos`, `cotizaciones`,
  `mano_obra_items`), porque así piensa el usuario del sistema y evita traducir mentalmente.
  El código PHP/JS en sí (nombres de métodos, variables internas) puede ser en inglés si es
  más natural (`calculate()`, `index()`), pero los nombres de dominio visibles (columnas,
  rutas, labels de UI) van en español.
- Cada controlador de recurso (`ProyectoController`, `CotizacionController`, etc.) sigue
  REST estándar de Laravel (`index/store/show/update/destroy`); no agregar acciones custom
  salvo las de generación de documentos (`DocumentoController@generar`).
- Las páginas Inertia usan `Layouts/AppLayout.jsx` como layout compartido (sidebar + navbar
  con el nombre del usuario logueado).

## Qué falta / próximos pasos sugeridos

- Tests automáticos (no se incluyeron; priorizar `ManoObraCalculatorService`,
  `CotizacionSummaryService` y `MaterialItem` porque ahí vive la plata).
- **Decidir almacenamiento persistente antes del primer deploy real** (Render Disk vs S3/R2 —
  ver `docker/ALMACENAMIENTO.md`); sin esto, los planos subidos se pierden en cada deploy.
- Exportar también a `.docx` si lo piden, reutilizando la lógica que ya se validó a mano con
  `docx` (Node) en conversaciones previas de cotización.
- Multi-usuario con roles si más de una persona de A.R.R Construcciones va a usar el sistema
  (hoy cada `Proyecto` pertenece a un único `user_id`, sin conceptos de equipo/organización).
- Versionado de cotizaciones (hoy `Cotizacion` no tiene un campo `version`, se podría agregar
  si el flujo de negocio necesita comparar versiones de una misma cotización).
- Sumar el APU, el cronograma o el acta de inicio como documentos 4, 5, 6 si el alcance de la
  práctica crece — el modelo ya está pensado para eso (ver regla de negocio 5).
