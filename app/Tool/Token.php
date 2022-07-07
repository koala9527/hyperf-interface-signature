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
namespace App\Tool;

use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * token 用来设置更新和获取token相关的信息.
 */
class Token
{
    /**
     * token过期时间（以秒计算）.
     * @var int
     */
    protected $expire = 7200;

    /**
     * token长度.
     * @var int
     */
    protected $token_length = 96;

    /**
     * 缓存.
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        $this->cache = ApplicationContext::getContainer()->get(CacheInterface::class);
    }

    /**
     * 设置token.
     * @param array $info 需要缓存的信息，可以是头像用户名称等
     * @throws InvalidArgumentException
     * @return string str 成功返回token值，失败返回 false
     */
    public function set(string $openid, array $info, int $expire = 7200): string
    {
        $token = $this->getTokenStr($this->token_length);  // 随机字符串
        $token_name = $this->getTokenName($token);  // 加上混淆
        $token_info = [
            'openid' => $openid,
            'info' => $info,
            'create_time' => time(),
        ];
        $this->cache->set($token_name, $token_info, $expire);
        return $token;
    }

    /**
     * 获取token存储的信息.
     * @return array|false str 成功返回token信息，失败返回 false
     */
    public function get(string $token)
    {
        $token_name = $this->getTokenName($token);
        $info = $this->cache->get($token_name);
        if (! $info) {
            return false;
        }
        $this->update($token, $info['info']);
        return $info;
    }

    /**
     * 校验token是否存在.
     * @return bool
     */
    public function check(string $token)
    {
        $token_name = $this->getTokenName($token);
        $info = $this->cache->get($token_name);
        return $info ? true : false;
    }

    /**
     * 更新token.
     * @param array $info token需要存储的内容
     * @return bool 成功返回true 失败返回 false
     */
    public function update(string $token, array $info = null)
    {
        $token_name = $this->getTokenName($token);
        $token_info = $this->cache->get($token_name);
        if (! $token_info) {
            return false;
        }
        $token_info['update_time'] = time();
        $token_info['info'] = $info ? $info : $token_info['info'];
        $this->cache->set($token_name, $token_info, $this->expire);
        return true;
    }

    /**
     * 删除token.
     * @return bool 成功返回true 失败返回 false
     */
    public function delete(string $token)
    {
        $token_name = $this->getTokenName($token);
        return $this->cache->delete($token_name);
    }

    /**
     * 获取缓存的token名称.
     *
     * @return string 返回token名称
     */
    private function getTokenName(string $token)
    {
        return '__UserAuthToken__' . $token;
    }

    /**
     * 获取token字符串
     *
     * @param integer $len 长度
     * @return string 字符串
     */
    private function getTokenStr(int $len)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $string = (string)microtime(true) * 10000;
        settype($string,"string");
        $len -= strlen($string);
        for (; $len > 1; $len--) {
            $position = rand() % strlen($chars);
            $position2 = rand() % strlen($string);
            $string = substr_replace($string, substr($chars, $position, 1), $position2, 0);
        }
        return $string;
    }
}
