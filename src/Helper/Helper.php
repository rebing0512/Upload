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

    /**
     * [ParamsChecked 参数校验方法]
     * @author   Jenson
     * @version  1.0.0
     * @param    [array]                   $data   [原始数据]
     * @param    [array]                   $params [校验数据]
     * @return   [boolean|string]                  [成功true, 失败 错误信息]
     */
    public static function ParamsChecked($data, $params)
    {
        if(empty($params) || !is_array($data) || !is_array($params))
        {
            return '内部调用参数配置有误';
        }

        foreach ($params as $v)
        {
            if(empty($v['key_name']) || empty($v['error_msg']))
            {
                return '内部调用参数配置有误';
            }

            // 是否需要验证
            $is_checked = true;

            // 数据或字段存在则验证
            // 1 数据存在则验证
            // 2 字段存在则验证
            if(isset($v['is_checked']))
            {
                if($v['is_checked'] == 1)
                {
                    if(empty($data[$v['key_name']]))
                    {
                        $is_checked = false;
                    }
                } else if($v['is_checked'] == 2)
                {
                    if(!isset($data[$v['key_name']]))
                    {
                        $is_checked = false;
                    }
                }
            }

            // 是否需要验证
            if($is_checked === false)
            {
                continue;
            }

            // 数据类型,默认字符串类型
            $data_type = empty($v['data_type']) ? 'string' : $v['data_type'];

            // 验证规则，默认isset
            $checked_type = isset($v['checked_type']) ? $v['checked_type'] : 'isset';
            switch($checked_type)
            {
                // 是否存在
                case 'isset' :
                    if(!array_key_exists($v['key_name'], $data))
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 是否为空
                case 'empty' :
                    if(empty($data[$v['key_name']]))
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 是否存在于验证数组中
                case 'in' :
                    if(empty($v['checked_data']))
                    {
                        return '指定校验数据为空['.$v['key_name'].']';
                    }
                    if(!is_array($v['checked_data']))
                    {
                        return '内部调用参数配置有误['.$v['key_name'].']';
                    }
                    if(!isset($data[$v['key_name']]) || !in_array($data[$v['key_name']], $v['checked_data']))
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 是否为数组
                case 'is_array' :
                    if(!isset($data[$v['key_name']]) || !is_array($data[$v['key_name']]))
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 长度
                case 'length' :
                    if(!isset($v['checked_data']))
                    {
                        return '长度规则值未定义['.$v['key_name'].']';
                    }
                    if(!isset($data[$v['key_name']]))
                    {
                        return $v['error_msg'];
                    }
                    if($data_type == 'array')
                    {
                        $length = count($data[$v['key_name']]);
                    } else {
                        $length = mb_strlen($data[$v['key_name']], 'utf-8');
                    }
                    $rule = explode(',', $v['checked_data']);
                    if(count($rule) == 1)
                    {
                        if($length > intval($rule[0]))
                        {
                            return $v['error_msg'];
                        }
                    } else {
                        if($length < intval($rule[0]) || $length > intval($rule[1]))
                        {
                            return $v['error_msg'];
                        }
                    }
                    break;

                // 自定义函数
                case 'fun' :
                    if(empty($v['checked_data']) || !function_exists($v['checked_data']))
                    {
                        return '验证函数为空或函数未定义['.$v['key_name'].']';
                    }
                    $fun = $v['checked_data'];
                    if(!isset($data[$v['key_name']]) || !$fun($data[$v['key_name']]))
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 最小
                case 'min' :
                    if(!isset($v['checked_data']))
                    {
                        return '验证最小值未定义['.$v['key_name'].']';
                    }
                    if(!isset($data[$v['key_name']]) || $data[$v['key_name']] < $v['checked_data'])
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 最大
                case 'max' :
                    if(!isset($v['checked_data']))
                    {
                        return '验证最大值未定义['.$v['key_name'].']';
                    }
                    if(!isset($data[$v['key_name']]) || $data[$v['key_name']] > $v['checked_data'])
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 相等
                case 'eq' :
                    if(!isset($v['checked_data']))
                    {
                        return '验证相等未定义['.$v['key_name'].']';
                    }
                    if(!isset($data[$v['key_name']]) || $data[$v['key_name']] == $v['checked_data'])
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 不相等
                case 'neq' :
                    if(!isset($v['checked_data']))
                    {
                        return '验证相等未定义['.$v['key_name'].']';
                    }
                    if(!isset($data[$v['key_name']]) || $data[$v['key_name']] != $v['checked_data'])
                    {
                        return $v['error_msg'];
                    }
                    break;

                // 数据库唯一
                case 'unique' :
                    if(!isset($v['checked_data']))
                    {
                        return '验证唯一表参数未定义['.$v['key_name'].']';
                    }
                    if(empty($data[$v['key_name']]))
                    {
                        return str_replace('{$var}', 'unique验证', $v['error_msg']);
                    }
                    $temp = \think\facade\Db::name($v['checked_data'])->where([$v['key_name']=>$data[$v['key_name']]])->find();
                    if(!empty($temp))
                    {
                        // 错误数据变量替换
                        $error_msg = str_replace('{$var}', $data[$v['key_name']], $v['error_msg']);

                        // 是否需要排除当前操作数据
                        if(isset($v['checked_key']))
                        {
                            if(empty($data[$v['checked_key']]) || (isset($temp[$v['checked_key']]) && $temp[$v['checked_key']] != $data[$v['checked_key']]))
                            {
                                return $error_msg;
                            }
                        } else {
                            return $error_msg;
                        }
                    }
                    break;
            }
        }
        return true;
    }
}

