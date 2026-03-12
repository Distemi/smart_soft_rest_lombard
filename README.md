# SmartLombard REST API Integration

Веб-приложение для интеграции с API SmartLombard: клиентский портал для просмотра залоговых билетов и админ-панель.

**Стек:** PHP 8.4, Symfony 8.0, PostgreSQL 17, Bootstrap 5.3

## Требования

### Docker

- Docker
- Docker Compose v2 (в составе Docker)
- Открытый порт 80 (или другой, указанный с помощью `HTTP_PORT` в `.env.local`)
- Доступ в интернет для загрузки образов и зависимостей

### Без Docker

- PHP 8.4 (CLI + FPM)
- Composer
- PostgreSQL 17
- Web-сервер (Nginx/Apache) + PHP-FPM 8.4
- Node.js LTS + npm (для сборки фронтенда), или bun
- Symfony CLI (опционально, для `symfony server:start` - локального сервера)

**Обязательные расширения PHP:**
- ctype, iconv, json, mbstring, openssl, pdo, tokenizer, xml
- intl
- pdo_pgsql
- zip
- opcache

**Порты и доступ:**
- HTTP: 80 (или `HTTP_PORT` в `.env.local`)
- PostgreSQL: 5432 (если локально), или другой, указанный в `DATABASE_URL`

## Установка

### Docker (рекомендуется)

#### 1. Копирование репозитория и первая настройка

```bash
git clone https://github.com/distemi/smart_soft_rest_lombard.git
cd smart_soft_rest_lombard
cp .env .env.local
```

#### 2. Настройка в `.env.local` переменных среды ([как получить ключ](#шаги-получения-api-ключа))

```env
LOMBARD_SECRET_KEY="ваш_секретный_ключ"
LOMBARD_ACCOUNT_ID="ваш_идентификатор_аккаунта"
APP_SECRET=случайная_строка
POSTGRES_PASSWORD=секретный_пароль
```

<details>
<summary>Как получить APP_SECRET</summary>

```bash
# Вариант 1 (кроссплатформенно, если есть PHP)
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"

# Вариант 2 (Linux/macOS)
openssl rand -hex 32

# Вариант 3 (Windows PowerShell)
[guid]::NewGuid().ToString('N') + [guid]::NewGuid().ToString('N')
```

Сгенерированное значение подставьте в `.env.local`:

```env
APP_SECRET=вставьте_сюда_сгенерированную_строку
```

</details>

#### 3. Сборка и запуск в фоне

```bash
docker compose up -d --build
```

#### 4. Создание админа и принудительная синхронизация данных

```bash
docker compose exec php php bin/console app:create-admin
docker compose exec php php bin/console app:sync-data
```

Сайт доступен на `http://localhost` (порт меняется через `HTTP_PORT` в `.env.local`).

### Без Docker

```bash
composer install
cp .env .env.local
# Настроить DATABASE_URL, LOMBARD_SECRET_KEY, LOMBARD_ACCOUNT_ID, APP_SECRET в .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console app:create-admin
php bin/console app:sync-data
symfony server:start
```

## Доступ

| Раздел            | URL      | Вход           |
|-------------------|----------|----------------|
| Клиентский портал | `/`      | Номер ЗБ + ФИО |
| Админ-панель      | `/admin` | Логин + пароль |

## API

Публичный эндпоинт для получения данных клиента по билету:

- `GET /api/client/tickets`
- Параметры:
    - `fullName` — ФИО полностью (отчество необязательно)
    - `ticketNumber` — номер залогового билета

Пример запроса:

```bash
curl "http://localhost/api/client/tickets?fullName=Иванов Иван Иванович&ticketNumber=АА000001"
```

## Консольные команды

| Команда                      | Описание                     |
|------------------------------|------------------------------|
| `app:sync-data`              | Синхронизация данных с API   |
| `app:create-admin`           | Создание администратора      |
| `app:cleanup-logs --days=30` | Очистка старых API-логов     |
| `app:show-api-logs`          | Просмотр API-логов           |
| `app:set-api-token`          | Установка API-токена вручную |
| `app:clear-api-cache`        | Очистка кэша API-токена      |

## Scheduler

Расписание определено в `src/Schedule.php`:

| Задача                       | Расписание          |
|------------------------------|---------------------|
| `app:sync-data`              | Ежедневно в 02:00   |
| `app:cleanup-logs --days=30` | Воскресенье в 03:00 |

```bash
# Запуск планировщика
php bin/console messenger:consume scheduler_default

# Просмотр задач
php bin/console debug:scheduler
```

На продакшене - через cron или Supervisor:

```bash
* * * * * cd /path/to/project && php bin/console messenger:consume scheduler_default --time-limit=60
```

## Продакшен

```bash
composer install --no-dev --optimize-autoloader
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup
```

<details>
<summary>Nginx конфиг</summary>

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/project/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

</details>

## Решение проблем

| Проблема                | Решение                                                                |
|-------------------------|------------------------------------------------------------------------|
| Ошибка подключения к БД | Проверить `DATABASE_URL` и что PostgreSQL запущен                      |
| API не отвечает         | Проверить `LOMBARD_SECRET_KEY` и `LOMBARD_ACCOUNT_ID`                  |
| Ошибки прав доступа     | `chmod -R 777 var/` (dev) или `chown -R www-data:www-data var/` (prod) |
| Откат миграции          | `php bin/console doctrine:migrations:migrate prev`                     |

## FAQ

### Шаги получения API ключа
1. Войдите в СмартЛомбард (https://online.smartlombard.ru)
2. Перейдите в раздел: Кнопка профиля (справа сверху) → Мои настройки
3. Внизу выбрать "Доступ к ключам API" на "С доступом"
4. Забрать секретный ключ (будет в LOMBARD_SECRET_KEY)
5. Забрать номер пользователя (в пути, вроде https://online.smartlombard.ru/admin2/settings/users/edit/?account_id=xxxxx, где в конце xxxxx - номер)
6. Замените значения ниже на полученные из панели

## Ссылки

- [СмартЛомбард](https://smartlombard.ru/)
- [Документация API](https://docs.api.smartlombard.ru/)
- [Symfony](https://symfony.com/)
- [Bootstrap](https://getbootstrap.com/)
- [Postgres Pro](https://postgrespro.ru/)