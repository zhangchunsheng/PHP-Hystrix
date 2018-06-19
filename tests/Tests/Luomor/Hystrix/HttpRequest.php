<?php
/**
 * Created by PhpStorm.
 * User: peterzhang
 * Date: 19/06/2018
 * Time: 10:30 AM
 */
namespace Tests\Luomor\Hystrix;

use Luomor\Hystrix\AbstractCommand;
use Luomor\Hystrix\Exception\BadRequestException;

class HttpRequest extends AbstractCommand {
    public $url = "http://base.lan-tc.yongche.org/api/dict/getDictData";
    public $params = array(
        "dict_category_id" => 1
    );

    protected function run() {
        $url = $this->url . '?' . http_build_query($this->params);

        $ch = curl_init();

        try {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

            $result = curl_exec($ch);

            if(curl_error($ch)) {
                $errMsg = curl_error($ch);
                throw new \Exception($errMsg);
            }

            curl_close($ch);
        } catch(\Exception $e) {
            curl_close($ch);
            throw $e;
        }

        if(empty($result)) {
            throw new \Exception('not result');
        }

        $arrResult = json_decode($result, true);
        $jsonError = json_last_error();

        if($jsonError == JSON_ERROR_NONE) {
            return $arrResult;
        } else {
            throw new \Exception('json decode error');
        }
    }

    protected function getFallback() {
        return "fallback";
    }

    protected function getCacheKey() {
        $url = $this->url . '?' . http_build_query($this->params);
        return md5($url);
    }
}