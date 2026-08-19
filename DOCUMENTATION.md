# Polimer — документация проекта

> Интернет-магазин на 1С-Битрикс (polimer-vrn.ru).
> Документ поддерживается агентом: при изменениях в проекте — **обновлять этот файл**.

Последнее обновление: 2026-07-17 (reCAPTCHA: один Site Key + siteverify, Secret вне git)

## 1. Окружения

| Папка | Назначение | Боевой домен | Локальный порт |
|---|---|---|---|
| `dev.polimer-vrn.ru/` | **Dev** — выкатываем сюда то, что делаем на локале; клиент смотрит и согласует | `dev.polimer-vrn.ru` | **8084** |
| `polimer-vrn.ru/` | **Prod** — рабочий сайт; выкатываем после согласования на dev | `polimer-vrn.ru` | локально не поднимаем |

**Workflow:** локаль → dev (8084) → согласование клиентом на `dev.polimer-vrn.ru` → деплой на prod (`polimer-vrn.ru`) на сервере.

На сервере оба сайта лежат в **одной базе** (`polimer_vrn_r_db`); в файловой системе — общие каталоги, симлинки и отдельные файлы.

## 2. Структура каталога

```
polimer/
├── dev.polimer-vrn.ru/     # dev-копия (git-репозиторий)
│   └── upload/             # медиафайлы (~17 ГБ), не в git
├── polimer-vrn.ru/         # prod-копия (локально без upload и без порта)
├── архивы/                 # бэкапы/архивы
├── DOCUMENTATION.md        # этот файл
├── scripts/
│   ├── start-dev.sh        # запуск dev-сайта локально (8084)
│   └── stop-dev.sh         # остановка
└── .local/                 # nginx + php-fpm 8.3 (не в git)
```

`upload/` лежит в `dev.polimer-vrn.ru/upload/` (реальная папка). Nginx root = `dev.polimer-vrn.ru`, пути `/upload/...` резолвятся туда. На сервере `dev.polimer-vrn.ru/upload` — симлинк на общую `polimer-vrn.ru/upload`.

## 3. Платформа

| | |
|---|---|
| CMS | 1С-Битрикс, версия **26.250.100** |
| PHP | **8.3** (`short_open_tag=On` — обязательно) |
| БД | MySQL, схема `polimer_vrn_r_db` |
| Конфиг БД | `bitrix/.settings.php`, `bitrix/php_interface/dbconn.php` |

## 4. База данных

> **Важно:** prod (`polimer-vrn.ru`) всегда на своей схеме `polimer_vrn_r_db`.  
> Локаль и remote-dev переключаются отдельно и **не деплоятся через git** (`dbconn.php` / `.settings.php` в `.gitignore`).

### Активная БД для локали и remote-dev (с 2026-08-11)

| | Локаль | Remote-dev (`dev.polimer-vrn.ru`) |
|---|---|---|
| Host | `127.0.0.1` | `localhost` |
| Schema | `dev` | `dev` |
| Логин / пароль | `dev_usr` / `devdevdev` | `dev_usr` / `devdevdev` |
| Дамп | `~/Downloads/polimer_vrn_r_db (1).sql.gz` | тот же дамп на сервере |

Конфиг локали: `dev.polimer-vrn.ru/bitrix/php_interface/dbconn.local.php` + `bitrix/.settings.php`  
(старые креды закомментированы в файлах — для отката).

Конфиг remote-dev правится **только на сервере** (`dbconn.php` / `.settings.php` в каталоге dev). Prod не трогать.

```bash
# Локальный импорт (пример)
gunzip -c "~/Downloads/polimer_vrn_r_db (1).sql.gz" \
  | mysql -u root --max_allowed_packet=512M --force dev

# Прогресс локального импорта
tail -f .local/run/mysql-import-dev.log
mysql -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='dev'"
```

### Prod (не трогаем)

| | |
|---|---|
| Host | `localhost` (на сервере) |
| Schema | `polimer_vrn_r_db` |
| Логин | `polimer_mac` / `polimer_vrn__usr` (как в FastPanel) |

### Старая удалённая БД (fallback / откат локали)

