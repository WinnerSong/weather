<?php
/**
 * Created by PhpStorm.
 * User: songzw
 * Date: 2018/9/14
 * Time: 00:48
 */

namespace Winner\Weather;


use GuzzleHttp\Client;
use Winner\Weather\Exceptions\HttpException;
use Winner\Weather\Exceptions\InvalidArgumentException;

class Weather
{
    protected $key = null;
    protected $guzzleOptions = [];

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function getUsers()
    {

    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    public function getWeather($city, string $type = 'base', string $format = 'json')
    {
        $url = 'https://restapi.amap.com/v3/weather/weatherInfo';
        if (!in_array($format, ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format:'.$format);
        }
        if (!in_array($type, ['base', 'all'])) {
            throw new InvalidArgumentException('Invalid type value(base/all):'.$type);
        }
        $query = array_filter([
            'key' => $this->key,
            'city' => $city,
            'output' => $format,
            'extensions' => $type
        ]);
        try {

            $response = $this->getHttpClient()->get($url, ['query' => $query])->getBody()->getContents();
            return $format === 'json' ? json_decode($response, true) : $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(),$e);
        }

    }
}