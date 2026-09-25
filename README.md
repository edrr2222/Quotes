# Cotizador de Construcción

Sistema con login para automatizar la cotización de obras (piscinas, remodelaciones, obra
civil) a partir de planos en PDF: sube los planos, el sistema ayuda a organizarlos y a leer
sus cotas, y genera 3 documentos en PDF:

1. **Especificación del trabajo** — el alcance de la obra, sin precios.
2. **Especificación + Cotización de mano de obra** — el mismo alcance, con la tabla de
   cuadrilla/jornales/ARL/imprevistos.
3. **Especificación + Cotización de materiales** — el mismo alcance, con la tabla de
   materiales por categoría (obra civil / hidráulico / eléctrico).

Puede correr **local** (con SQLite, ver sección 2) o **desplegado en Render** con PostgreSQL
(ver sección 5) — el código es el mismo, solo cambia la configuración de base de datos y
almacenamiento de archivos.

## 1. Qué documentos suele llevar una cotización de obra (contexto)

No hace falta que inventes el formato — esto es lo estándar en construcción en Colombia,
y es lo que este sistema está pensado para generar:

| Documento | Para qué sirve |
|---|---|
| **Cotización / propuesta económica** | El documento principal que se le entrega al cliente: alcance, presupuesto por ítem, condiciones comerciales. Es el que ya venías armando en Word. |
| **APU (Análisis de Precios Unitarios)** | El desglose de cómo se calculó cada precio unitario (materiales + mano de obra + equipo por unidad de obra). Es el "respaldo técnico" de la cotización; muchos clientes corporativos o entidades lo piden aparte. |
| **Cronograma de obra** | Diagrama de Gantt simple con las fases (localización, cimentación, estructura, acabados, instalación hidráulica/eléctrica, entrega) y sus duraciones. |
| **Acta de inicio de obra** | Documento corto que firman ambas partes el día que arranca la obra; deja constancia de la fecha de inicio (importante para contar plazos y garantías). |
| **Actas de avance / entrega parcial** | Se firman en cada corte de obra (ej. mensual) certificando el % ejecutado — respaldan los pagos intermedios. |
| **Contrato de obra u orden de compra** | El documento legal que ata la cotización aprobada; puede ser tan simple como una orden de compra firmada. |
| **Pólizas de cumplimiento / garantía** (opcional) | En obras más grandes, garantías que respaldan el cumplimiento y la calidad de la obra. |

Este sistema, en su primera versión, genera los 3 documentos descritos arriba (especificación
sola, especificación + mano de obra, especificación + materiales) porque son los que definió
A.R.R Construcciones para la práctica. El modelo `Documento` está diseñado para que agregar el
APU, el cronograma o el acta de inicio más adelante sea solo una plantilla Blade nueva y un
valor más en el enum `tipo`, no un cambio de arquitectura.

## 2. Instalación (ejecutar en tu máquina, con internet)

El repositorio ya incluye el esqueleto completo (Laravel 12 + Breeze React/Inertia +
dompdf), así que basta con clonarlo e instalar dependencias (requiere PHP 8.2+, Composer y
Node 20+):

```bash
git clone https://github.com/edrr2222/Quotes.git cotizador-construccion
cd cotizador-construccion

composer install
npm install

cp .env.example .env          # queda con DB_CONNECTION=sqlite
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# (Opcional) análisis automático de planos con IA: en .env, ANTHROPIC_API_KEY=sk-ant-...

npm run build
php artisan serve
```

Abre `http://127.0.0.1:8000`, crea tu usuario desde "Registrarse" (o crea uno con
`php artisan tinker` si prefieres deshabilitar el registro público más adelante).

Para desarrollo día a día (hot reload), corre `npm run dev` en una terminal aparte de
`php artisan serve`.

## 3. Estructura de este paquete

