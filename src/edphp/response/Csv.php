<?php

namespace edphp\response;

use edphp\Response;

class Csv extends Response
{

    protected $contentType = 'application/vnd.ms-excel';

    /**
     * header参数
     * @var array
     */
    protected $header = ['Cache-Control' => 'max-age=0'];

    public function __construct($data = '', $headlist = array(), $filename, array $header = [], array $options = [])
    {
        parent::__construct($data, 200, $header, $options);
        $this->headlist = $headlist;
        $this->header['Content-Disposition'] = "attachment;filename=$filename.csv";
    }

    /**
     * 导出excel(csv)
     * @data 导出数据
     * @headlist 第一行,列名
     * @fileName 输出Excel文件名
     */
    public function csv_export()
    {
        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        //输出Excel列名信息
        foreach ($this->headlist as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $headlist[$key] = iconv('utf-8', 'gbk', $value);
        }

        //将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headlist);

        //计数器
        $num = 0;

        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;

        $count = count($this->data);
        for ($i = 0; $i < $count; $i++) {
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }

            $row = $this->data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $row);
        }
    }

    /**
     * 输出数据
     * @access protected
     * @param string $data 要处理的数据
     * @return void
     */
    protected function sendData($data)
    {
        $this->csv_export();
    }


    /**
     * 获取输出数据
     * @access public
     * @return mixed
     */
    public function getContent()
    {
        return $this->data;
    }

}

