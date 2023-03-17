<?php

namespace App\Helpers;

class OrdersHelper
{
    /**
     * @param $result
     * @return false|mixed
     */
    public static function requestArray($result)
    {
        $errorCodes = [
            'STATUS_OK' => 7,
            'ACCESS_ACTIVATION' => 6, //Сервис успешно активирован
            'ACCESS_CANCEL' => 8, //Активация отменена
            'ACCESS_READY' => 3, //Ожидание нового смс
            'ACCESS_RETRY_GET' => 1, //Готовность номера подтверждена
            'ACCOUNT_INACTIVE' => 'Свободных номеров нет',
            'ALREADY_FINISH' => 'Аренда уже завершена',
            'ALREADY_CANCEL' => 'Аренда уже отменена',
            'BAD_ACTION' => 16, //'Некорректное действие (параметр action)',
            'BAD_SERVICE' => 'Некорректное наименование сервиса (параметр service)',
            'BAD_KEY' => 15, //'Неверный API ключ доступа',
            'BAD_STATUS' => 'Попытка установить несуществующий статус',
            'BANNED' => 17, //'Аккаунт заблокирован',
            'CANT_CANCEL' => 'Невозможно отменить аренду (прошло более 20 мин.)',
            'ERROR_SQL' => 'Один из параметров имеет недопустимое значение',
            'NO_NUMBERS' => 2, //'Нет свободных номеров для приёма смс от текущего сервиса',
            'NO_BALANCE' => 10 , //'Закончился баланс'
            'NO_YULA_MAIL' => 'Необходимо иметь на счету более 500 рублей для покупки сервисов холдинга Mail.ru и Mamba',
            'NO_CONNECTION' => 11, //'Нет соединения с серверами sms-activate',
            'NO_ID_RENT' => 'Не указан id аренды',
            'NO_ACTIVATION' => 12, //'Указанного id активации не существует',
            'STATUS_CANCEL' => 9, //'Активация/аренда отменена',
            'STATUS_FINISH' => 'Аренда оплачена и завершена',
            'STATUS_WAIT_CODE' => 4, //Ожидание первой смс
            'STATUS_WAIT_RETRY' => 5, //Ожидание уточнения кода,
            'SQL_ERROR' => 13, //'Один из параметров имеет недопустимое значение',
            'INVALID_PHONE' => 'Номер арендован не вами (неправильный id аренды)',
            'INCORECT_STATUS' => 'Отсутствует или неправильно указан статус',
            'WRONG_SERVICE' => 'Сервис не поддерживает переадресацию',
            'WRONG_SECURITY' => 14, //'Ошибка при попытке передать ID активации без переадресации, или же завершенной/не активной активации'
        ];

        if (array_key_exists($result, $errorCodes)) {
            return $errorCodes[$result];
        } else {
            return false;
        }
    }
}
