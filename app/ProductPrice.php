<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Ixudra\Curl\Facades\Curl;

class ProductPrice extends Model
{
    public static function getBySku($sku)
    {
        try {

            $response = Curl::to('https://b2b-sandi.com.ua/api/price-center')
                ->withData([
                    'action' => 'get_ir_prices',
                    'sku_list' => $sku
                ])->post();

            return json_decode($response, true);

        } catch (\Exception $e) {

            return null;

        }
    }
}
