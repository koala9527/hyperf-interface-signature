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
namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Tool\Token;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;

/**
 * @Controller(prefix="test")
 */
class TestController extends AbstractController
{
    /**
     * @Inject
     * @var Token
     */
    protected $token_tool;

    /**
     * @PostMapping(path="test")
     * @Middleware(AuthMiddleware::class)
     */
    public function test()
    {
        return $this->request->input('id');
    }

    /**
     * @PostMapping(path="login")
     */
    public function login()
    {
        $user = $this->request->input('name');
        $password = $this->request->input('password');
        // 略过数据库对比，直接插入redis表示登录成功
        $admin_array['user'] = $user;
        $openid = $user.$password;
        $token = $this->token_tool->set($openid, $admin_array);
        $res = ['token'=>$token,'code'=>200,'msg'=>'ok'];
        return json_encode($res);
    }
}
