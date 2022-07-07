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
namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());
        $error = [
            'code' => (int) $throwable->getCode(),
            'msg' => $throwable->getMessage(),
            'app' => env('APP_NAME'),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ];
        $this->logger->error(sprintf('%s[%s] in %s,%s', $error['app'], $error['msg'], $error['line'], $error['file']));
        $this->logger->error($throwable->getTraceAsString());

        if (env('APP_ENV') != 'prod') {
            $res = $error;
        } else {
            $res = [
                'code' => $error['code'],
                'msg' => $error['msg'],
            ];
        }
        $data = json_encode($res);
        $this->stopPropagation();
        return $response->withHeader('Server', 'Hyperf')->withHeader('Content-Type', 'application/json')->withStatus(500)->withBody(new SwooleStream($data));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
