<?php

namespace edphp\exception;

use edphp\Response;
use Exception;

class Handle
{
    protected $render;
    //
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
        if (!$this->isIgnoreReport($exception)) {
            // 收集异常数据
            $data = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $this->getMessage($exception),
                'code' => $this->getCode($exception),
            ];
            $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";

            if (config('log.record_trace')) {
                $log .= "\r\n" . $exception->getTraceAsString();
            }

            record($log, 'error');
        }
    }

    protected function isIgnoreReport(Exception $exception)
    {
        foreach ($this->ignoreReport as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
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
        
        if (key_exists("HTTP_ACCEPT", $_SERVER) && false !== strpos($_SERVER["HTTP_ACCEPT"], 'application/json')) {
            return $this->renderErrorJson($e);
        } else {
            return $this->renderErrorHtml($e);
        }
    }

    protected function renderErrorHtml(Exception $exception)
    {
        if (!isDebug()) {
            return Response::create("系统内部错误", 'html');
        }

        // 调试模式，获取详细的错误信息
        $data = $this->getError($exception);

        //保留一层
        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        $data['echo'] = ob_get_clean();

        ob_start();
        extract($data);
        include config('exception_tmpl');
        // 获取并清空缓存
        $content = ob_get_clean();
        $response = Response::create($content, 'html');

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $response->header($exception->getHeaders());
        }

        if (!isset($statusCode)) {
            $statusCode = 500;
        }
        $response->code($statusCode);

        return $response;
    }

    private function getError($exception)
    {
        return [
            'name' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $this->getMessage($exception),
            'trace' => $exception->getTrace(),
            'code' => $this->getCode($exception),
            'source' => $this->getSourceCode($exception),
            'datas' => $this->getExtendData($exception),
            'tables' => [
                'GET Data' => $_GET,
                'POST Data' => $_POST,
                'Files' => $_FILES,
                'Cookies' => $_COOKIE,
                'Session' => isset($_SESSION) ? $_SESSION : [],
                'Server/Request Data' => $_SERVER,
                'Environment Variables' => $_ENV,
            ],
        ];
    }

    protected function renderErrorJson(Exception $e)
    {
        // 参数验证错误
        if ($e instanceof \edphp\exception\ValidateException) {
            return json(['message' => 'no route found', 'code' => 422, 'success' => false]);
        }
        // 请求异常
        if ($e instanceof \edphp\exception\HttpException) {
            return json(['message' => $e->getMessage(), 'code' => $e->getStatusCode(), 'success' => false]);
        }

        if (isDebug()) {
            $error = $this->getError($e);
            return json(['detail' => $error, 'message' => $e->getMessage() , 'code' => 500, 'success' => false]);
        }
        return json(['message' => 'server error', 'code' => 500, 'success' => false]);
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
        $line = $exception->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;

        try {
            $contents = file($exception->getFile());
            $source = [
                'first' => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (Exception $e) {
            $source = [];
        }

        return $source;
    }

    /**
     * 获取异常扩展信息
     * 用于非调试模式html返回类型显示
     * @access protected
     * @param  \Exception $exception
     * @return array                 异常类定义的扩展数据
     */
    protected function getExtendData(Exception $exception)
    {
        $data = [];

        if ($exception instanceof \edphp\Exception) {
            $data = $exception->getData();
        }

        return $data;
    }

}
