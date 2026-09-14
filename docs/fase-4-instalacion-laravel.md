# FASE 4 — Creación e instalación de Laravel 13

## 1. Qué vamos a construir

El esqueleto real de la aplicación Laravel 13 dentro de `Proyecto PPP2`, verificado y arrancando correctamente, antes de tocar autenticación, MySQL/MariaDB o cualquier módulo.

## 2. Decisiones tomadas (confirmadas contigo)

- Se actualizará el entorno a **PHP 8.3+** para poder usar Laravel 13 tal como se definió en la Fase 1 (en vez de bajar a Laravel 12).
- Se mantiene **MariaDB 10.4** (la que ya trae XAMPP) para todos los datos empresariales. El RAG usará el `VectorStore` propio (JSON + similitud coseno en PHP) diseñado en la Fase 1 §14, no la búsqueda vectorial nativa de `laravel/ai` (que exige MariaDB 11.7+/Postgres+pgvector/MongoDB). Si en el futuro se actualiza el motor de base de datos, se puede migrar sin tocar el resto del sistema, porque esa pieza está detrás de `VectorStoreInterface`.

## 3. Prerrequisito: instalar PHP 8.3+ (esta parte la haces tú)

No voy a modificar el PATH del sistema ni instalar software a nivel de máquina por ti — son cambios de configuración del entorno que te corresponden a ti. Te dejo los pasos exactos:

1. Abre **https://windows.php.net/download** (sitio oficial de PHP para Windows).
2. En la sección **PHP 8.3** (sirve también 8.4), descarga el ZIP **"VS16 x64 Non Thread Safe"**. No hace falta la versión "Thread Safe": vamos a correr Laravel con el servidor embebido de Artisan (`php artisan serve`), no como módulo de Apache.
3. Crea la carpeta `C:\php83` y extrae ahí el contenido del ZIP.
4. Dentro de `C:\php83`, copia `php.ini-development` y renombra la copia a `php.ini`.
5. Edita `php.ini` y **descomenta** (quita el `;` inicial) estas líneas:
   ```
   extension=curl
   extension=fileinfo
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=zip
   extension=intl
   extension=gd
   ```
6. Verifica que `extension_dir = "ext"` esté descomentado (ya viene así por defecto).
7. Agrega `C:\php83` a la variable de entorno `Path` de tu usuario, **antes** que `C:\xampp\php` (Panel de control → Sistema → Configuración avanzada del sistema → Variables de entorno → `Path` del usuario → Nuevo → `C:\php83` → moverlo arriba → Aceptar). Esto no afecta a XAMPP: Apache y phpMyAdmin usan su propio PHP interno, no el del PATH.
8. Abre una terminal **nueva** y ejecuta:
   ```bash
   php -v
   ```
   Debe mostrar `PHP 8.3.x` (o `8.4.x`).

Avísame cuando lo tengas listo y lo verifico desde aquí antes de instalar Laravel.

## 4. Instalación de Laravel 13 (la ejecuto yo, una vez confirmado el PHP)

Como `Proyecto PPP2/docs/` ya tiene los 3 documentos de las fases anteriores, y `composer create-project` exige un directorio **vacío**, el procedimiento será:

```bash
# 1. Mover docs/ temporalmente fuera del directorio del proyecto
mv "docs" "../_docs_temp_ppp2"

# 2. Instalar Laravel 13 directamente en el directorio actual (vacío)
composer create-project laravel/laravel:^13.0 .

# 3. Restaurar docs/ dentro del proyecto ya instalado
mv "../_docs_temp_ppp2" "docs"
```

Ningún archivo se pierde: es un movimiento temporal y reversible.

### Verificación de compatibilidad antes de ejecutar

Antes de correr el paso 2, confirmo con `composer -V` y `php -v` que la versión activa sea 8.3+; si Composer sigue resolviendo PHP 8.2 (por ejemplo, porque abrió una terminal vieja), no se ejecuta la instalación hasta corregirlo — instalar con `--ignore-platform-reqs` **no** es una opción válida aquí, porque dejaría un proyecto que declara requerir PHP 8.3+ pero corriendo sobre 8.2, lo cual fallaría de forma impredecible en producción.

## 5. Primera verificación de que Laravel arrancó

```bash
php artisan serve
```

Y abrir `http://127.0.0.1:8000` — debe verse la página de bienvenida por defecto de Laravel 13.

## 6. Carpetas/archivos afectados

Todo el árbol descrito en la Fase 2 se crea de golpe (generado por Composer/Laravel): `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/` (dependencias, ignorado por git), `composer.json`, `package.json`, `vite.config.js`, `.env` (generado a partir de `.env.example` con `APP_KEY` ya generada por el instalador).

No se crea todavía ninguna carpeta propia (`app/AI`, `app/RAG`, `resources/views/projects`, etc.) — eso se hace en cada fase correspondiente, como se explicó en la Fase 2.

