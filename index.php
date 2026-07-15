<?php

declare(strict_types=1);

/**
 * Точка входа для облачной функции (совместимо с рантаймом Yandex Cloud / навыком Алисы)
 *
 * @param array  $event   Входящие данные от Алисы
 * @param object $context Контекст выполнения (может использоваться для логов, таймаутов и т.п.)
 * @return array          Ответ в формате, аналогичном твоему Python-примеру
 */
function handler(array $event, $context): array
{
    $version    = $event['version'] ?? '1.0';
    $session    = $event['session'] ?? [];
    $userId     = $session['user_id'] ?? null;
    $endSession = true;
    $house      = '';
    $text       = '';
    $userText   = '';

    try {
        if ($userId === null) {
            throw new \LogicException('Не удалось определить пользователя.');
        }
        $sites = getUserSites();
        $isUnknown = !isset($sites[$userId]);
        $house = $sites[$userId] ?? '';
        if (!isset($event['request'])) {
            throw new \LogicException('Ошибка: нет данных запроса.');
        }   
        $req = $event['request'];
        $userText = trim(mb_strtolower($req['command'] ?? ''));
        if (empty($userText)) {
            $userText = trim(mb_strtolower($req['original_utterance'] ?? ''));
        }
        if (str_contains($userText, 'хватит') || str_contains($userText, 'стоп') || str_contains($userText, 'stop')) {
            throw new \LogicException('До свидания.');
        }
        $welcomeMessage = "Я помогаю заказывать пропуска на въезд автотранспорта через КПП в охране СНТ Южное (Москва, Троицк, квартал № 86).\n".
            "Если нужна помощь, скажите «Помощь» либо пришлите марку, цвет и госномер автотранспортного средства.";
        if ((
                empty($userText) && ($session['new'] ?? false)
            ) 
            || str_contains($userText, 'помощь') 
            || str_contains($userText, 'что ты умеешь')
        ) {
            $endSession = false;
            if ($isUnknown) {
                $code = generatePassCode();
                error_log("NEW USER: {$code}, {$userId}");
                $welcomeMessage .= "\nВы не авторизованы для заказа пропусков! Обратитесь к администратору и сообщите ему код {$code} и номер участка!";
            } else {
                $welcomeMessage .= "\nВаш участок (№ {$house}) уже привязан администратором, пришлите марку, цвет и госномер автотранспортного средства.";
            }
            $welcomeMessage .= "\nЧтобы выйти из навыка, скажите: «Хватит».";
            throw new \LogicException($welcomeMessage);
        }

        if ($isUnknown) {
            $code = generatePassCode();
            error_log("NEW USER: {$code}, {$userId}");
            throw new \LogicException("Вы не авторизованы для заказа пропусков! Обратитесь к администратору и сообщите ему код {$code} и номер участка!");
        }
        if (empty($userText)) {
            $endSession = false;
            throw new \LogicException('Пожалуйста, сообщите, на какой автомобиль нужно заказать пропуск.');
        }
        $sendResult = sendMessageToMax($userText, $userId, $house);
        if ($sendResult['success']) {
            $text = "Пропуск успешно заказан на участок {$house}";
        } else {
            $err = $sendResult['error'] ?? 'Неизвестная ошибка';
            $text = "Не удалось отправить сообщение охране: {$err}";
        }
    }
    catch(\Throwable $e) {
        $text = $e->getMessage();
    }
    return [
        'version'  => $version,
        'session'  => $session,
        'response' => [
            'text'        => $text,
            'end_session' => $endSession,
        ],
    ];
}

function generatePassCode(): string
{
    $part1 = random_int(100, 999);
    $part2 = random_int(100, 999);
    return "{$part1}-{$part2}";
}

function getUserSites(): array
{
    $filePath = __DIR__ . '/users.csv'; // Файл должен быть в архиве с функцией
    $sites = [];

    if (!is_readable($filePath)) {
        error_log("ERROR: CSV file not found or not readable: $filePath");
        return $sites;
    }

    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return $sites;
    }

    $firstRow = true;

    while (($row = fgetcsv($handle)) !== false) {
        // Пропуск заголовка
        if ($firstRow) {
            $firstRow = false;
            continue;
        }

        // Защита от пустых строк
        if (count($row) < 2 || empty($row[0])) {
            continue;
        }

        $userId = trim($row[0]);
        $siteNumber = trim($row[1]);

        // Если вдруг в первой ячейке остался BOM (редко, но бывает при ручном сохранении)
        $userId = preg_replace('/^[\xEF\xBB\xBF]/u', '', $userId);

        if ($userId && $siteNumber) {
            $sites[$userId] = $siteNumber;
        }
    }

    fclose($handle);
    return $sites;
}

/**
 * Отправка сообщения в API мессенджера MAX
 */
function sendMessageToMax(string $text, string $userId, string $house): array
{
    echo "Дом {$house}: {$text}\n";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "https://platform-api2.max.ru/messages?user_id=" . getenv('MAX_USER_ID'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['text'    => 'Дом ' . $house . ': ' . $text], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . getenv('MAX_TOKEN'),
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($err !== '') {
        return ['success' => false, 'error' => 'cURL error: ' . $err];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'success' => false,
            'error'   => "HTTP {$httpCode}",
            'debug'   => $body,
        ];
    }

    return ['success' => true];
}

