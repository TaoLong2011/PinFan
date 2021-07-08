<?php


namespace app\controller\api;

use crmeb\basic\BaseController;
use think\response\Json;


class GitHook extends BaseController
{
    /**
     * @var string 根目录
     */
    private static $root;
    /**
     * @var string Git 提交秘钥
     */
    private static $token;

    public function __construct()
    {
        self::$root = app()->getRootPath();
        self::$token = 'kaolabinfen';
    }

    public function test()
    {
        return app('json')->success('无解了');

    }

    /**
     * 服务器拉取 gitee 代码
     * @return Json
     */
    public function pull()
    {
        $pwd = self::$root;
        $token = self::$token;
        $js    = app()->request->header('X-GITEE-TOKEN');
        if (!$js) {
            return app('json')->fail('网络错误');
        }
        if ($js != $token){
            return app('json')->fail('秘钥错误');
        }
        $command = sprintf('cd %s && git pull 2>&1',$pwd);
        $res1 = shell_exec($command);//
        return app('json')->success($res1 ?: 'fail');
    }

}