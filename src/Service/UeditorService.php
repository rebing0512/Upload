<?php
// +----------------------------------------------------------------------
// | Jenson Base
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://jenson.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Jenson
// +----------------------------------------------------------------------
namespace Jenson\Upload\Service;

use ResourcesService;
//use app\service\SystemBaseService;
use Jenson\Upload\Helper\Helper;
use Jenson\Upload\Uploader;

/**
 * 百度编辑器附件服务层
 * @author   Jenson
 * @blog     http://jenson.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class UeditorService
{
    private static $current_action;
    private static $current_config;
    private static $params;
    private static $path_type;

    /**
     * 请求入口
     * @author  Jenson
     * @blog    http://jenson.gg/
     * @version 1.0.0
     * @date    2019-08-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function Run($params = [])
    {
        // 配置信息
        self::$params = $params;
        self::$current_config = parse_ini_file('../config/ueditor.ini', true);//MyConfig('ueditor');
        self::$current_action = isset($params['action']) ? $params['action'] : '';
        self::$path_type = isset($params['path_type']) ? $params['path_type'] : Helper::PathToParams('path_type', 'other');

        // action
        $ret = Helper::DataReturn('请求action有误', -1);
        switch(self::$current_action)
        {
            // 配置信息
            case 'config':
                $ret = Helper::DataReturn('success', 0, self::$current_config);
                break;

            /* 上传图片 */
            case 'uploadimage':
            /* 上传涂鸦 */
            case 'uploadscrawl':
            /* 上传视频 */
            case 'uploadvideo':
            /* 上传文件 */
            case 'uploadfile':
                $ret = self::ActionUpload();
                break;

            /* 列出图片 */
            case 'listimage':
            /* 列出文件 */
            case 'listfile':
            /* 列出视频 */
            case 'listvideo':
                $ret = self::ActionList();
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $ret = self::ActionCrawler();
                break;

            /* 删除文件 */
            case 'deletefile':
                $ret = self::DeleteFile();
                break;
        }
        return $ret;
    }

    /**
     * 文件删除
     * @author   Jenson
     * @blog    http://jenson.gg/
     * @version 1.0.0
     * @date    2018-12-10
     * @desc    description
     */
    private static function DeleteFile()
    {
        $pamars = array_merge($_POST,$_GET);
        return ResourcesService::AttachmentDelete($pamars);
    }

    /**
     * [ActionUpload 上传配置]
     * @author   Jenson
     * @blog     http://jenson.gg/
     * @version  0.0.1
     * @datetime 2017-01-17T22:45:06+0800
     */
    private static function ActionUpload()
    {
        $attachment_type = "file";
        switch(htmlspecialchars(self::$current_action))
        {
            case 'uploadimage':
                $temp_config = array(
                        "pathFormat" => self::$current_config['imagePathFormat'],
                        "maxSize" => self::$current_config['imageMaxSize'],
                        "allowFiles" => self::$current_config['imageAllowFiles']
                    );
                $field_name = self::$current_config['imageFieldName'];
                $attachment_type = "image";
                break;

            case 'uploadscrawl':
                $temp_config = array(
                        "pathFormat" => self::$current_config['scrawlPathFormat'],
                        "maxSize" => self::$current_config['scrawlMaxSize'],
                        "allowFiles" => self::$current_config['scrawlAllowFiles'],
                        "oriName" => "scrawl.png"
                    );
                $field_name = self::$current_config['scrawlFieldName'];
                $attachment_type = "scrawl";
                break;

            case 'uploadvideo':
                $temp_config = array(
                        "pathFormat" => self::$current_config['videoPathFormat'],
                        "maxSize" => self::$current_config['videoMaxSize'],
                        "allowFiles" => self::$current_config['videoAllowFiles']
                    );
                $field_name = self::$current_config['videoFieldName'];
                $attachment_type = "video";
                break;

            case 'uploadfile':
            default:
                $temp_config = array(
                        "pathFormat" => self::$current_config['filePathFormat'],
                        "maxSize" => self::$current_config['fileMaxSize'],
                        "allowFiles" => self::$current_config['fileAllowFiles']
                    );
                $field_name = self::$current_config['fileFieldName'];
                $attachment_type = "file";
        }

        /* 生成上传实例对象并完成上传 */
        $up = new Uploader($field_name, $temp_config, $attachment_type);

        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
         *     "url" => "",            //返回的地址
         *     "path" => "",           //绝对地址
         *     "title" => "",          //新文件名
         *     "original" => "",       //原始文件名
         *     "type" => ""            //文件类型
         *     "size" => "",           //文件大小
         *     "hash" => "",           //sha256值
         * )
         */
        $data = $up->getFileInfo();
        if(isset($data['state']) && $data['state'] == 'SUCCESS')
        {
            if($attachment_type == 'scrawl')
            {
                $attachment_type = 'image';
            }
            $data['type'] = $attachment_type;
            $data['path_type'] = self::$path_type;
            $data['not_delete_local_file'] = self::$params['not_delete_local_file'] ?? 0;
            return ResourcesService::AttachmentAdd($data);
        }
        return Helper::DataReturn(isset($data['state']) ? $data['state'] : '上传失败', -1);
    }

    /**
     * [ActionList 文件列表]
     * @author   Jenson
     * @blog     http://jenson.gg/
     * @version  0.0.1
     * @datetime 2017-01-17T22:55:16+0800
     */
    private static function ActionList()
    {
        /* 判断类型 */
        switch(self::$current_action)
        {
            /* 列出视频 */
            case 'listvideo':
                $allow_files = self::$current_config['videoManagerAllowFiles'];
                $list_size = self::$current_config['videoManagerListSize'];
                $path = self::$current_config['videoManagerListPath'];
                break;
            /* 列出文件 */
            case 'listfile':
                $allow_files = self::$current_config['fileManagerAllowFiles'];
                $list_size = self::$current_config['fileManagerListSize'];
                $path = self::$current_config['fileManagerListPath'];
                break;

            /* 列出图片 */
            case 'listimage':
            default:
                $allow_files = self::$current_config['imageManagerAllowFiles'];
                $list_size = self::$current_config['imageManagerListSize'];
                $path = self::$current_config['imageManagerListPath'];
        }
        $allow_files = substr(str_replace(".", "|", join("", $allow_files)), 1);

        /* 获取参数 */
        $size = isset(self::$params['size']) ? intval(self::$params['size']) : $list_size;
        $start = isset(self::$params['start']) ? intval(self::$params['start']) : 0;
        $end = $start + $size;

        // 参数
        $params = [
            'm'         => $start,
            'n'         => $size,
            'where'     => [
                ['type', '=', substr(self::$current_action, 4)],
                ['path_type', '=', self::$path_type]
            ],
        ];

        // 搜索关键字
        if(!empty(self::$params['keywords']))
        {
            $params['where'][] = ['original', 'like', '%'.self::$params['keywords'].'%'];
        }

        // 总数
        $total = ResourcesService::AttachmentTotal($params['where']);

        // 获取数据
        if($total > 0)
        {
            $ret = ResourcesService::AttachmentList($params);
            if(!empty($ret['data']))
            {
                return DataReturn('success', 0, [
                    'start' => $start,
                    'total' => $total,
                    'list'  => $ret['data'],
                ]);
            }
        }

        return Helper::DataReturn('no_data', -1);
    }

    /**
     * [ActionCrawler 抓取远程文件]
     * @author   Jenson
     * @blog     http://jenson.gg/
     * @version  0.0.1
     * @datetime 2017-01-17T23:08:29+0800
     */
    private static function ActionCrawler()
    {
        $temp_config = array(
                "pathFormat" => self::$current_config['catcherPathFormat'],
                "maxSize" => self::$current_config['catcherMaxSize'],
                "allowFiles" => self::$current_config['catcherAllowFiles'],
                "oriName" => "remote.png"
            );
        $field_name = self::$current_config['catcherFieldName'];

        // 当前站点域名或者附件域名不下载
        $attachment_host = GetUrlHost(SystemBaseService::AttachmentHost());

        // 抓取远程图片
        $list = array();
        $source = isset(self::$params[$field_name]) ? self::$params[$field_name] : self::$params[$field_name];
        foreach($source as $imgUrl)
        {
            if(GetUrlHost($imgUrl) != $attachment_host)
            {
                $up = new Uploader($imgUrl, $temp_config, "remote");
                /**
                 * 得到上传文件所对应的各个参数,数组结构
                 * array(
                 *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
                 *     "url" => "",            //返回的地址
                 *     "path" => "",           //绝对地址
                 *     "title" => "",          //新文件名
                 *     "original" => "",       //原始文件名
                 *     "type" => ""            //文件类型
                 *     "size" => "",           //文件大小
                 *     "hash" => "",           //sha256值
                 * )
                 */
                $data = $up->getFileInfo();
                if(isset($data['state']) && $data['state'] == 'SUCCESS')
                {
                    $data['type'] = 'image';
                    $data['path_type'] = self::$path_type;
                    $data['not_delete_local_file'] = self::$params['not_delete_local_file'] ?? 0;
                    $ret = ResourcesService::AttachmentAdd($data);
                    if($ret['code'] == 0)
                    {
                        $ret['data']['source'] = htmlspecialchars($imgUrl);
                        array_push($list, $ret['data']);
                    }
                }
            }
        }
        if(!empty($list))
        {
            return Helper::DataReturn('success', 0, $list);
        }
        return Helper::DataReturn('没有相关数据', -1);
    }
}
?>