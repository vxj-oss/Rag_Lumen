# FASE 2 — Estructura completa de carpetas

> Estado: propuesta para revisión. Sigue sin generarse código Laravel, migraciones, controladores, modelos ni comandos de instalación — eso corresponde a la Fase 4 en adelante.

## 1. Qué vamos a construir en esta fase

El **plano completo y definitivo** del árbol de carpetas del proyecto: no solo `resources/` y `app/` (ya esbozados en la Fase 1), sino también `routes/`, `database/`, `config/`, `storage/` y `tests/`, indicando para cada una si la genera Laravel automáticamente al instalarse o si es una adición nuestra.

## 2. Por qué esta fase va antes de instalar Laravel

`composer create-project` exige que el directorio destino esté **vacío**. Como `Proyecto PPP2` está vacío ahora mismo, si creáramos aquí las carpetas físicas de antemano, la instalación de Laravel (Fase 4) fallaría por "directorio no vacío". Por eso esta fase entrega **solo el blueprint documentado** — la creación física de cada carpeta ocurre progresivamente: el esqueleto base lo crea `composer create-project` en la Fase 4, y las carpetas propias (`app/AI`, `app/RAG`, `resources/views/projects`, etc.) se van creando en la fase donde efectivamente se necesitan (evitamos crear carpetas vacías "por si acaso").

## 3. Árbol completo del proyecto

```
proyecto-ppp2/                          ← raíz del repositorio Laravel
├── app/
│   ├── Models/                         [Laravel] + modelos propios
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Web/                    [nuestro] controladores Blade
│   │   │   └── Api/V1/                 [nuestro] controladores JSON
│   │   ├── Requests/                   [Laravel, poblado por nosotros]
│   │   └── Middleware/                 [Laravel] + middleware propio si aplica
│   ├── Policies/                       [nuestro]
│   ├── Services/                       [nuestro] ProjectMetricsService, ProjectRiskService, ProjectDecisionService
│   ├── Actions/                        [nuestro]
│   ├── Jobs/                           [Laravel, poblado por nosotros]
│   ├── Events/                         [nuestro]
│   ├── Listeners/                      [nuestro]
│   ├── AI/
│   │   ├── Agents/                     [nuestro]
│   │   ├── Tools/                      [nuestro]
│   │   └── Prompts/                    [nuestro]
│   ├── RAG/
│   │   ├── Services/                   [nuestro]
│   │   ├── Loaders/                    [nuestro]
│   │   ├── Chunkers/                   [nuestro]
│   │   ├── Embeddings/                 [nuestro]
│   │   ├── Retrievers/                 [nuestro]
│   │   ├── VectorStores/               [nuestro]
│   │   └── Prompts/                    [nuestro]
│   ├── Providers/                      [Laravel]
│   └── Support/                        [nuestro] helpers/enums compartidos
│
├── bootstrap/                          [Laravel — no se toca]
│
├── config/
│   ├── app.php, database.php, ...      [Laravel]
│   ├── permission.php                  [generado por spatie/laravel-permission]
│   ├── risk.php                        [nuestro] pesos y umbrales de ProjectRiskService
│   ├── decisions.php                   [nuestro] umbrales del motor de decisiones
│   ├── rag.php                         [nuestro] tamaño de chunk, solape, límites de retrieval
│   └── ai.php                          [nuestro] proveedor de LLM/embeddings, modelo, timeouts
│
├── database/
│   ├── migrations/                     [Laravel] una por tabla (ver Fase 3 para el detalle exacto)
│   ├── seeders/                        [Laravel, poblado por nosotros]
│   └── factories/                      [Laravel, poblado por nosotros]
│
├── public/                             [Laravel — build de Vite y punto de entrada]
│
├── resources/
│   ├── views/
│   │   ├── layouts/                    [nuestro] app.blade.php, guest.blade.php
│   │   ├── components/
│   │   │   ├── layouts/                [nuestro] sidebar, navbar
│   │   │   ├── ui/                     [nuestro] card, button, badge, modal, alert, progress-bar, stat-card
│   │   │   ├── forms/                  [nuestro] input, select, textarea
│   │   │   ├── tables/                 [nuestro] table, pagination
│   │   │   └── charts/                 [nuestro] bar-chart, line-chart, donut-chart
│   │   ├── auth/            {html,js,css}/   [nuestro]
│   │   ├── profile/         {html,js,css}/   [nuestro]
│   │   ├── dashboard/       {html,js,css}/   [nuestro]
│   │   ├── employees/       {html,js,css}/   [nuestro]
│   │   ├── projects/        {html,js,css}/   [nuestro]
│   │   ├── tasks/           {html,js,css}/   [nuestro]
│   │   ├── alerts/          {html,js,css}/   [nuestro]
│   │   ├── reports/         {html,js,css}/   [nuestro]
│   │   ├── ai/              {html,js,css}/   [nuestro]
│   │   └── rag/             {html,js,css}/   [nuestro]
│   ├── js/
│   │   ├── app.js                      [Laravel, lo vaciamos de lógica de página]
│   │   └── shared/                     [nuestro] http.js, toast.js, formatters.js
│   └── css/
│       └── app.css                     [Laravel] entrada de Tailwind
│
├── routes/
│   ├── web.php                         [Laravel] rutas Blade por módulo
│   ├── api.php                         [Laravel] grupo /api/v1
│   └── console.php                     [Laravel] scheduler (Fase 46 del plan original)
│
├── storage/
│   ├── app/
│   │   ├── private/rag-documents/      [nuestro] disco privado para documentos subidos
│   │   └── public/                     [Laravel]
│   └── logs/                           [Laravel]
│
├── tests/
│   ├── Feature/
│   │   ├── Auth/                       [nuestro]
│   │   ├── Projects/                   [nuestro]
│   │   ├── Tasks/                      [nuestro]
│   │   ├── Risk/                       [nuestro]
│   │   ├── Decisions/                  [nuestro]
│   │   ├── Rag/                        [nuestro]
│   │   ├── Ai/                         [nuestro]
│   │   └── Api/                        [nuestro]
│   └── Unit/
│       ├── Services/                   [nuestro] ProjectRiskServiceTest, ProjectMetricsServiceTest...
│       └── Rag/                        [nuestro] ChunkerTest, RetrieverTest...
│
├── .env.example                        [Laravel, ampliado con nuestras variables — ver Fase 1 §44]
├── composer.json                       [Laravel]
├── package.json                        [Laravel]
├── vite.config.js                      [Laravel, ampliado con nuestras entradas]
├── tailwind.config.js                  [Laravel/Breeze, ajustado a nuestras rutas de vistas]
└── docs/
    ├── fase-1-arquitectura.md          [ya creado]
    ├── fase-2-estructura-carpetas.md   [este archivo]
    ├── architecture.md                 [se consolidará al cierre del proyecto, Fase 35]
    ├── database.md
    ├── rag.md
    ├── ai-agent.md
    ├── api.md
    ├── installation.md
    ├── security.md
    └── decisions.md
```

