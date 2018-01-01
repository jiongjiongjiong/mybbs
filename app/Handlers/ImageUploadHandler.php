<?php
/**
 * Created by PhpStorm.
 * User: congzhang
 * Date: 2018/1/1
 * Time: 下午1:24
 */

namespace App\Handlers;

use Image;

class ImageUploadHandler
{
    //只允许以下后缀名的图片文件上传
    protected $allow_ext = ["png","jpg","gif","jpeg"];

    public function save($file, $folder, $file_prefix, $max_width = false)
    {
        //构建存储的文件夹规则，如：uploads/images/avatars/201801/01/
        //文件夹切割能让查找效率更高。
        $folder_name = "uploads/images/$folder/" . date("Ym", time()) . '/' .date("d", time()) . '/';

        //文件具体存储的物理路径，`public_path()` 获取的是`public` 文件夹的物理路径。
        //如：/home/vagrant/Code/larabbs/public/uploads/images/avatars/201801/01/
        $upload_path = public_path() . '/' . $folder_name;

        //获取文件的后缀名，因图片从剪贴板里黏贴时后缀名为空，所以此处确保后缀一直存在
        $extension = strtolower($file->getClientOriginalExtension()) ?:'png';

        //拼接文件名，加前缀是为了增加辨析度，前缀可以是相关数据模型的ID
        //如：1_1493521050_7bvc9v9ujP.png
        $filename = $file_prefix . '_' . time() . '_' . str_random(10) . '.' . $extension;

        //如果上传的部署图片将终止操作
        if ( !in_array($extension, $this->allow_ext)){
            return false;
        }

        //将图片移动到我们的目标存储路径中
        $file->move($upload_path, $filename);

        if ($max_width && $extension != 'gif'){
            //此类封装的函数用于裁剪图片
            $this->reduceSize($upload_path . '/' . $filename, $max_width);
        }

        return [
            'path'  => config('app.url') . "/$folder_name/$filename"
        ];
    }

    public function reduceSize($file_path, $max_width)
    {
        $image = Image::make($file_path);

        $image->resize($max_width, null, function ($constraint){
           //设定宽度是 $max_width,高度等比列缩放
            $constraint->aspectRatio();

            //防止裁图时图片尺寸变大
            $constraint->upsize();
        });

        $image->save();
    }
}