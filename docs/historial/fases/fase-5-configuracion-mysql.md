# FASE 5 — Configuración de base de datos (MariaDB)

## 1. Qué se construyó

Conexión real de Laravel 13 a la base de datos empresarial, reemplazando el `sqlite` por defecto del instalador por **MariaDB 10.4** (la que trae XAMPP), con un usuario de aplicación dedicado en vez de `root`.

## 2. Por qué un usuario dedicado y no `root`

Usar `root` desde la aplicación viola el principio de menor privilegio: si alguna vez hay una fuga de credenciales (`.env` expuesto, log mal configurado, etc.), el daño queda acotado a una sola base de datos en vez de a todo el servidor MariaDB. Es una práctica de seguridad estándar y de bajo costo de implementar.

## 3. Qué se creó en MariaDB

```sql
CREATE DATABASE marketing_rag_agent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ppp2_app'@'127.0.0.1' IDENTIFIED BY '••••••••••••••••••••••••';
CREATE USER 'ppp2_app'@'localhost' IDENTIFIED BY '••••••••••••••••••••••••';
GRANT ALL PRIVILEGES ON marketing_rag_agent.* TO 'ppp2_app'@'127.0.0.1';
GRANT ALL PRIVILEGES ON marketing_rag_agent.* TO 'ppp2_app'@'localhost';
```

- Base de datos: `marketing_rag_agent`, charset `utf8mb4` (soporta emojis y todos los caracteres del español correctamente, incluida la collation `utf8mb4_unicode_ci`).
- Usuario: `ppp2_app`, con privilegios **solo** sobre esa base de datos (no sobre todo el servidor).
- La contraseña real generada está únicamente en el `.env` de tu máquina (no se sube a git, no se repite en la documentación en texto plano).

## 4. Archivos modificados

### `.env`
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marketing_rag_agent
DB_USERNAME=ppp2_app
DB_PASSWORD=<generada, ver tu .env local>
```
También se ajustó `APP_NAME="Agente RAG Marketing"` y `APP_LOCALE=es` / `APP_FALLBACK_LOCALE=es` / `APP_FAKER_LOCALE=es_ES`, alineado con el requisito de interfaz en español (Fase 1 §41) — esto afectará los seeders (Faker) y mensajes de validación por defecto de Laravel más adelante.

### `.env.example`
Mismo bloque `DB_*` pero con `DB_USERNAME`/`DB_PASSWORD` vacíos (nunca se versiona una credencial real, según Fase 1 §44).

### Limpieza
Se eliminó `database/database.sqlite` (archivo vacío que dejó el instalador antes de cambiar a MariaDB; ya no se usa).

## 5. Comandos ejecutados

```bash
# Arrancar MariaDB de XAMPP (si no está corriendo)
"C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"

# Migraciones por defecto de Laravel, como prueba de conectividad
php artisan migrate --force
```

## 6. Resultado verificado

`php artisan migrate` corrió sin errores y creó las tablas base de Laravel en `marketing_rag_agent`:

```
cache, cache_locks, failed_jobs, job_batches, jobs,
migrations, password_reset_tokens, sessions, users
```

Esto confirma conectividad end-to-end: `.env` → `config/database.php` → MariaDB. Ninguna de estas tablas es del dominio de negocio todavía (esas empiezan en la Fase 8, Empleados) — son las que Laravel necesita para sesiones, colas y caché basados en base de datos, que ya definimos usar (`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database` en el `.env` por defecto del instalador, y que mantenemos así: evita depender de Redis para el MVP, tal como se decidió en la Fase 1 §21).

## 7. Cómo verificarlo tú mismo

1. Abrir phpMyAdmin (`http://localhost/phpmyadmin` con XAMPP corriendo) y confirmar que existe la base `marketing_rag_agent` con las tablas listadas arriba.
2. `php artisan migrate:status` → todas las migraciones deben aparecer como `Ran`.
3. `php artisan tinker` → `DB::connection()->getPdo();` no debe lanzar error.

## 8. Errores comunes

| Error | Causa | Solución |
|---|---|---|
| `SQLSTATE[HY000] [2002] No connection could be made` | MariaDB no está corriendo | Iniciar `mysqld.exe` (o el módulo MySQL desde el panel de control de XAMPP) |
| `Access denied for user 'ppp2_app'@...` | Host de conexión distinto al autorizado | Se crearon usuarios para `127.0.0.1` y `localhost`; si Laravel se conecta desde otro host (raro en desarrollo local) habría que agregar ese host también |
| `Unknown database 'marketing_rag_agent'` | La base no se creó o hay un typo en `DB_DATABASE` | Verificar en phpMyAdmin que el nombre coincide exactamente |
| Laravel sigue usando sqlite pese al cambio en `.env` | Caché de configuración activa (`config:cache`) | En desarrollo no debería estar cacheada; si ocurre, `php artisan config:clear` |

---

**Fase 5 completada y verificada de punta a punta. Quedo a la espera de tu "CONTINUAR" para pasar a la Fase 6 (Autenticación).**
