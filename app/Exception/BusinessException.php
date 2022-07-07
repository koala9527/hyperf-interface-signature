<?php

declare(strict_types=1);
/**
 * This file is part of YiTui.
 */

namespace App\Exception;

use App\Constants\ErrorCode;
use Hyperf\Server\Exception\ServerException;
use Throwable;

class BusinessException extends ServerException
{
    public function __construct(int $code = 0, string $msg = null, Throwable $previous = null, array $params = [])
    {
        if (is_null($msg) || $msg == '') {
            $msg = ErrorCode::getMessage($code);
        }
        // 追加参数
        if ($params) {
            $str = [];
            foreach ($params as $k => $v) {
                $str[] = "{$k}:{$v}";
            }
            $msg .= ',' . join(',', $str);
        }
        parent::__construct($msg, $code ?: ErrorCode::SERVER_ERROR, $previous);
    }
}