## 4. Carpetas afectadas en esta fase

Solo `docs/` (documentación). No se toca `app/`, `resources/`, `database/`, etc. todavía porque Laravel aún no existe en este directorio.

## 5. Archivos creados en esta fase

- [docs/fase-2-estructura-carpetas.md](../Proyecto%20PPP2/docs/fase-2-estructura-carpetas.md) (este documento).

## 6. Comandos

Ninguno en esta fase. La creación física del árbol comienza en la **Fase 4** con:

```bash
composer create-project laravel/laravel:^13.0 .
```

(comando que se ejecutará y explicará en detalle en esa fase, no ahora).

## 7. Cómo verificar esta fase

Revisar que el árbol de la sección 3 sea coherente con lo acordado en la Fase 1 y cubra los 4 dominios (empresarial, métricas/riesgo/decisiones, RAG, agente) sin carpetas sobrantes.

## 8. Errores comunes a evitar más adelante (por anticipado)

- **Instalar Laravel sobre un directorio no vacío**: por eso no creamos carpetas físicas todavía. Si en algún punto se crea un archivo suelto en `Proyecto PPP2` antes de la Fase 4, habrá que moverlo o instalar en un subdirectorio temporal y luego trasladar el contenido.
- **Mezclar mayúsculas/minúsculas en nombres de carpetas**: Windows no distingue mayúsculas de minúsculas en rutas, pero un servidor Linux de producción sí. Se usará consistentemente `kebab-case` o `snake_case` en minúsculas para carpetas y archivos (excepto clases PHP, que siguen `PascalCase` por convención de Laravel/PSR-4) para evitar bugs de "funciona en mi máquina, falla en el servidor".
- **Crear carpetas vacías "por si acaso"**: cada carpeta de la sección 3 se crea en la fase que efectivamente la necesita, para no terminar con estructura muerta sin uso.

---

**Quedo a la espera de tu confirmación ("CONTINUAR") o de los cambios que quieras hacer antes de avanzar a la Fase 3 (Base de datos y ERD).**