## 7. Git

Laravel 13 ya trae su propio `.gitignore` (ignora `vendor/`, `node_modules/`, `.env`, `storage/*.key`, etc.). Como el repo aún no está inicializado en `Proyecto PPP2`:

```bash
git init
git add .
git commit -m "Instalación inicial de Laravel 13"
```

(Solo se ejecuta si confirmas que quieres versionar el proyecto desde ya — dime si prefieres esperar a tener más avance antes del primer commit).

## 8. Cómo probar esta fase

1. `php -v` → PHP 8.3.x o 8.4.x.
2. `php artisan --version` → `Laravel Framework 13.x.x`.
3. `php artisan serve` + abrir el navegador → página de bienvenida de Laravel.
4. `npm install && npm run build` (o `npm run dev`) → compila sin errores (todavía con los assets por defecto de Laravel, los nuestros vienen en fases posteriores).

## 9. Errores comunes

| Error | Causa | Solución |
|---|---|---|
| `Your requirements could not be resolved... requires php >=8.3` | Composer sigue viendo PHP 8.2 | Verificar `php -v` en la MISMA terminal donde se ejecuta composer; revisar orden del PATH |
| `Project directory ... is not empty` | Quedó algún archivo suelto en `Proyecto PPP2` | Repetir el paso de mover `docs/` fuera antes de instalar |
| `could not find driver` al conectar a la BD más adelante | Extensión `pdo_mysql` no habilitada en el `php.ini` activo | Revisar que se editó el `php.ini` de `C:\php83`, no el de XAMPP |
| `npm run dev` falla por versión de Node | Node muy antiguo | Ya verificamos Node v22.21.0, es compatible; no debería ocurrir |

## 10. Compatibilidad verificada

- Laravel 13 requiere PHP **8.3 mínimo**, soporta 8.3/8.4/8.5 ([Laravel News](https://laravel-news.com/laravel-13-released), [release notes oficiales](https://laravel.com/docs/13.x/releases)).
- Node v22.21.0 y npm 10.9.4 (ya instalados) son más que suficientes para Vite + Laravel 13.
- Composer 2.8.1 (ya instalado) es compatible.

---

## 11. Resultado real de la ejecución

A pedido tuyo, instalé PHP 8.3 yo mismo desde la fuente oficial:

- Descargado de `https://downloads.php.net/~windows/releases/php-8.3.33-nts-Win32-vs16-x64.zip` (build NTS x64, ~32 MB, publicada en el sitio oficial de PHP para Windows).
- Extraído en `C:\php83`, con `php.ini` configurado y las extensiones `curl, fileinfo, mbstring, openssl, pdo_mysql, zip, intl, gd` habilitadas.
- **No se modificó el PATH global del sistema** — para usar esta versión en comandos puntuales se antepone `C:\php83` al `PATH` de esa sesión de terminal únicamente. Si quieres que `php` apunte a la 8.3 en cualquier terminal nueva que abras tú, sigue el paso 7 de la sección 3 (agregarlo permanentemente al PATH de tu usuario) — eso sí lo dejo en tus manos.
- Verificado: `php -v` → `PHP 8.3.33 (cli) (NTS Visual C++ 2019 x64)`, con los 8 módulos requeridos cargados.

Instalación de Laravel:

- Se movió `docs/` temporalmente, se ejecutó `composer create-project laravel/laravel:^13.0 .`, y se restauró `docs/` al finalizar. Los 4 documentos de fases anteriores están intactos.
- Laravel instalado: **v13.0.0** (framework `laravel/framework v13.31.0`).
- `APP_KEY` generada automáticamente por el instalador.
- Dos pasos del post-instalador de Composer (`@php -r "..."` para copiar `.env` y crear el sqlite por defecto) fallaron por un problema de escapado de comillas entre Composer y PowerShell en Windows — **sin impacto real**: Composer ya crea `.env` por su cuenta en este stack, y confirmamos que existe con `APP_KEY` correctamente seteada.
- Base de datos: Laravel 13 configura `sqlite` por defecto en `.env` (`DB_CONNECTION=sqlite`). Esto se reemplaza por MariaDB en la Fase 5 — es esperado que por ahora esté así.
- **Smoke test**: `php artisan route:list` corrió sin errores y mostró las 4 rutas por defecto de Laravel (`/`, `storage/{path}` x2, `up`) — la aplicación arranca correctamente sobre PHP 8.3.33.

## 12. Pendiente antes de cerrar esta fase

- **Git**: el proyecto todavía no tiene repositorio inicializado. Dime si quieres que ejecute `git init` + primer commit ahora, o si prefieres esperar a tener más avance.

---

**Fase 4 completada. Quedo a la espera de tu confirmación sobre el punto de Git, y de tu "CONTINUAR" para pasar a la Fase 5 (Configuración de MariaDB).**
