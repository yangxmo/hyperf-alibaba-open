<?php


namespace JavaReact\AlibabaOpen\core;

use JavaReact\AlibabaOpen\tools\Guzzle;

/**
 * Class BaseClient
 * @package JavaReact\AlibabaOpen\core
 * @property \JavaReact\AlibabaOpen\AlibabaClient app
 */
class BaseClient
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @var string
     */
    public $base_url = 'http://gw.open.1688.com';

    /**
     * @var
     */
    public $url_info;

    /**
     * @var
     */
    protected $postData;

    /**
     * @var
     */
    public $res_url;

    /**
     * BaseClient constructor.
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }


    /**
     * 签名
     * @param $method
     * @throws Exception
     */
    public function sign($method)
    {
        $method = strtolower($method);
        if (empty($this->url_info)) {
            throw new Exception('url因子为空，如无配置，请配置');
        }
        $arr       = explode(':', $this->url_info);
        $spacename = $arr[0];
        $arr       = explode('-', $arr[1]);
        $version   = $arr[1];
        $apiname   = $arr[0];
        $url_info  = 'param2/' . $version . '/' . $spacename . '/' . $apiname . '/';
        //参数因子
        $appKey    = $this->app->appkey;
        $appSecret = $this->app->appsecret;
        $apiInfo   = $url_info . $appKey;//此处请用具体api进行替换
        $params    = $this->app->params;
        if ($params) {
            foreach ($params as &$param) {
                $param = is_string($param) ? $param : json_encode($param, JSON_UNESCAPED_UNICODE);
            }
        }
        //配置参数，请用apiInfo对应的api参数进行替换
        $code_arr  = array_merge([
            'access_token' => $this->app->access_token
        ], $params);
        $aliParams = array();
        $url_pin   = '';
        foreach ($code_arr as $key => $val) {
            $url_pin     .= $key . '=' . $val . '&';
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $apiInfo . $sign_str;
        //签名
        $code_sign      = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
        $this->postData = $code_arr;
        $this->postData['_aop_signature'] = $code_sign;

        $this->res_url = 'openapi/' . $apiInfo;

//        if ($method == 'get') {
//            $this->res_url = 'openapi/' . $apiInfo; // . '?' . $url_pin . '_aop_signature=' . $code_sign;
//        } else {
//            $this->res_url = 'openapi/' . $apiInfo; // . '?_aop_signature=' . $code_sign;
//        }
    }

    /**
     * GET请求方式
     * @return array
     * @throws Exception
     */
    public function get()
    {
        $this->sign('get');

        return $this->curlRequest($this->res_url, $this->postData, 'get');
    }

    /**
     * POST请求方式
     * @throws array|Exception
     */
    public function post()
    {
        $this->sign('post');
        return $this->curlRequest($this->res_url, $this->postData, 'post');
    }

    /**
     * 设置API地址
     * @param string $comalibabatradealibabatradegetbuyerView
     * @return $this
     */
    public function setApi(string $comalibabatradealibabatradegetbuyerView)
    {
        $this->url_info = $comalibabatradealibabatradegetbuyerView;
        return $this;
    }

    /**
     * curl 请求
     * @param string $url
     * @param array $data
     * @param string $method
     * @param int $timeout
     * @return array
     */
    public function curlRequest(string $url,array $data,string $method = 'get', int $timeout = 10): array
    {
        /** @var Guzzle $client */
        $client = \Hyperf\Support\make(Guzzle::class);

        $client->setHttpHandle(
            [
                'base_uri' => $this->base_url,
                'timeout' => $timeout,
            ]);

        $method = 'send' . ucfirst($method);

        return $client->$method($url, $data);
    }

}
