<?php

namespace App\Services\Activate;

use App\Dto\BotDto;
use App\Models\Activate\SmsCountry;
use App\Models\Rent\RentOrder;
use App\Services\External\SmsActivateApi;
use App\Services\MainService;
use RuntimeException;

class RentService extends MainService
{
    /**
     * формируем список стран
     *
     * @param BotDto $botDto
     * @return array
     */
    public function getRentCountries(BotDto $botDto)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->getRentServicesAndCountries();
        $countries = $resultRequest['countries'];

        $result = [];
        foreach ($countries as $country) {
            $smsCountry = SmsCountry::query()->where(['org_id' => $country])->first();

            array_push($result, [
                'id' => $smsCountry->org_id,
                'title_ru' => $smsCountry->name_ru,
                'title_eng' => $smsCountry->name_en,
                'image' => $smsCountry->image,
            ]);
        }

        return $result;
    }

    /**
     * формируем список сервисов
     *
     * @param BotDto $botDto
     * @param $country
     * @return array
     */
    public function getRentService(BotDto $botDto, $country)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->getRentServicesAndCountries($country);
        $services = $resultRequest['services'];

        $result = [];
        foreach ($services as $key => $service) {
            array_push($result, [
                'name' => $key,
                'count' => $service['quant']['total'],
                'cost' => $service['retail_cost'],
                'image' => 'https://smsactivate.s3.eu-central-1.amazonaws.com/assets/ico/' . $key . '0.webp',
            ]);
        }

        return $result;
    }

    /**
     * получить цену аренды отдельного сервиса
     *
     * @param BotDto $botDto
     * @param $country
     * @param $service
     * @return mixed
     */
    public function getPriceService(BotDto $botDto, $country, $service)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->getRentServicesAndCountries($country);
//        dd($resultRequest);
        if (!isset($resultRequest['services'][$service]))
            throw new RuntimeException('Сервис не указан или название неверно');
        $service = $resultRequest['services'][$service];
//        dd($service);
        $service_price = $service['retail_cost'];

        return $service_price;
    }

    /**
     * создание заказа на аренду
     *
     * @param BotDto $botDto
     * @param $service
     * @param $country
     * @param $time
     * @param array|null $userData
     * @param $url
     * @return array
     */
    public function create(BotDto $botDto, $service, $country, $time, array $userData = null, $url = 'https://activate.bot-t.com/updateSmsRent/')
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);
        $country = SmsCountry::query()->where(['org_id' => $country])->first();
        $orderAmount = $this->getPriceService($botDto, $country->org_id, $service);

        $resultRequest = $smsActivate->getRentNumber($service, $country->org_id, $time, $url);
        $end_time = strtotime($resultRequest['phone']['endDate']);

        $amountStart = intval(floatval($orderAmount) * 100);
        $amountFinal = $amountStart + ($amountStart * ($botDto->percent / 100));

        $data = [
            'bot_id' => $botDto->id,
            'user_id' => 1, //$user->id
            'service' => $service,
            'country_id' => $country->id,
            'org_id' => $resultRequest['phone']['id'],
            'phone' => $resultRequest['phone']['number'],
            'codes' => null,
            'status' => RentOrder::STATUS_WAIT_CODE,
            'start_time' => time(),
            'end_time' => $end_time,
            'operator' => null,
            'price_final' => $amountFinal,
            'price_start' => $amountStart,
        ];

        $rent_order = RentOrder::create($data);

        $responseData = [
            'id' => $rent_order->org_id,
            'phone' => $rent_order->phone,
            'time' => $rent_order->end_time,
            'status' => $rent_order->status,
            'codes' => null,
            'country' => $country->org_id,
            'service' => $rent_order->service,
            'cost' => $amountFinal
        ];

        return $responseData;
    }

    /**
     * Отмена аренды
     *
     * @param BotDto $botDto
     * @param RentOrder $rent_order
     * @param array|null $userData
     * @return mixed
     */
    public function cancel(BotDto $botDto, RentOrder $rent_order, array $userData = null)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $result = $smsActivate->setRentStatus($rent_order->org_id, RentOrder::ACCESS_CANCEL);

        $rent_order->status = RentOrder::STATUS_CANCEL;
        $rent_order->save();

        return $result;
    }

    /**
     * Успешно завершить аренду
     *
     * @param BotDto $botDto
     * @param RentOrder $rent_order
     * @param array|null $userData
     * @return false|mixed|string
     */
    public function confirm(BotDto $botDto, RentOrder $rent_order, array $userData = null)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $result = $smsActivate->setRentStatus($rent_order->org_id, RentOrder::ACCESS_FINISH);

        $rent_order->status = RentOrder::STATUS_FINISH;
        $rent_order->save();

        return $result;
    }

    //получение статуса аренды
    public function getStatus(BotDto $botDto, $org_id)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->getRentStatus($org_id);

        dd($resultRequest);
    }

    //изменение статсуса аренды
    public function setStatus(BotDto $botDto)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->setRentStatus();
    }

    //получение списка текущих активаций
    public function getRentList(BotDto $botDto)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->getRentList();
    }

    //разобраться для всех ли аренд работает?

    /**
     * цена продления аренды
     *
     * @param BotDto $botDto
     * @param RentOrder $rent_order
     * @param $time
     * @return float|int
     */
    public function priceContinue(BotDto $botDto, RentOrder $rent_order, $time = 4)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->getContinueRentPriceNumber($rent_order->org_id, $time);
        $requestAmount = $resultRequest['price'];

        $amountStart = intval(floatval($requestAmount) * 100);
        $amountFinal = $amountStart + ($amountStart * ($botDto->percent / 100));

        return $amountFinal;
    }

    //продление срока аренды
    public function continueRent(BotDto $botDto)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $resultRequest = $smsActivate->continueRentNumber();
    }

    public function updateSms(array $hook_rent)
    {
        $rent_org_id = $hook_rent['rentId'];
        $codes = $hook_rent['sms']['text'];
        $codes_date = $hook_rent['sms']['date'];
        $codes_id = $hook_rent['sms']['smsId'];

        $rentOrder = RentOrder::query()->where(['org_id' => $rent_org_id])->first();

//        $str_code = 'Ваш код подтверждения: 107-981. Наберите его в поле ввода.';
        $codes = explode(' ', $codes);
        $codes = $codes[3];
        $update_codes = $rentOrder->codes . ' ' . $codes;
//        print_r($codes);

        $rentOrder->codes = $update_codes;
        $rentOrder->codes_id = $codes_id;
        $rentOrder->codes_date = $codes_date;

        $rentOrder->save();
    }
}
