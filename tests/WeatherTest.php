<?php
/**
 * Created by PhpStorm.
 * User: songzw
 * Date: 2018/9/15
 * Time: 15:19
 */
namespace Winner\Weather\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery\Matcher\AnyArgs;
use PHPUnit\Framework\TestCase;
use Winner\Weather\Exceptions\HttpException;
use Winner\Weather\Exceptions\InvalidArgumentException;
use Winner\Weather\Weather;

class WeatherTest extends TestCase

{
    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather('mock-key');

        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid type value(base/all): foo'
        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $w->getWeather('深圳', 'foo');

        $this->fail('Faild to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithInvalidFormat()
    {
        {
            $w = new Weather('mock-key');

            // 断言会抛出此异常类
            $this->expectException(InvalidArgumentException::class);

            // 断言异常消息为 'Invalid response format: array'
            $this->expectExceptionMessage('Invalid response format: array');

            // 因为支持的格式为 xml/json，所以传入 array 会抛出异常
            $w->getWeather('深圳', 'base', 'array');

            // 如果没有抛出异常，就会运行到这行，标记当前测试没成功
            $this->fail('Faild to assert getWeather throw exception with invalid argument.');
        }

    }

    public function testGetWeather()
    {
        $response = new Response(200, [], '{"success":true}');
        $client = new \Mockery(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'output' => 'json',
                'extensions' => 'base',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);
        $this->assertSame(['success' => true], $w->getWeather('深圳'));
        $response = new Response(200, [], '<hello>content</hello>');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '深圳',
                'extensions' => 'all',
                'output' => 'xml',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('深圳', 'all', 'xml'));

    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs()) // 由于上面的用例已经验证过参数传递，所以这里就不关心参数了。
            ->andThrow(new \Exception('request timeout')); // 当调用 get 方法时会抛出异常。

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        // 接着需要断言调用时会产生异常。
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('深圳');
    }
}