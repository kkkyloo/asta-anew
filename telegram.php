<?php
session_start();

// Путь к файлу для хранения данных
$counterFile = 'form_counter/form_counter.txt';

// Проверка, существует ли файл. Если нет, создаем его.
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, serialize(array('counters' => array(), 'timestamps' => array())));
}

// Чтение текущего значения счетчика и временных меток из файла
$data = unserialize(file_get_contents($counterFile));
$ipCounters = $data['counters'];
$timestamps = $data['timestamps'];

// Получаем IP-адрес текущего пользователя
$ipAddress = $_SERVER['REMOTE_ADDR'];

// Проверка, существует ли сессионная переменная для подсчета форм
if (!isset($_SESSION['form_count'])) {
    $_SESSION['form_count'] = 0;
}

// Проверка, не превышен ли лимит форм в день и для текущего IP-адреса
if ($_SESSION['form_count'] < 4 && (!isset($ipCounters[$ipAddress]) || $ipCounters[$ipAddress] < 4)) {
    // Обработка формы
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $message = $_POST['message'];
        $sel = $_POST['sel'];

        $config = include 'config.php';

        $token = $config['token'];
        $chat_id = $config['chat_id'];

        $wp = "-";
        $tg = "-";
        $wp_text = "";
        $tg_text = "";



        $phone = str_replace(['(', ')', ' ', '-', '+'], '', $phone);
        $phone = urlencode($phone);

        if ($sel == 1) {
            $sel = "Позвонить по номеру телефона";

            if (substr($phone, 0, 1) != '8') {
                $phone = "%2B" . $phone;
            }


        } elseif ($sel == 2) {
            $sel = "Написать в WhatsApp";
            $wb_text = "Ссылка на ватсап: ";

            if (substr($phone, 0, 1) === '8') {
                $wp_phone = $phone;
                $wp_phone = '7' . substr($wp_phone, 1);
                $wp = "https://api.whatsapp.com/send?phone=" . $wp_phone;
            } else {
                $wp = "https://api.whatsapp.com/send?phone=" . $phone;
                $phone = "%2B" . $phone;
            }

        } elseif ($sel == 3) {
            $sel = "Написать в Telegram";

            $tg_text = "Ссылка на телеграм: (Может не работать, если человек запретил доступ по ссылке. Тогда добавить в контакты в телефоне и связаться)";

            if (substr($phone, 0, 1) === '8') {
                $tg_phone = $phone;
                $tg_phone = '7' . substr($tg_phone, 1);
                $tg = "https://t.me/%2B" . $tg_phone;

            } else {
                $phone = "%2B" . $phone;
                $tg = "https://t.me/" . $phone;
            }

        }

        $arr = array(
            'Заявка с сайта:' => '',
            '-----' => "",
            'Введенное имя: ' => $name,
            'Номер телефона: ' => $phone,
            'Сообщение: ' => $message,
            'Способ свзяи: ' => $sel,
            $wb_text => $wp,
            $tg_text => $tg
        );

        foreach ($arr as $key => $value) {
            $txt .= "<b>" . $key . "</b> " . $value . "%0A";
        }

        $sendToTelegram = fopen("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&parse_mode=html&text={$txt}", "r");

        $_SESSION['form_count']++;

        // Увеличение счетчика форм для текущего IP
        $ipCounters[$ipAddress] = isset($ipCounters[$ipAddress]) ? $ipCounters[$ipAddress] + 1 : 1;
        // Сохранение временной метки для текущего IP
        $timestamps[$ipAddress] = time();

        // Запись новых значений счетчика и временных меток в файл
        file_put_contents($counterFile, serialize(array('counters' => $ipCounters, 'timestamps' => $timestamps)));

        if ($sendToTelegram) {
            $response = array('status' => 'success', 'message' => 'Форма успешно отправлена! Скоро наши менеджеры свяжутся с вами');
        } else {
            $response = array('status' => 'error', 'message' => 'Ошибка отправки формы. Свяжитесь с нами по телефону или через телеграм бота');
        }

        echo json_encode($response);

    }
} else {
    // Проверка, прошло ли 24 часа с момента последней отправки формы для текущего IP
    $lastTimestamp = isset($timestamps[$ipAddress]) ? $timestamps[$ipAddress] : 0;
    $timeElapsed = time() - $lastTimestamp;

    if ($timeElapsed >= 24 * 60 * 60) {
        // Если прошло более 24 часов, сбросить счетчики
        $_SESSION['form_count'] = 0;
        $ipCounters[$ipAddress] = 0;
        $timestamps[$ipAddress] = 0;

        // Запись новых значений счетчика и временных меток в файл
        file_put_contents($counterFile, serialize(array('counters' => $ipCounters, 'timestamps' => $timestamps)));

        $response = array('status' => 'success2', 'message' => 'После привышения лимита отпарвки формы прошло более 24 часов. Нажмите кнопку отправить снова, чтобы наши менеджеры получили ваше сообщение');
        echo json_encode($response);
    } else {
        $response = array('status' => 'success2', 'message' => 'Превышен лимит отправки форм в день. Пожалуйста, подождите.');
        echo json_encode($response);
    }
}
?>