| | |
|---|---|
| Host | `83.220.173.130` |
| Логин | `polimer_mac` |
| Схема | `polimer_vrn_r_db` |

```bash
./scripts/use-remote-db.sh            # вернуть удалённую БД в локальных конфигах
```

### Старая локальная БД (откат)

| | |
|---|---|
| Host | `127.0.0.1` |
| Логин | `polimer_local` / пароль `polimer_local` |
| Схема | `polimer_vrn_r_db` |
| Дамп | `polimer_vrn_r_db.sql` / `polimer_vrn_r_db-1.sql.gz` в корне проекта |

```bash
./scripts/setup-local-db.sh           # импорт старого дампа + переключение конфига
./scripts/setup-local-db.sh --background
tail -f .local/run/mysql-import.log
```

Конфиг: `bitrix/php_interface/dbconn.local.php` (локально) + `bitrix/.settings.php`.  
Резервная копия удалённого конфига: `.local/backup/`.  
Env: `.local/db.env` (активные значения + закомментированный откат).

## 5. Сервер и SSH

| | |
|---|---|
| IP | `83.220.173.130` |
| Панель | **FastPanel** |
| SSH-логин | **`root`** (не `polimer_mac`) |
| Git (GitHub) | https://github.com/bziksv/polimer (`dev` / `main`) |

### Пути на сервере

| Сайт | Домен | Каталог | Ветка git | Владелец файлов |
|---|---|---|---|---|
| Dev | `dev.polimer-vrn.ru` | `/var/www/polimer-vrn.ru/data/www/dev.polimer-vrn.ru` | `dev` | `root:root` |
| Prod | `polimer-vrn.ru` | `/var/www/polimer-vrn.ru/data/www/polimer-vrn.ru` | `main` | `polimer-vrn.ru:polimer-vrn.ru` |

`upload/` на dev — симлинк на `../polimer-vrn.ru/upload` (общая папка медиа).

### Вход с Mac без пароля

На Mac ключ: `~/.ssh/id_ed25519.pub` (тот же, что для GitHub).

**На сервере под root** (консоль FastPanel / VNC — не с Mac, пока IP в блоке):

```bash
mkdir -p /root/.ssh
chmod 700 /root/.ssh
echo 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIGYLkfOkq1DFP6vfJft/JT/4+U3ZM5lsrMLuHqtYSvKV stanislav-almamed-github' >> /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys

grep -q '^PubkeyAuthentication' /etc/ssh/sshd_config && \
  sed -i 's/^#\?PubkeyAuthentication.*/PubkeyAuthentication yes/' /etc/ssh/sshd_config || \
  echo 'PubkeyAuthentication yes' >> /etc/ssh/sshd_config
grep -q '^PermitRootLogin' /etc/ssh/sshd_config && \
  sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config || \
  echo 'PermitRootLogin prohibit-password' >> /etc/ssh/sshd_config
systemctl reload sshd
```

**На Mac:**

```bash
ssh root@83.220.173.130
```

`ssh-copy-id polimer_mac@...` **не использовать** — неверный пользователь и лишние попытки пароля → бан.

### Fail2ban и блокировки SSH

На сервере стоит **fail2ban** (jail `sshd`). При частых неудачных попытках IP режется **до пароля**:

```
kex_exchange_identification: Connection closed by remote host
```

**Что пробовали — не помогло сразу:**
- `systemctl reload sshd` — не снимает бан IP
- смена IP (раздача с телефона) — иногда тоже не помогает, если root/SSH временно в жёстком блоке или ключ ещё не в `/root/.ssh/`

**IP разработки (whitelist):**

| IP | Сеть |
|---|---|
| `31.210.223.140` | домашний Wi‑Fi |
| `185.117.0.100` | раздача / другая сеть |

На сервере под root:

```bash
fail2ban-client status sshd
fail2ban-client unban 31.210.223.140
fail2ban-client unban 185.117.0.100
fail2ban-client set sshd addignoreip 31.210.223.140
fail2ban-client set sshd addignoreip 185.117.0.100
```

Текущий внешний IP Mac: `curl ifconfig.me`