```
app/Models/            Proyecto, Plano, Cotizacion, EspecificacionItem, ManoObraItem, MaterialItem, Documento
app/Http/Controllers/  ProyectoController, PlanoController, CotizacionController, DocumentoController
app/Services/          PlanoAnalyzerService, ManoObraCalculatorService, CotizacionSummaryService
database/migrations/   Las 7 tablas de negocio (sobre el esqueleto de Breeze)
resources/js/Pages/    Proyectos (Index/Show), Cotizaciones/Edit
resources/views/pdf/   especificacion, especificacion-mano-obra, especificacion-materiales (+ parciales)
routes/web.php         Todas las rutas de la app (reemplaza el web.php de ejemplo de Breeze)
Dockerfile, docker/    Imagen para desplegar en Render (PHP-FPM + Nginx + build de assets)
render.yaml            Blueprint de Render (web service + base de datos Postgres)
CLAUDE.md              Contexto para retomar el desarrollo con Claude Code
```

## 4. Flujo de uso

1. Inicias sesión → creas un **Proyecto** (cliente, ubicación, descripción).
2. Subes los **planos** en PDF al proyecto; opcionalmente le das "Analizar" a cada uno para
   que la IA extraiga cotas y texto de leyendas (requiere `ANTHROPIC_API_KEY`).
3. Creas una **Cotización** dentro del proyecto: llenas la lista de **especificación** (qué
   hay que hacer, agrupado por categoría), la tabla de **mano de obra** (cargos, jornales,
   ARL) y la de **materiales** (por categoría), ajustando los porcentajes de AIU, transporte
   e IVA según el proyecto.
4. Generas los 3 PDF: especificación sola, especificación + mano de obra, especificación +
   materiales — cada uno queda guardado como `Documento` para volver a descargarlo sin
   recalcular.

## 5. Desplegar en Render

El paquete trae `Dockerfile` + `render.yaml` listos (PHP-FPM + Nginx en un solo contenedor,
build de los assets React/Inertia en una etapa previa). Pasos:

1. El código ya está en GitHub (`edrr2222/Quotes`).
2. En Render: **New → Blueprint**, apunta al repo — Render lee `render.yaml` y crea el Web
   Service (Docker) y la base de datos PostgreSQL juntos.
3. Genera la `APP_KEY` en tu máquina con `php artisan key:generate --show` y pégala en las
   variables de entorno del servicio en el dashboard de Render (el `render.yaml` la deja
   marcada como `sync: false` a propósito, para que no quede en el repo).
4. Si vas a usar el análisis de planos con IA, pega también `ANTHROPIC_API_KEY` ahí.
5. Primer deploy: Render construye la imagen, corre `php artisan migrate --force`
   automáticamente (ver `docker/start.sh`) y levanta el servicio.

**Importante — almacenamiento de archivos**: el disco del Web Service de Render es efímero
por defecto (se borra en cada deploy). Los planos PDF subidos y los documentos generados
necesitan almacenamiento persistente aparte — ver `docker/ALMACENAMIENTO.md` para las dos
opciones (Render Disks o un bucket S3-compatible como Cloudflare R2).

**Nota sobre SQLite**: en Render se usa PostgreSQL (`DB_CONNECTION=pgsql`), no SQLite. Las
migraciones de este paquete no usan nada específico de SQLite, así que no hay que tocarlas.

**Base de datos compartida**: este proyecto NO crea su propia instancia de Postgres en
Render — reutiliza una instancia existente compartida con otras dos apps (Render solo
permite una base gratis por cuenta), aislado en su propio schema y rol de Postgres.
Antes del primer deploy:

1. Corre `sql/001_schema_setup.sql` conectado con la External Connection String de la
   base compartida (como el usuario admin, no como el rol nuevo) — crea el rol
   `cotizador_user` y el schema `cotizador`, sin tocar los schemas de las otras apps.
2. En Render, llena las variables de entorno de `render.yaml` marcadas `sync: false`
   (`DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE`) con los datos del rol que
   acabas de crear.
3. El comando `php artisan db:prepare-schema` (ya incluido en `docker/start.sh`, antes de
   `migrate`) crea el schema `cotizador` si todavía no existe la primera vez — evita que
   `migrate` falle al intentar crear su tabla de control en un schema inexistente.

La conexión `pgsql` de `config/database.php` lee `DB_SCHEMA` (clave propia `app_schema` y el
`search_path` real de la conexión).
