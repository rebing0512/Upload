<?php

namespace Jenson\Upload\Helper;

class Helper
{
    public static function DataReturn($msg = '', $code = 0, $data = '')
    {
        // 默认情况下，手动调用当前方法
        $result = ['msg'=>$msg, 'code'=>$code, 'data'=>$data];

        // 错误情况下，防止提示信息为空
        if($result['code'] != 0 && empty($result['msg']))
        {
            $result['msg'] = '操作失败';
        }
        return $result;
    }

    /**
     * 路径解析指定参数
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-06
     * @desc    description
     * @param   [string]            $key        [指定key]
     * @param   [mixed]             $default    [默认值]
     * @param   [string]            $path       [参数字符串 格式如： a/aa/b/bb/c/cc ]
     */
    public static function PathToParams($key = null, $default = null, $path = '')
    {
        $data = $_REQUEST;
        if(empty($path) && isset($_REQUEST['s']))
        {
            $path = $_REQUEST['s'];
        }
        if(empty($path))
        {
            $path = !empty($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : (empty($_SERVER['REDIRECT_URL']) ? (empty($_SERVER['REQUEST_URI']) ? (empty($_SERVER['PATH_TRANSLATED']) ? '' : $_SERVER['PATH_TRANSLATED']) : $_SERVER['REQUEST_URI']) : $_SERVER['REDIRECT_URL']);
        }

        if(!empty($path) && !array_key_exists($key, $data))
        {
            if(substr($path, 0, 1) == '/')
            {
                $path = mb_substr($path, 1, mb_strlen($path, 'utf-8')-1, 'utf-8');
            }
            $position = strrpos($path, '.');
            if($position !== false)
            {
                $path = mb_substr($path, 0, $position, 'utf-8');
            }
            $arr = explode('/', $path);


            $index = 0;
            foreach($arr as $k=>$v)
            {
                if($index != $k)
                {
                    $data[$arr[$index]] = $v;
                    $index = $k;
                }
            }
        }

        if($key !== null)
        {
            return array_key_exists($key, $data) ? $data[$key] : $default;
        }
        return $data;
    }
}