**Типичные ошибки:**
- ключ положили в `/.ssh/` вместо **`/root/.ssh/`** (когда `getent passwd polimer_mac` вернул пустой Home)
- `chown polimer_mac:polimer_mac` — пользователя не существует
- долбить SSH с Mac во время бана — бан продлевается

**FastPanel:** если SSH с Mac не пускает — зайти через **терминал/консоль в панели** (root), настроить ключ и fail2ban там. Перезагрузка только `sshd` бан не снимает.

## 6. Запуск локально

```bash
cd /Users/stanislav/Documents/projects/polimer
./scripts/start-dev.sh    # nginx + php-fpm 8.3
./scripts/stop-dev.sh     # остановка
```

| URL | Сайт |
|---|---|
| http://localhost:8084/ | dev.polimer-vrn.ru |

Проверка после запуска:

```bash
curl -sS -o /dev/null -w "dev HTTP %{http_code}\n" http://localhost:8084/
```

## 7. Порты других проектов (~/Documents/projects)

| Порт | Проект |
|---|---|
| 3002 | cabinet.titlo.ru |
| 8080 | almamed |
| 8082 | vilmed |
| 8083 | kosmamed |
| **8084** | **polimer dev** |

## 8. Git и деплой

| | |
|---|---|
| Репозиторий | https://github.com/bziksv/polimer |
| Remote (Mac) | `git@github.com:bziksv/polimer.git` (SSH) |
| Remote (сервер) | `https://github.com/bziksv/polimer.git` (HTTPS) |
| Git в проекте | `dev.polimer-vrn.ru/.git` |
| Ветка `dev` | разработка → dev-сервер |
| Ветка `main` | prod после согласования |

> **Важно:** старый репозиторий `neeil1990/polimer-vrn.ru` **снят с prod** (2026-07-14). Dev и prod используют только `bziksv/polimer`.

> **Правило выката:** при команде «выкати в гит на dev и/или prod» — **всё через git** (commit → push → pull на сервере). Запрещены `scp` / `rsync` / копирование отдельных файлов на сервер.

### Локально → dev

```bash
cd dev.polimer-vrn.ru
git add … && git commit -m "…"
git push origin dev
```

На сервере dev:

```bash
ssh root@83.220.173.130
cd /var/www/polimer-vrn.ru/data/www/dev.polimer-vrn.ru
git pull origin dev
rm -rf bitrix/cache/* bitrix/managed_cache/*
```

### dev → prod (после согласования клиентом)

**1. Обновить `main` на GitHub:**

```bash
cd dev.polimer-vrn.ru
git push origin dev:main
```

**2. Выкат на prod:**

```bash
ssh root@83.220.173.130
cd /var/www/polimer-vrn.ru/data/www/polimer-vrn.ru
git pull origin main
chown -R polimer-vrn.ru:polimer-vrn.ru .
rm -rf bitrix/cache/* bitrix/managed_cache/*
```

**3. Проверка:**

```bash
curl -sS -o /dev/null -w "prod HTTP %{http_code}\n" https://polimer-vrn.ru/
```

### Откат prod

```bash
cd /var/www/polimer-vrn.ru/data/www/polimer-vrn.ru
git log --oneline -5          # выбрать коммит
git reset --hard <commit>
chown -R polimer-vrn.ru:polimer-vrn.ru .
rm -rf bitrix/cache/* bitrix/managed_cache/*
```

Точка отката до миграции git (2026-07-14): `472856b`.

### Не в git (не коммитить)

| Путь | Почему |
|---|---|
| `bitrix/.settings.php` | конфиг БД/кэша, свой на каждом окружении |
| `bitrix/php_interface/dbconn.php` | конфиг БД |
| `bitrix/php_interface/recaptcha_secret.php` | Secret Key Google reCAPTCHA (см. `.example`) |
| `upload/` | медиа ~17 ГБ |
| `.htaccess` | серверные правила |

### Google reCAPTCHA

| | |
|---|---|
| Site Key (в git) | `POLIMER_RECAPTCHA_SITE_KEY` в `bitrix/php_interface/init.php` |
| Secret Key (не в git) | `bitrix/php_interface/recaptcha_secret.php` ← копия с `recaptcha_secret.php.example` |
| JS | `window.POLIMER_RECAPTCHA_SITEKEY` в `header.php` |
| Проверка | `polimerVerifyGoogleRecaptcha()` — siteverify Google; без Secret — только «токен не пустой» |

