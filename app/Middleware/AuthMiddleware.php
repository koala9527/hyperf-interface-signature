<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Middleware;

use App\Exception\BusinessException;
use App\Tool\Token;
use Hyperf\Utils\context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $request->hasHeader('sign') || ! $request->hasHeader('time-stamp') || ! $request->hasHeader('nonce-str')) {
            throw new BusinessException(3002, '登录失败');
        }
        $request_sign_str = $request->getHeader('sign')[0];
        $request_time_stamp = $request->getHeader('time-stamp')[0];
        $nonce_str = $request->getHeader('nonce-str')[0];
        $time_stamp = time();
        $difference = $time_stamp - $request_time_stamp;

        if ($difference && $difference > 180 || $difference && $difference < -180) {
            throw new BusinessException(3004, '密钥已过期');
        }
        $sign_type = ($request_time_stamp % 5) % 2;
        if ($sign_type) {
            $request_sign = substr($request_sign_str, 0, 32);
            $request_token = substr($request_sign_str, 32);
        } else {
            $request_sign = substr($request_sign_str, -32);
            $request_token = substr($request_sign_str, 0, -32);
        }
        $sign_info = [
            'time_stamp' => $request_time_stamp,
            'nonce_str' => $nonce_str,
            'token' => $request_token,
        ];
        $all_params = array_merge($sign_info, $request->getQueryParams(), $request->getParsedBody());
        $sort_string = $this->sort_ascii($all_params);
        $sign_string = strtoupper(base64_encode($sort_string));  // base64转后转大写
        $sign = md5($sign_string);
        if ($request_sign != $sign) {
            throw new BusinessException(3005, '签名错误');
        }
        $token = new Token();
        $token_info = $token->get($request_token);
        if (! $token_info) {
            throw new BusinessException(3003, '页面已过期，请重新操作');
        }
        $querys = $request->getQueryParams();
        if (isset($querys['openid'])) {
            if ($querys['openid'] != $token_info['openid']) {
                throw new BusinessException(3006, '非法请求！');
            }
        }
        $parsed = $request->getParsedBody('openid');
        if (isset($parsed['openid'])) {
            if ($parsed['openid'] != $token_info['openid']) {
                throw new BusinessException(3006, '非法请求！');
            }
        }
        Context::set(ServerRequestInterface::class, $request);
        return $handler->handle($request);
    }

    /*ascii码从小到大排序
     * @param array $params
     * @return bool|string
     */
    private function sort_ascii($params = [])
    {
        if (! empty($params)) {
            $p = ksort($params);
            if ($p) {
                $str = '';
                foreach ($params as $k => $val) {
                    $str .= $k . '=' . $val . '&';
                }
                return rtrim($str, '&');
            }
        }
        return false;
    }
}
