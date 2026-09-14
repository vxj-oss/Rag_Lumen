# Despliegue en Hostinger (Cloud Professional Hosting)

Guía de referencia para subir este proyecto a Hostinger. El plan Cloud Professional es hosting administrado (sin acceso root, sin instalar software adicional, sin procesos persistentes), así que varias cosas que funcionan en local (Ollama, `queue:work`, `schedule:work`) no aplican tal cual en producción.

## 1. Motor del chat RAG: Ollama → Cohere + Groq

Ollama no puede correr en este plan (necesita instalar software y mantener un proceso vivo). Ya se implementó el cambio de proveedor en el código (`config/rag.php`, `app/Providers/AppServiceProvider.php`, `app/RAG/Embeddings/CohereEmbeddingService.php`, `app/RAG/Generators/GroqAnswerGenerator.php`); en producción solo hace falta configurar el `.env`:

```env
RAG_EMBEDDING_PROVIDER=cohere
COHERE_API_KEY=tu_api_key_de_cohere
COHERE_EMBEDDING_MODEL=embed-multilingual-v3.0

RAG_COMPLETION_PROVIDER=groq
GROQ_API_KEY=tu_api_key_de_groq
GROQ_COMPLETION_MODEL=openai/gpt-oss-120b
```

- Cohere: crea una cuenta gratis en https://dashboard.cohere.com/api-keys
- Groq: crea una cuenta gratis en https://console.groq.com/keys

Groq cambia su catálogo de modelos disponibles con cierta frecuencia (al probar esto, `llama-3.3-70b-versatile` ya no existía para la cuenta nueva). Si `GROQ_COMPLETION_MODEL` deja de funcionar, revisa los modelos vigentes en https://console.groq.com/docs/models o pide la lista con `GET https://api.groq.com/openai/v1/models`.

En local, dejar `RAG_EMBEDDING_PROVIDER=ollama` y `RAG_COMPLETION_PROVIDER=ollama` sigue funcionando igual que antes — es un cambio de `.env`, no de código.

**Importante**: si ya tienes documentos procesados en producción con un proveedor y cambias al otro, los embeddings viejos quedan en una dimensión distinta (384 de Ollama vs 1024 de Cohere) y dejan de aportar en las búsquedas silenciosamente (no da error, solo no los encuentra). Si eso pasa, hay que reprocesar esos documentos (botón "Reintentar" en Documentos, o resetear su estado a `pending`).

## 2. Checklist de `.env` de producción

No copies tu `.env` local tal cual. Cambios obligatorios:

| Variable | Valor en producción |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (¡crítico! con `true` cualquiera ve trazas de error con rutas y datos internos) |
| `APP_URL` | `https://tudominio.com` |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | los reales de la base de datos MySQL que crees en hPanel |
| `SESSION_SECURE_COOKIE` | `true` (agregar esta línea; no existe en el `.env` actual) |
| `MAIL_MAILER` | `smtp` con las credenciales SMTP que te da Hostinger en hPanel → Emails |
| `RAG_EMBEDDING_PROVIDER` / `RAG_COMPLETION_PROVIDER` | `cohere` / `groq` (ver sección 1) |

Genera una `APP_KEY` nueva para producción (no reuses la de local):
```bash
php artisan key:generate --force
```

## 3. Dónde debe apuntar el dominio (document root)

Laravel se sirve desde la carpeta `public/`, **no** desde la raíz del proyecto. Si subes todo el proyecto dentro de `public_html` tal cual, cualquiera podría ver `.env`, el código fuente, etc.

En hPanel, al agregar el sitio (Websites → Add website → PHP/HTML o similar), busca la opción de **Document Root** / **carpeta raíz del sitio** en la configuración avanzada del dominio y apúntala a la carpeta `public/` de tu proyecto (ej. `domains/tudominio.com/laravel_app/public`), dejando el resto del proyecto (`app/`, `.env`, `vendor/`, etc.) **fuera** de `public_html`.

## 4. Build antes de subir

Desde tu máquina, antes de subir los archivos:
```bash
composer install --no-dev --optimize-autoloader
npm run build
```
Sube todo el proyecto (incluyendo `vendor/` y `public/build/`, ya generados) — Hostinger Cloud no corre `composer`/`npm` por ti automáticamente salvo que tengas acceso SSH para hacerlo ahí mismo (revisa hPanel → Avanzado → SSH Access; si lo tienes, es más prolijo correr `composer install` directo en el servidor en vez de subir `vendor/` por FTP).

## 5. Migraciones

Por SSH (o el terminal que te dé hPanel):
```bash
php artisan migrate --force
```
El `--force` es obligatorio porque `APP_ENV=production` bloquea migraciones por seguridad si no lo pones.

## 6. Cron Jobs (reemplazan a `queue:work` / `schedule:work`)

En local dejamos `queue:work` y `schedule:work` corriendo como procesos de fondo — eso **no existe** en este plan. hPanel → Avanzado → Cron Jobs, agrega dos tareas cada minuto:

```bash
* * * * * php /home/tu_usuario/domains/tudominio.com/laravel_app/artisan schedule:run >> /dev/null 2>&1
* * * * * php /home/tu_usuario/domains/tudominio.com/laravel_app/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

- La primera dispara `alerts:generate` cada hora (ya programado en `routes/console.php`).
- La segunda procesa lo que haya en cola (documentos RAG subidos) y se cierra sola antes del siguiente minuto — es el patrón estándar de Laravel para hosting sin procesos persistentes.

Ajusta la ruta del `artisan` a donde realmente quede tu proyecto en el servidor.

## 6.1 Crear el primer usuario administrador

Las migraciones no crean ningún usuario — sin esto no vas a poder entrar al sistema recién subido. Por SSH, una sola vez:

```bash
php artisan app:create-admin
```

Te va a pedir nombre, correo y contraseña de forma interactiva (la contraseña no se muestra en pantalla). También acepta los valores como flags si prefieres no usar los prompts:

```bash
php artisan app:create-admin --name="Tu Nombre" --email="tu@correo.com" --password="tu-contraseña"
```

El usuario queda creado ya verificado y con el rol de administrador.

## 7. Seguridad antes de salir a producción

- **Cambia o elimina el usuario admin de prueba** (`test@example.com` / `password`) — hoy es de conocimiento público en el historial de este proyecto. Cambia su contraseña desde Perfil apenas tengas acceso, o bórralo y crea el admin real.
- Confirma `APP_DEBUG=false` (sección 2).
- Verifica que Hostinger tenga el certificado SSL activo (gratis, Let's Encrypt, se activa desde hPanel → SSL) antes de forzar `https://` en `APP_URL`.

## 8. Verificación post-despliegue

- [ ] El sitio carga en `https://tudominio.com` sin errores
- [ ] Corriste `php artisan app:create-admin` y puedes iniciar sesión con ese usuario (punto 6.1)
- [ ] Subir un documento de prueba y confirmar que pasa de "Pendiente" a "Procesado" (verifica que el cron de `queue:work` esté corriendo)
- [ ] Preguntar algo en el Asistente y confirmar que responde citando fuentes
- [ ] Revisar que llegue un correo de prueba (alerta o notificación)
- [ ] Borrar el usuario/contraseña de prueba mencionado en el punto 7