Актуальный Site Key (с 2026-07, ключ Дениса): `6LfAz1YtAAAAAMPXRZUxo38fvpz__MlOHs7DBA41`.

### Файлы раздела оплаты (в git)

| Путь | Содержание |
|---|---|
| `payment/about-sbp/` | информационная страница про СБП |
| `payment/about-split/` | информационная страница про Яндекс Сплит |
| `payment/.access.php` | права доступа Bitrix для раздела |

### Workflow (кратко)

1. Локально → commit/push в `dev`
2. `git pull origin dev` на dev-сервере
3. Клиент согласовал → `git push origin dev:main` → `git pull origin main` на prod

**Последний выкат prod:** `19844a8` — меню каталога V6, мобильные правки (2026-07-14).

## 9. Шаблоны и ключевые изменения

| Что | Где |
|---|---|
| Меню каталога **V6** (единственный вариант) | `bitrix/templates/main/components/bitrix/catalog.section.list/top-menu-catalog-v6/` |
| Подключение меню | `bitrix/templates/main/header.php` → шаблон `top-menu-catalog-v6` |
| Кастомные стили | `bitrix/templates/main/css/custom.css` |
| Мобильная вёрстка отзывов | `bitrix/templates/main/components/bitrix/news.list/reviews/style.css` |
| Публичные характеристики (карточка + сравнение) | `bitrix/php_interface/polimer_catalog_props.php` — все заполненные свойства, кроме Avito/Ozon/маркетплейс/служебных |

Переключатель вариантов меню (`catalog-menu-switcher`) и страница `/catalog-menu-preview/` **удалены** (2026-07-14).

## 10. Промпт описания товара (для клиента / AI)

Универсальный промпт (вместо старых двух: JSON + HTML):

[opisanie-tovara-universal.md](/Users/stanislav/Documents/projects/polimer/prompts/opisanie-tovara-universal.md)

Клиент заполняет блок **«ВХОДНЫЕ ДАННЫЕ»** в начале, отдаёт в DeepSeek/Qwen → на выходе HTML для `DETAIL_TEXT` в Bitrix. CSS-классы уже в `custom.css` (`.product-description`, `.specs-grid`, …).

## 11. Аудит картинок каталога

| | |
|---|---|
| URL (локально) | http://localhost:8084/tools/catalog-image-audit.php |
| CLI-сборка | `php tools/catalog-image-audit-build.php` (на сервере: `/opt/php82/bin/php -d short_open_tag=On tools/catalog-image-audit-build.php`) |
| Логика | `bitrix/php_interface/polimer_catalog_image_audit.php` |
| Кэш | `bitrix/cache/polimer/catalog_image_audit.json` (24 ч) |

На prod **не собирать отчёт через браузер в синхронном запросе** — nginx обрывает через ~60 с (504). Кнопка «Обновить отчёт» запускает фоновую CLI-сборку через `/opt/php82/bin/php` (не php-cgi).

Если зависло «Идёт пересборка» без прогресса:
```bash
ssh root@83.220.173.130
cd /var/www/polimer-vrn.ru/data/www/polimer-vrn.ru
rm -f bitrix/cache/polimer/catalog_image_audit.lock
nohup /opt/php82/bin/php -d short_open_tag=On tools/catalog-image-audit-build.php >> bitrix/cache/polimer/catalog_image_audit_build.log 2>&1 &
tail -f bitrix/cache/polimer/catalog_image_audit_build.log
```

Доступ: localhost; авторизованный пользователь из группы «Администраторы» или с правом редактирования каталога; либо `?token=...` (env `POLIMER_AUDIT_TOKEN`).

Приоритеты (сверху вниз): **срочно** (нет файла / <150px) → **очень важно** (<220px, экстремальные пропорции, большие отступы) → **важно** (letterbox, много пустого поля) → **средне** (отступы >8%).

Параметры: `?refresh=1` пересборка, `?format=json`, `?priority=high`, `?min_score=450`, `?q=название`.
