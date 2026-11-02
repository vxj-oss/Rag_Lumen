# Despliegue en Railway (con MySQL)

Guía para publicar el proyecto en [Railway](https://railway.com) con una base de datos MySQL. El repositorio ya trae lo necesario: `Dockerfile`, `railway.json` y la carpeta `docker/`.

> **Cómo queda armado**
> - **Servicio web** (este repo): un solo contenedor con Apache + el worker de colas (documentos del RAG) + el scheduler (alertas y respaldo diario), gestionados por `supervisord`.
> - **Servicio MySQL**: base de datos administrada por Railway.
> - **Volume** montado en `/data`: aquí se guardan firmas, evidencias, documentos del RAG y respaldos. Sin Volume, esos archivos se pierden en cada despliegue.
>
> Todo corre en **un solo servicio** porque un Volume solo se puede montar en un servicio. Por eso no hay réplicas.

## 1. Sube el código a GitHub

El repositorio ya existe (`origin`). Comprueba que `Dockerfile`, `railway.json`, `docker/` y `.gitattributes` estén en el commit y haz push:

```bash
git add Dockerfile railway.json .dockerignore docker .gitattributes bootstrap/app.php docs/despliegue-railway.md
git commit -m "Configuración de despliegue en Railway"
git push
```

`.env` no se sube (está en `.gitignore`): las variables se cargan en Railway.

## 2. Crea el proyecto y la base de datos

1. En Railway: **New Project → Deploy from GitHub repo** y elige el repositorio. Railway detecta el `Dockerfile` (lo fija `railway.json`).
2. En el mismo proyecto: **+ New → Database → Add MySQL**.

## 3. Variables del servicio web

En el servicio de la app → **Variables**. Las dos primeras usan *referencias*: Railway rellena los datos del MySQL por ti (cambia `MySQL` por el nombre real de tu servicio de base de datos si es distinto).

| Variable | Valor |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_URL` | `${{MySQL.MYSQL_URL}}` |
| `APP_KEY` | genera una con `php artisan key:generate --show` en tu PC (no reutilices la local) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://TU-DOMINIO.up.railway.app` (después del paso 5) |
| `APP_LOCALE` / `APP_TIMEZONE` | `es` / `America/Lima` |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `LOG_CHANNEL` | `stderr` (para ver los logs en Railway) |
| `FILESYSTEM_PRIVATE_ROOT` | `/data/private` |
| `RAG_EMBEDDING_PROVIDER` / `COHERE_API_KEY` | `cohere` / tu clave |
| `RAG_COMPLETION_PROVIDER` / `GROQ_API_KEY` | `groq` / tu clave |
| `MAIL_MAILER` | `log` (o `smtp` con tus credenciales si quieres correos reales) |

`DB_URL` basta para la conexión: Laravel lo lee y descarta `DB_HOST`, `DB_USERNAME`, etc.

## 4. Volume

Servicio de la app → **Volumes → New Volume** → *Mount path*: `/data`. Con `FILESYSTEM_PRIVATE_ROOT=/data/private` el disco privado de Laravel (`Storage::disk('local')`) queda dentro del Volume.

## 5. Dominio y primer despliegue

1. **Settings → Networking → Generate Domain**. Copia la URL y ponla en `APP_URL`.
2. Despliega. Railway ejecuta, antes de arrancar, `php artisan migrate --force` (está en `railway.json`) y comprueba `GET /up`.

Si algo falla, mira **Deployments → View logs**.

## 6. Datos iniciales (una sola vez)

La base queda con las tablas pero sin roles ni usuarios. Con la [CLI de Railway](https://docs.railway.com/guides/cli) (`npm i -g @railway/cli`, `railway login`, `railway link`):

```bash
railway ssh
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=AreaSeeder --force
php artisan app:create-admin
```

**No ejecutes `php artisan db:seed` a secas**: `DatabaseSeeder` crea un usuario de prueba y los datos de demostración.

## 7. (Opcional) Llevar tus datos de XAMPP

Solo si quieres conservar lo que ya tienes. Desde tu PC, con el XAMPP encendido:

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root --no-tablespaces --ignore-table=marketing_rag_agent.sessions --ignore-table=marketing_rag_agent.cache --ignore-table=marketing_rag_agent.cache_locks --ignore-table=marketing_rag_agent.jobs marketing_rag_agent > respaldo.sql
```

Importa en Railway usando los datos de conexión **públicos** del servicio MySQL (pestaña *Connect* → *Public Network*: host, puerto, usuario, contraseña y base `railway`):

```bash
C:\xampp\mysql\bin\mysql.exe -h HOST -P PUERTO -u root -p railway < respaldo.sql
```

Haz esto **antes del primer despliegue** (así `migrate` encuentra la tabla `migrations` al día) o con la base vacía. Importar el volcado reemplaza los pasos del apartado 6, porque ya trae roles y usuarios.

Los **archivos** (firmas, evidencias, documentos del RAG) no van en el volcado: vuelve a subirlos o cópialos al Volume con `railway ssh`. Las firmas guardadas se pueden regenerar con `php artisan firmas:limpiar` si las subes de nuevo.

## 8. Comprobaciones tras publicar

- `https://TU-DOMINIO/up` responde 200.
- Inicias sesión y las páginas cargan con estilos (si salen sin estilos, falló el build de Vite en el `Dockerfile`).
- Subes un documento en *Documentos*: pasa de *Pendiente* a *Procesado* (lo hace el worker que corre dentro del mismo contenedor).
- Sube una firma, reinicia el servicio y comprueba que sigue ahí (confirma que el Volume funciona).

## Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| Error 500 sin detalle | Falta `APP_KEY`, o hay un error de base de datos: revisa los logs. |
| `Access denied` / no conecta | `DB_URL` mal escrita o el nombre del servicio MySQL en la referencia no coincide. |
| Login que no se mantiene / redirige a `http://` | Falta `APP_URL` con `https` o `SESSION_SECURE_COOKIE`; el proxy ya está confiado en `bootstrap/app.php`. |
| Se pierden archivos tras cada despliegue | Falta el Volume o `FILESYSTEM_PRIVATE_ROOT`. |
| Documentos se quedan en *Pendiente* | El worker no corre (revisa logs de `queue`) o faltan `COHERE_API_KEY` / `GROQ_API_KEY`. |

## Límites a tener en cuenta

- Un solo servicio con Volume ⇒ no se puede escalar a varias réplicas sin mover los archivos a un almacenamiento externo (S3 o similar).
- Railway cobra por uso (plan Hobby con crédito mensual); la base de datos y el Volume suman al consumo.
- Los respaldos diarios (`workspace:backup`) se guardan dentro del propio Volume: descarga copias periódicas aparte.
