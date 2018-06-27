<?php

namespace edphp\exception;

use Exception;
use edphp\Response;

class Handle
{
    protected $render;
    protected $ignoreReport = [
        '\\edphp\\exception\\HttpException',
    ];

    public function setRender($render)
    {
        $this->render = $render;
    }

    /**
     * Report or log an exception.
     *
     * @access public
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        //TODO 收集错误，写入日志文件
        // if (!$this->isIgnoreReport($exception)) {
        //     // 收集异常数据
        //     if (Container::get('app')->isDebug()) {
        //         $data = [
        //             'file'    => $exception->getFile(),
        //             'line'    => $exception->getLine(),
        //             'message' => $this->getMessage($exception),
        //             'code'    => $this->getCode($exception),
        //         ];
        //         $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
        //     } else {
        //         $data = [
        //             'code'    => $this->getCode($exception),
        //             'message' => $this->getMessage($exception),
        //         ];
        //         $log = "[{$data['code']}]{$data['message']}";
        //     }

        //     if (Container::get('app')->config('log.record_trace')) {
        //         $log .= "\r\n" . $exception->getTraceAsString();
        //     }

        //     Container::get('log')->record($log, 'error');
        // }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param  \Exception $e
     * @return Response
     */
    public function render(Exception $e)
    {
        if(isDebug()) {
            //TODO 输出错误页面方便调试
        }

        // 参数验证错误
        if ($e instanceof \edphp\exception\ValidateException) {
            return json(['msg' => 'no route found', 'code' => 422, 'success' => false]);
        }
        // 请求异常
        if ($e instanceof \edphp\exception\HttpException ) {
            return json(['msg' => $e->getMessage(), 'code' => $e->getStatusCode(), 'success' => false]);
        }

        return json(['msg' => 'server error', 'code' => 500, 'success' => false]);
    }

    /**
     * 获取错误编码
     * ErrorException则使用错误级别作为错误编码
     * @access protected
     * @param  \Exception $exception
     * @return integer                错误编码
     */
    protected function getCode(Exception $exception)
    {
        $code = $exception->getCode();

        if (!$code && $exception instanceof ErrorException) {
            $code = $exception->getSeverity();
        }

        return $code;
    }

    /**
     * 获取错误信息
     * ErrorException则使用错误级别作为错误编码
     * @access protected
     * @param  \Exception $exception
     * @return string                错误信息
     */
    protected function getMessage(Exception $exception)
    {
        $message = $exception->getMessage();

        if (PHP_SAPI == 'cli') {
            return $message;
        }

        $lang = Container::get('lang');

        if (strpos($message, ':')) {
            $name    = strstr($message, ':', true);
            $message = $lang->has($name) ? $lang->get($name) . strstr($message, ':') : $message;
        } elseif (strpos($message, ',')) {
            $name    = strstr($message, ',', true);
            $message = $lang->has($name) ? $lang->get($name) . ':' . substr(strstr($message, ','), 1) : $message;
        } elseif ($lang->has($message)) {
            $message = $lang->get($message);
        }

        return $message;
    }

    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * @access protected
     * @param  \Exception $exception
     * @return array                 错误文件内容
     */
    protected function getSourceCode(Exception $exception)
    {
        // 读取前9行和后9行
        $line  = $exception->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;

        try {
            $contents = file($exception->getFile());
            $source   = [
                'first'  => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (Exception $e) {
            $source = [];
        }

        return $source;
    }

}
