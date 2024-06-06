<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace Jenson\Upload\Service;

use Jenson\Upload\Helper\DB;
use Jenson\Upload\Helper\FileUtil;
use Jenson\Upload\Helper\Helper;

// HTTP类型
define('__MY_HTTP__', (
    (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && (strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off' || $_SERVER['HTTP_FRONT_END_HTTPS'] == 'https'))
    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (!empty($_SERVER['HTTP_FROM_HTTPS']) && $_SERVER['HTTP_FROM_HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_CLIENT_SCHEME']) && $_SERVER['HTTP_X_CLIENT_SCHEME'] == 'https')
    || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
) ? 'https' : 'http');
// 根目录
$my_root = '';
if(!empty($_SERVER['SCRIPT_NAME']))
{
    $index_pos = strpos($_SERVER['SCRIPT_NAME'], 'index.php');
    if($index_pos !== false)
    {
        $my_root = substr($_SERVER['SCRIPT_NAME'], 1, $index_pos-1);
    } else {
        $my_root = substr($_SERVER['SCRIPT_NAME'], 1, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }
}
define('__MY_ROOT_PUBLIC__', defined('IS_ROOT_ACCESS') ? DS.$my_root.'public'.DS : DS.$my_root);
// 定义系统目录分隔符
define('DS', '/');
// 项目HOST
define('__MY_HOST__', empty($_SERVER['HTTP_HOST']) ? '' : strtolower($_SERVER['HTTP_HOST']));
// 项目HOST地址
define('__MY_DOMAIN__',  empty($_SERVER['HTTP_HOST']) ? '' : __MY_HTTP__.'://'.__MY_HOST__.DS);
// 项目完整HOST地址
define('__MY_URL__',  empty($_SERVER['HTTP_HOST']) ? '' : __MY_DOMAIN__.$my_root);
// 项目public目录URL地址
define('__MY_PUBLIC_URL__',  empty($_SERVER['HTTP_HOST']) ? '' : __MY_HTTP__.'://'.__MY_HOST__.__MY_ROOT_PUBLIC__);
// 系统根目录,强制转换win反斜杠
define('ROOT_PATH', str_replace('\\', DS, dirname(__FILE__)).DS);
// 系统根目录 去除public
define('ROOT', substr(ROOT_PATH, 0, -7));
/**
 * 资源服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ResourcesService
{
    /**
     * 编辑器中内容的静态资源替换
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-22T16:07:58+0800
     * @param    [string]    $content [在这个字符串中查找进行替换]
     * @param    [string]    $type    [操作类型[get读取额你让, add写入内容](编辑/展示传入get,数据写入数据库传入add)]
     * @return   [string]             [正确返回替换后的内容, 则返回原内容]
     */
    public static function ContentStaticReplace($content, $type = 'get')
    {
        // 配置文件附件url地址
        $attachment_host = self::AttachmentHost();
        if(empty($attachment_host))
        {
            $attachment_host = substr(__MY_PUBLIC_URL__, 0, -1);
        }
        $attachment_host_path = $attachment_host.'/static/';

        // 根据类型处理附件地址
        switch($type)
        {
            // 读取内容
            case 'get':
                return str_replace('src="/static/', 'src="'.$attachment_host_path, $content);
                break;

            // 内容写入
            case 'add':
                $search = [
                    'src="'.__MY_PUBLIC_URL__.'static/',
                    'src="'.__MY_ROOT_PUBLIC__.'static/',
                    'src="'.$attachment_host_path,
                ];
                return str_replace($search, 'src="/static/', $content);
        }
        return $content;
    }

    /**
     * 附件路径处理
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     * @param   [string|array]          $value [附件路径地址]
     */
    public static function AttachmentPathHandle($value)
    {
        // 配置文件附件url地址
        $attachment_host = self::AttachmentHost();
        $attachment_host_path = empty($attachment_host) ? __MY_PUBLIC_URL__ : $attachment_host.DS;

        // 替换处理
        $search = [
            __MY_PUBLIC_URL__,
            __MY_ROOT_PUBLIC__,
            $attachment_host_path,
        ];

        // 是否数组
        if(!empty($value))
        {
            if(is_array($value))
            {
                foreach($value as &$v)
                {
                    // 是否二级url
                    if(isset($v['url']))
                    {
                        $v['url'] = empty($v['url']) ? '' : str_replace($search, DS, $v['url']);
                    } else {
                        $v = empty($v) ? '' : str_replace($search, DS, $v);
                    }
                }
            } else {
                $value = empty($value) ? '' : str_replace($search, DS, $value);
            }
        }
        return $value;
    }

    /**
     * 附件集合处理
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     * @param   [array]          $params [输入参数]
     * @param   [array]          $data   [字段列表]
     */
    public static function AttachmentParams($params, $data)
    {
        $result = [];
        if(!empty($data))
        {
            foreach($data as $field)
            {
                $result[$field] = isset($params[$field]) ? self::AttachmentPathHandle($params[$field]) : '';
            }
        }

        return Helper::DataReturn('success', 0, $result);
    }

    /**
     * 附件展示地址处理
     * @author   Jenson
     * @version  1.0.0
     * @param    [string|array]             $value [附件地址]
     */
    public static function AttachmentPathViewHandle($value)
    {
        if(!empty($value))
        {
            // 附件地址
            $host = self::AttachmentHost();

            // 是否数组
            if(is_array($value))
            {
                foreach($value as &$v)
                {
                    // 是否二级url
                    if(isset($v['url']))
                    {
                        if(substr($v['url'], 0, 4) != 'http')
                        {
                            $v['url'] = $host.$v['url'];
                        }
                    } else {
                        if(substr($v, 0, 4) != 'http')
                        {
                            $v = $host.$v;
                        }
                    }
                }
            } else {
                if(substr($value, 0, 4) != 'http')
                {
                    $value = $host.$value;
                }
            }
        }
        return $value;
    }

    /**
     * 相对路径文件新增
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     * @param   [string]          $value        [相对路径文件 /static 开头]
     * @param   [string]          $path_type    [文件存储路径]
     */
    public static function AttachmentPathAdd($value, $path_type)
    {
        // 文件是否存在
        $file = ROOT.'public'.$value;
        if(!file_exists($file))
        {
            return Helper::DataReturn('文件不存在', -1);
        }

        // 配置信息
        $config = parse_ini_file('../config/ueditor.ini', true);//MyConfig('ueditor');

        // 文件信息
        $info = pathinfo($file);
        $title = empty($info['basename']) ? substr(strrchr($file, '/'), 1) : $info['basename'];
        $ext = strtolower(strrchr($file, '.'));
        $type = in_array($ext, $config['imageAllowFiles']) ? 'image' : (in_array($ext, $config['videoAllowFiles']) ? 'video' : 'file');

        // 添加文件
        $data = [
            "url"       => $value,
            "path"      => $file,
            "title"     => $title,
            "original"  => $title,
            "ext"       => $ext,
            "size"      => filesize($file),
            'type'      => $type,
            "hash"      => hash_file('sha256', $file, false),
            'path_type' => $path_type,
        ];
        return self::AttachmentAdd($data);
    }

    /**
     * 附件添加
     * @author   Jenson
     * @version  1.0.0
     * @param    [array]         $params [输入参数]
     */
    public static function AttachmentAdd($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'title',
                'error_msg'         => '名称有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'original',
                'error_msg'         => '原名有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'path_type',
                'error_msg'         => '路径标记有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'url',
                'error_msg'         => '地址有误',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'size',
                'error_msg'         => '文件大小有误',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'ext',
                'error_msg'         => '扩展名有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'hash',
                'error_msg'         => 'hash值有误',
            ],
        ];
        $ret = Helper::ParamsChecked($params, $p);
        if($ret !== true)
        {
            return Helper::DataReturn($ret, -1);
        }

        // 数据组装
        $data = [
            'path_type'     => $params['path_type'],
            'original'      => empty($params['original']) ? '' : mb_substr($params['original'], -160, null, 'utf-8'),
            'title'         => $params['title'],
            'size'          => $params['size'],
            'ext'           => $params['ext'],
            'type'          => isset($params['type']) ? $params['type'] : 'file',
            'hash'          => $params['hash'],
            'url'           => self::AttachmentPathHandle($params['url']),
            'add_time'      => time(),
        ];
        // 添加到数据库
        $DB = new DB();
        $database = $DB->database;
        $database->insert('attachment',$data);
        $attachment_id = $database->id();
        if($attachment_id > 0)
        {
            $params['id'] = $attachment_id;
            $params['url'] = self::AttachmentPathViewHandle($data['url']);
            $params['add_time'] = date('Y-m-d H:i:s', $data['add_time']);
            return Helper::DataReturn('插入成功', 0, $params);
        }

        // 删除本地图片
        if(!empty($params['path']))
        {
            FileUtil::UnlinkFile($params['path']);
        }
        return Helper::DataReturn('插入失败', -100);
    }

    /**
     * 获取附件总数
     * @author   Jenson
     * @version  1.0.0
     * @param    [array]               $where [条件]
     */
    public static function AttachmentTotal($where)
    {
        $DB = new DB();
        $database = $DB->database;
        $count = $database->count('attachment',$where);
        return (int) $count;
    }

    /**
     * 获取附件列表
     * @author   Jenson
     * @version  1.0.0
     * @param    [array]               $params [参数]
     */
    public static function AttachmentList($params = [])
    {
        $m = max(0, isset($params['m']) ? intval($params['m']) : 0);
        $n = max(1, isset($params['n']) ? intval($params['n']) : 20);
        //Db::name('Attachment')->where($params['where'])->order('id desc')->limit($m, $n)->select()->toArray();
        $DB = new DB();
        $database = $DB->database;
        $where = $params['where'];
        $where[] = [
            'id' => 'ASC',
            'ç'=>[$m,$n]
        ];
        $data = $database->select('attachment',[],$where);
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                if(isset($ret['code']) && $ret['code'] != 0)
                {
                    return $ret;
                }
                // 数据处理
                $v['url'] = self::AttachmentPathViewHandle($v['url']);
                $v['add_time'] = date('Y-m-d H:i:s');
            }
        }
        return Helper::DataReturn('success', 0, $data);
    }

    /**
     * 附件删除
     * @author   Jenson
     * @version  1.0.0
     * @param    [array]              $params [输入参数]
     */
    public static function AttachmentDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ]
        ];
        $ret = Helper::ParamsChecked($params, $p);
        if($ret !== true)
        {
            return Helper::DataReturn($ret, -1);
        }

        // 获取数据
        $DB = new DB();
        $database = $DB->database;
        $data = $database->get('attachment','','',['id'=>$params['id']]);
