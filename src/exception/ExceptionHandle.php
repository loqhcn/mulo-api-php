<?php

namespace mulo\exception;

use mulo\exception\MuloException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{


    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
        MuloException::class,
    ];


    public function render($request, Throwable $e): Response
    {

        // 自定义异常处理为返回接口
        if ($e instanceof MuloException) {
            return $e->getResponse();
        }

        // // 参数验证错误
        // if ($e instanceof ValidateException) {
        //     return json($e->getError(), 422);
        // }

        // // 请求异常
        // if ($e instanceof HttpException && $request->isAjax()) {
        //     return response($e->getMessage(), $e->getStatusCode());
        // }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
