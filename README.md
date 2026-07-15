# Yandex Alice Skill: Order Vehicle Permits via MAX Messenger

Навык для Алисы, позволяющий заказывать пропуска на въезд транспортных средств через голосовые или текстовые команды на любой «поверхности» Яндекса (колонка, приложение, чат и т. д.) с отправкой уведомления в мессенджер MAX (служба охраны).

[![Алиса это умеет](https://dialogs.s3.yandex.net/badges/v1-term1.svg)](https://dialogs.yandex.ru/store/skills/22302add-zakazat-propusk?utm_source=site&utm_medium=badge&utm_campaign=v1&utm_term=d1 "Перейти к навыку в каталоге")

## 🎯 Назначение и основная идея

Навык решает задачу быстрого и удобного заказа пропуска без необходимости вручную звонить или писать в чат охраны. Пользователь (владелец участка/жилец) говорит Алисе простую команду — навык валидирует запрос, по `user_id` находит номер участка в файле `users.csv` и отправляет структурированное сообщение в мессенджер MAX. В результате охрана получает чёткую заявку: кто, когда, на каком авто, какой участок.

Идея в том, чтобы сделать процесс максимально простым: достаточно одной фразы вроде «Алиса, попроси заказать пропуск на Газель 556» — и охране уже приходит готовое оповещение.

## 💡 Ключевые возможности

- **Голосовой и текстовый заказ пропуска**: работает на любой «поверхности» Яндекса — колонке, в мобильном приложении, в чате.
- **Привязка участка к пользователю**: соответствие `userId → участок` хранится в файле `users.csv` (две колонки: `userId`, `location`). Поиск участка выполняется по `session.user.user_id` из события Алисы.
- **Структурированное сообщение для охраны**: в MAX отправляется сообщение с полями: `user_id`, `участок`, `марка/модель авто`, `госномер`, `время прибытия`, `ФИО водителя`. Пример итогового сообщения для охраны: «Дом <НОМЕР УЧАСТКА>: газель 5 5 6».
- **Обработка ошибок и валидация**: если пользователь не найден в `users.csv`, либо не указаны обязательные данные (например, госномер), навык вежливо просит уточнить информацию.
- **Логирование запросов**: каждый вызов облачной функции фиксируется в логах (для отладки и аудита).

## 🧠 Архитектура решения

1. **Навык Алисы** — принимает голосовые/текстовые команды, извлекает сущности (данные авто, время) и передаёт событие в облачную функцию.
2. **Облачная функция (Yandex Cloud, PHP 8.2)** — читает `users.csv`, по `user_id` определяет участок, формирует JSON‑структуру и отправляет POST‑запрос к API MAX.
3. **API MAX (мессенджер охраны)** — принимает сообщение и доставляет его оператору охраны.
4. **Конфигурация**: список привязок `userId → location` хранится в файле `users.csv`. Файл загружается вместе с кодом функции в Yandex Cloud Functions.

## 🛠 Технологии и стек

- **Язык**: PHP 8.2 (облачная функция Yandex Cloud).
- **Платформа**: Yandex Dialogs (навыки Алисы), Yandex Cloud Functions.
- **Протокол**: HTTP(S) POST‑запросы к API MAX.
- **Формат данных**: JSON (запрос/ответ в стиле, совместимом с Python‑структурами).
- **Хранение конфигурации**: файл `users.csv` со списком `userId → location`.

## 🚀 Как использовать (для соседей/пользователей)

1. Активируйте навык в приложении Алисы: [активировать навык](https://dialogs.yandex.ru/store/skills/22302add-zakazat-propusk/activate?deeplink=true).
2. Произнесите команду, например:
   - «Алиса, закажи пропуск для машины на мой участок, приедет в 14:30, госномер А123АА, водитель Иванов Иван».
   - Или максимально просто: «Алиса, попроси заказать пропуск на Газель 556».
3. Навык отправит заявку в MAX, а вы получите подтверждение: «Пропуск заказан. Участок X. Время: 14:30».

> **Важно:** Для доступа к навыку требуется предварительная активация устройства (привязка к участку).

---

## 🤝 Как получить доступ к навыку (для пользователей)

Навык приватный: доступ предоставляется только после проверки.

Чтобы активировать устройство (колонку/приложение) и привязать его к участку:

1. Получите временный код устройства вида `XXX-XXX`.
2. Отправьте этот код и номер вашего участка администратору (автору навыка) в личные сообщения.
3. После проверки (для исключения доступа третьих лиц) устройство будет активировано, а привязка `userId → участок` добавлена в `users.csv`.

---

## 🎁 Поддержка проекта

Буду благодарен за поддержку проекта — донат поможет покрыть расходы на облачные ресурсы и развитие функционала.

[Поддержать проект (донат)](https://dialogs.yandex.ru/store/skills/22302add-zakazat-propusk?action=donation)

Также буду признателен, если поставите навыку 5 звёзд в каталоге — это помогает в его продвижении.

---

## ⚙️ Настройка и развёртывание (для разработчика)

Для развёртывания проекта нужно выполнить стандартные шаги настройки облачной функции и связать её с навыком в консоли Яндекс Диалогов.

Требуется настроить переменные окружения в Yandex Cloud Function:
- `MAX_API_URL` — URL API мессенджера MAX.
- `MAX_API_TOKEN` — токен авторизации для вызова API.

Убедитесь, что файл `users.csv` находится в корне пакета, загружаемого в облачную функцию, и имеет формат с двумя колонками: `userId` и `location`.

В консоли Яндекс Диалогов привяжите облачную функцию к навыку и протестируйте работу через интерфейс тестирования навыков либо голосом на Станции.

### Важное про работу с users.csv

- Файл `users.csv` читается внутри облачной функции — в зависимости от реализации это может происходить при каждом вызове либо один раз при инициализации.
- Ключом для поиска участка служит `session.user.user_id` из входящего события Алисы.
- Если `user_id` не найден в файле, функция возвращает пользователю понятный ответ с просьбой проверить доступ или обратиться к администратору.

---

## ⚠️ Важные замечания

- **Безопасность**: токены и чувствительные данные не храните в коде — только в переменных окружения.
- **Приватность навыка**: навык приватный — доступ к нему получают только те пользователи, которым вы дали ссылку на активацию.
- **Идентификация пользователя**: в событии Алисы передаётся `session.user.user_id` — он используется как ключ для поиска строки в `users.csv`.
- **Лимиты и отказоустойчивость**: учитывайте лимиты вызовов API MAX и добавляйте повторные попытки (retry) при временных сбоях.
- **Обновление списка участков**: для добавления или изменения привязки пользователей достаточно обновить файл `users.csv` и заново развернуть облачную функцию (либо предусмотреть механизм горячей перезагрузки, если требуется).

---

# Yandex Alice Skill: Order Vehicle Permits via MAX Messenger (EN)

An Alice skill that allows users to order vehicle entry permits using voice or text commands on any Yandex “surface” (speaker, app, chat, etc.), with notifications sent to the MAX messenger (security team).

[![Alice can do it](https://dialogs.s3.yandex.net/badges/v1-term1.svg)](https://dialogs.yandex.ru/store/skills/22302add-zakazat-propusk?utm_source=site&utm_medium=badge&utm_campaign=v1&utm_term=d1 "Go to the skill in the catalog")

## Purpose

The skill simplifies the process of ordering a vehicle permit without manual calls or chat messages. The user speaks a simple voice command to Alice; the skill validates the request, looks up the plot/section number from `users.csv` by `user_id`, and sends a structured message to the MAX messenger. As a result, security receives a clear request: who, when, what car, which plot.

The goal is to make the process as simple as possible: a single phrase like “Alice, ask to order a permit for GAZel 556” is enough for security to receive a ready-made alert.

## Key Features

- **Voice and text-based permit ordering**: works on any Yandex surface — speaker, mobile app, chat.
- **User-to-plot mapping**: the `userId → plot` mapping is stored in `users.csv` (two columns: `userId`, `location`). The plot is resolved using `session.user.user_id` from the Alice event.
- **Structured message for security**: MAX receives a message with fields: `user_id`, `plot`, `car make/model`, `license plate`, `arrival time`, `driver name`. Example final message for security: “House <PLOT_NUMBER>: GAZel 5 5 6”.
- **Error handling & validation**: if the user is not found in `users.csv` or required data (e.g., license plate) is missing, the skill politely asks for clarification.
- **Request logging**: every cloud function invocation is logged for debugging and auditing.

## Architecture

1. **Alice Skill** — accepts voice/text commands, extracts entities (car details, time), and passes the event to the cloud function.
2. **Cloud Function (Yandex Cloud, PHP 8.2)** — reads `users.csv`, resolves the plot by `user_id`, builds a JSON structure, and sends a POST request to the MAX API.
3. **MAX API (messenger for security)** — receives the message and delivers it to the security operator.
4. **Configuration**: the `userId → location` mapping is stored in `users.csv`, deployed together with the function code.

## Technologies & Stack

- **Language**: PHP 8.2 (Yandex Cloud Function).
- **Platform**: Yandex Dialogs (Alice skills), Yandex Cloud Functions.
- **Protocol**: HTTP(S) POST requests to MAX API.
- **Data Format**: JSON (request/response compatible with Python-style structures).
- **Configuration Storage**: `users.csv` file with `userId → location` mappings.

## How to Use (for neighbors/users)

1. Activate the skill in the Alice app: [activate skill](https://dialogs.yandex.ru/store/skills/22302add-zakazat-propusk/activate?deeplink=true).
2. Say a command, for example:
   - “Alice, order a permit for a car to my plot, arriving at 14:30, license plate A123AA, driver Ivanov Ivan.”
   - Or simply: “Alice, ask to order a permit for GAZel 556.”
3. The skill will send the request to MAX and you’ll get a confirmation: “Permit ordered. Plot X. Time: 14:30.”

> **Important**: Access to the skill requires prior device activation (plot binding).

## User Access Procedure (for users)

The skill is private: access is granted only after verification.

To activate your device (speaker/app) and bind it to a plot:

1. Get a temporary device code in the format `XXX-XXX`.
2. Send this code and your plot number to the administrator (skill author) via private message.
3. After verification (to prevent unauthorized third-party access), the device will be activated, and the `userId → plot` binding will be added to `users.csv`.

## Project Support

I’d appreciate any support for the project — donations help cover cloud costs and further development.

[Support the project (donation)](https://dialogs.yandex.ru/store/skills/22302add-zakazat-propusk?action=donation)

Also, I’d be grateful if you could rate the skill with 5 stars in the catalog — this helps with its visibility.

## Setup & Deployment (Developer)

Deploy the project by configuring the cloud function in Yandex Cloud and linking it to the skill in the Yandex Dialogs console.

Set the following environment variables in Yandex Cloud Function:
- `MAX_API_URL` — MAX messenger API URL.
- `MAX_API_TOKEN` — authorization token for API calls.

Ensure `users.csv` is in the root of the deployment package with the format: two columns `userId` and `location`.

In the Yandex Dialogs console, link the cloud function to the skill and test via the skill testing interface or by voice on a Station.

## User-to-Plot Mapping Strategy

The mapping is stored in `users.csv`. The cloud function uses `session.user.user_id` as the lookup key. If the `user_id` is not found, the function returns a user-friendly response asking to verify access or contact the administrator. To update user mappings, edit `users.csv` and redeploy the function (or implement a hot-reload mechanism if needed).

## Important Notes

- **Security**: never store tokens or sensitive data in code; use environment variables only.
- **Private Skill**: the skill is private — only users with the activation link can access it.
- **User Identification**: the Alice event includes `session.user.user_id`; use it as the key to look up the row in `users.csv`.
- **Rate Limits & Resilience**: consider MAX API rate limits and implement retry logic for transient failures.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you’d like to implement.

## License

This project is licensed under the MIT License.