//        $data = Db::name('Attachment')->find(intval($params['id']));
        if(empty($data))
        {
            return Helper::DataReturn('数据不存在或已删除', -1);
        }

        // 删除文件
        $path = substr(ROOT_PATH, 0, -1).$data['url'];
        if(file_exists($path))
        {
            if(is_writable($path))
            {
                if($database->delete('attachment',['id'=>$data['id']]))
                {
                    // 删除附件
                    FileUtil::UnlinkFile($path);

                    $ret = Helper::DataReturn('删除成功', 0);
                } else {
                    $ret = Helper::DataReturn('删除失败', -100);
                }
            } else {
                $ret = Helper::DataReturn('没有删除权限', -1);
            }
        } else {
            if($database->delete('attachment',['id'=>$data['id']]))
            {
                $ret = Helper::DataReturn('删除成功', 0);
            } else {
                $ret = Helper::DataReturn('删除失败', -100);
            }
        }
        return $ret;
    }

    /**
     * 附件根据标记删除
     * @author   Jenson
     * @version  1.0.0
     * @param    [string]              $path_type [唯一标记]
     */
    public static function AttachmentPathTypeDelete($path_type)
    {
        // 获取附件数据
        $where = ['path_type'=>$path_type];
        $DB = new DB();
        $database = $DB->database;
        $data = $database->select('attachment',[],$where);
//        $data = DB::name('Attachment')->where($where)->select()->toArray();
        if(!empty($data))
        {
            // 删除数据库数据
            if(!$database->delete('attachment',$where))
            {
                return Helper::DataReturn('删除失败', -1);
            }

            // 删除磁盘文件
            $path = substr(ROOT_PATH, 0, -1);
            foreach($data as $v)
            {
                $file = $path.$v['url'];
                if(file_exists($file) && is_writable($file))
                {
                    FileUtil::UnlinkFile($file);
                }
            }
        }
        return Helper::DataReturn('删除成功', 0);
    }

    /**
     * 磁盘附加同步到数据库
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     * @param   [string]          $dir_path     [附件路径类型]
     * @param   [string]          $path_type    [附件路径值类型]
     */
    public static function AttachmentDiskFilesToDb($dir_path, $path_type = '')
    {
        // 未指定类型值则使用路径值
        if(empty($path_type))
        {
            $path_type = $dir_path;
        }

        // 处理状态总数
        $count = 0;
        $success = 0;
        $error = 0;

        // 视频/文件/图片
        $path_all = [
            'video' => __MY_ROOT_PUBLIC__.'static/upload/video/'.$dir_path.'/',
            'file'  => __MY_ROOT_PUBLIC__.'static/upload/file/'.$dir_path.'/',
            'image' => __MY_ROOT_PUBLIC__.'static/upload/images/'.$dir_path.'/',
        ];
        foreach($path_all as $type=>$path)
        {
            $path = self::GetDocumentRoot() . (substr($path, 0, 1) == "/" ? "":"/") . $path;
            $files =self::AttachmentDiskFilesList($path, $type, $path_type);
            if(!empty($files))
            {
                $count += count($files);
                $DB = new DB();
                $database = $DB->database;
                foreach($files as $v)
                {
                    $temp = $database->get('attachment','','',['title'=>$v['title'], 'hash'=>$v['hash'], 'path_type'=>$path_type]);
//                    $temp = Db::name('Attachment')->where(['title'=>$v['title'], 'hash'=>$v['hash'], 'path_type'=>$path_type])->find();
                    if(empty($temp))
                    {
                        $ret = self::AttachmentAdd($v);
                        if($ret['code'] == 0)
                        {
                            $success++;
                        } else {
                            $error++;
                        }
                    } else {
                        $success++;
                    }
                }
            }
        }
        return Helper::DataReturn('总数['.$count.'], 成功['.$success.'], 失败['.$error.']', 0);
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @author   Jenson
     * @param    [string]        $path          [路径地址]
     * @param    [string]        $type          [允许的文件]
     * @param    [string]        $path_type     [路径类型]
     * @param    [array]         &$files        [数据]
     * @return   [array]                        [数据]
     */
    public static function AttachmentDiskFilesList($path, $type, $path_type, &$files = [])
    {
        if(!is_dir($path)) return null;
        if(substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        $document_root = self::GetDocumentRoot();
        while(false !== ($file = readdir($handle)))
        {
            if($file != 'index.html' && $file != '.' && $file != '..' && substr($file, 0, 1) != '.')
            {
                $temp_path = $path . $file;
                if(is_dir($temp_path))
                {
                    self::AttachmentDiskFilesList($temp_path, $type, $path_type, $files);
                } else {
                    $url = self::AttachmentPathHandle(substr($temp_path, strlen($document_root)));
                    $title = substr($url, strripos($url, '/')+1);
                    $root_path = ROOT.'public'.$url;
                    $files[] = array(
                        'url'       => $url,
                        'original'  => $title,
                        'title'     => $title,
                        'type'      => $type,
                        'path_type' => $path_type,
                        'size'      => file_exists($root_path) ? filesize($root_path) : 0,
                        'hash'      => file_exists($root_path) ? hash_file('sha256', $root_path, false) : '',
                        'ext'       => substr($title, strripos($title, '.')),
                    );
                }
            }
        }
        return $files;
    }

    /**
     * 小程序富文本标签处理
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     * @param   [string]          $content [需要处理的富文本内容]
     */
    public static function ApMiniRichTextContentHandle($content)
    {
        // 标签处理，兼容小程序rich-text
        $search = [
            '<img ',
            '<section',
            '/section>',
            '<p style="',
            '<p>',
            '<div>',
            '<table',
            '<tr',
            '<td',
        ];
        $replace = [
            '<img style="max-width:100%;height:auto;margin:0;padding:0;display:block;" ',
            '<div',
            '/div>',
            '<p style="margin:0;',
            '<p style="margin:0;">',
            '<div style="margin:0;">',
            '<table style="width:100%;margin:0px;border-collapse:collapse;border-color:#ddd;border-style:solid;border-width:0 1px 1px 0;"',
            '<tr style="border-top:1px solid #ddd;"',
            '<td style="margin:0;padding:5px;border-left:1px solid #ddd;"',
        ];
        return str_replace($search, $replace, $content);
    }

    /**
     * 正则匹配富文本图片
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     * @param   [string]      $content  [内容]
     * @param   [string]      $business [业务模块名称]
     * @param   [string]      $type     [附件类型（images 图片, file 文件, video 视频）]
     */
    public static function RichTextMatchContentAttachment($content, $business, $type = 'images')
    {
        if(!empty($content))
        {
            $pattern = '/<img.*?src=[\'|\"](\/static\/upload\/'.$type.'\/'.$business.'\/.*?[\.png|\.jpg|\.jpeg|\.gif|\.bmp|\.flv|\.swf|\.mkv|\.avi|\.rm|\.rmvb|\.mpeg|\.mpg|\.ogg|\.ogv|\.mov|\.wmv|\.mp4|\.webm|\.mp3|\.wav|\.mid|\.rar|\.zip|\.tar|\.gz|\.7z|\.bz2|\.cab|\.iso|\.doc|\.docx|\.xls|\.xlsx|\.ppt|\.pptx|\.pdf|\.txt|\.md|\.xml])[\'|\"].*?[\/]?>/';
            preg_match_all($pattern, self::AttachmentPathHandle($content), $match);
            return empty($match[1]) ? [] : $match[1];
        }
        return [];
    }

    /**
     * 货币信息
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     */
    public static function CurrencyData()
    {
        // 默认从配置文件读取货币信息
        return [
            'currency_symbol'   => getenv('currency_symbol'),
            'currency_code'     => getenv('currency_code'),
            'currency_rate'     => getenv('currency_rate'),
            'currency_name'     => getenv('currency_name'),
        ];
    }

    /**
     * 货币信息-符号
     * @author  Jenson
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     */
    public static function CurrencyDataSymbol()
    {
        $res = self::CurrencyData();
        return empty($res['currency_symbol']) ? getenv('currency_symbol') : $res['currency_symbol'];
    }

    /**
     * zip压缩包扩展可用格式
     * @author  Jenson
     * @version 1.0.0
     * @desc    description
     * @param   array           $params [description]
     */
    public static function ZipExtTypeList($params = [])
    {
        return [
            'application/zip',
            'application/octet-stream',
            'application/x-zip-compressed',
        ];
    }

    public static function AttachmentHost()
    {
        return getenv('ATTACHMENT_HOST');
    }

    /**
     * 获取当前系统所在根路径
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-09
     * @desc    description
     */
    public static function GetDocumentRoot()
    {
        // 当前所在的文档根目录
        if(!empty($_SERVER['DOCUMENT_ROOT']))
        {
            return $_SERVER['DOCUMENT_ROOT'];
        }

        // 处理iis服务器DOCUMENT_ROOT路径为空
        if(!empty($_SERVER['PHP_SELF']))
        {
            // 当前执行程序的绝对路径及文件名
            if(!empty($_SERVER['SCRIPT_FILENAME']))
            {
                return str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 -strlen($_SERVER['PHP_SELF'])));
            }

            // 当前所在绝对路径
            if(!empty($_SERVER['PATH_TRANSLATED']))
            {
                return str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 -strlen($_SERVER['PHP_SELF'])));
            }
        }

        // 服务器root没有获取到默认使用系统root_path
        return (substr(ROOT_PATH, -1) == '/') ? substr(ROOT_PATH, 0, -1) : ROOT_PATH;
    }
}
?>