<?php

// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------


namespace app\controller\api;


use app\common\repositories\system\CacheRepository;
//use app\Request;
use crmeb\basic\BaseController;
use app\common\repositories\store\shipping\ExpressRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\user\UserVisitRepository;
use app\common\repositories\wechat\TemplateMessageRepository;
use crmeb\services\AlipayService;
use crmeb\services\MiniProgramService;
use crmeb\services\UploadService;
use crmeb\services\WechatService;
use crmeb\services\MinPayService;
use Exception;
use Joypack\Tencent\Map\Bundle\Location;
use Joypack\Tencent\Map\Bundle\LocationOption;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;
use think\Response;
use think\response\Html;
use think\facade\Request;

/**
 * Class Common
 * @package app\controller\api
 * @author xaboy
 * @day 2020/5/28
 */
class Common extends BaseController
{
    /**
     * @return mixed
     * @author xaboy
     * @day 2020/5/28
     */
    public function hotKeyword()
    {
        $keyword = systemGroupData('hot_keyword');
        return app('json')->success($keyword);
    }

    public function express(ExpressRepository $repository)
    {
        return app('json')->success($repository->options());
    }

    public function menus()
    {
        return app('json')->success(['banner' => systemGroupData('my_banner'), 'menu' => systemGroupData('my_menus')]);
    }

    public function refundMessage()
    {
        return app('json')->success(explode("\n", systemConfig('refund_message')));
    }

    public function config()
    {
        $config = systemConfig(['mer_location', 'alipay_open', 'hide_mer_status', 'mer_intention_open', 'share_info', 'share_title', 'share_pic', 'store_user_min_recharge', 'recharge_switch', 'balance_func_status', 'yue_pay_status', 'site_logo', 'routine_logo', 'site_name', 'login_logo']);
        $make = app()->make(TemplateMessageRepository::class);
        $sys_intention_agree = app()->make(CacheRepository::class)->getResult('sys_intention_agree');
        if (!$sys_intention_agree) {
            $sys_intention_agree = systemConfig('sys_intention_agree');
        }
        $config['sys_intention_agree'] = $sys_intention_agree;
        $config['tempid'] = $make->getSubscribe();
        return app('json')->success($config);
    }

    /**
     * @param GroupDataRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/3
     */
    public function userRechargeQuota(GroupDataRepository $repository)
    {
        $recharge_quota = $repository->groupDataId('user_recharge_quota', 0);
        $recharge_attention = explode("\n", systemConfig('recharge_attention'));
        return app('json')->success(compact('recharge_quota', 'recharge_attention'));
    }

    /**
     * @param $field
     * @return mixed
     * @author xaboy
     * @day 2020/5/28
     */
    public function uploadImage($field)
    {
        $file = $this->request->file($field);
        if (!$file)
            return app('json')->fail('请上传图片');
        $file = is_array($file) ? $file[0] : $file;
        validate(["$field|图片" => [
            'fileSize' => 1024 * 1024 * 5, // 5Mb
            'fileExt' => 'jpg,jpeg,png,bmp,gif',
            'fileMime' => 'image/jpeg,image/png,image/gif',
            function ($file) {
                $ext = $file->extension();
                if ($ext != strtolower($file->extension())) {
                    return '图片后缀必须为小写';
                }
                return true;
            }
        ]])->check([$field => $file]);

        $upload = UploadService::create();
        $info = $upload->to('def')->move($field);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        $res['dir'] = tidy_url($res['dir']);
        return app('json')->success(['path' => $res['dir']]);
    }

    /**
     * @return Response
     * @author xaboy
     * @day 2020/6/3
     */
    public function wechatNotify()
    {
        try {
            return response(WechatService::create()->handleNotify()->getContent());
        } catch (Exception $e) {
            Log::info('支付回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
    }

    public function routineNotify()
    {
        try {
            return response(MiniProgramService::create()->handleNotify()->getContent());
        } catch (Exception $e) {
            Log::info('支付回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
    }

    public function alipayNotify($type)
    {

        if (!in_array($type, ['order', 'user_recharge', 'presell']))
            throw new ValidateException('参数错误');
        try {
            AlipayService::create()->notify($type, $this->request->post());
        } catch (Exception $e) {
            Log::info('支付宝回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
    }

    /**
     * minpay回调
     * @Author : UJB
     * @Date : 2021/3/24
     * @param Request $request
     * @return mixed
     */
    public function minpayNotify(Request $request)
    {
        $params = $request::param(false);
        if (empty($params)){
            $params = $this->request->post();
        }
        try {
            MinPayService::create()->minpayNotify($params);
        }catch (Exception $e){
            Log::info('minpay支付回调失败:' . var_export([$e->getMessage(), $e->getFile() . ':' . $e->getLine()], true));
        }
        return app('json')->success();
    }

    /**
     * 获取图片base64
     * @return mixed
     */
    public function get_image_base64()
    {
        list($imageUrl, $codeUrl) = $this->request->params([
            ['image', ''],
            ['code', ''],
        ], true);
        try {
            $codeTmp = $code = $codeUrl ? image_to_base64($codeUrl) : '';
            if (!$codeTmp) {
                $putCodeUrl = put_image($codeUrl);
                $code = $putCodeUrl ? image_to_base64('./runtime/temp' . $putCodeUrl) : '';
                $code && unlink('./runtime/temp' . $putCodeUrl);
            }

            $imageTmp = $image = $imageUrl ? image_to_base64($imageUrl) : '';
            if (!$imageTmp) {
                $putImageUrl = put_image($imageUrl);
                $image = $putImageUrl ? image_to_base64('./runtime/temp' . $putImageUrl) : '';
                $image && unlink('./runtime/temp' . $putImageUrl);
            }
            return app('json')->success(compact('code', 'image'));
        } catch (Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    public function home()
    {
        $banner = systemGroupData('home_banner', 1, 10);
        $menu = systemGroupData('home_menu');
        $hot = systemGroupData('home_hot', 1, 4);
        $ad = systemConfig(['home_ad_pic', 'home_ad_url']);
        $category = app()->make(StoreCategoryRepository::class)->getTwoLevel();
        return app('json')->success(compact('banner', 'menu', 'hot', 'ad', 'category'));
    }

    public function visit()
    {
        if (!$this->request->isLogin()) return app('json')->success();
        [$page, $type] = $this->request->params(['page', 'type'], true);
        $uid = $this->request->uid();
        if (!$page || !$uid) return app('json')->fail();
        $userVisitRepository = app()->make(UserVisitRepository::class);
        $type == 'routine' ? $userVisitRepository->visitSmallProgram($uid, $page) : $userVisitRepository->visitPage($uid, $page);
        return app('json')->success();
    }

    public function hotBanner($type)
    {
        if (!in_array($type, ['new', 'hot', 'best', 'good']))
            $data = [];
        else
            $data = systemGroupData($type . '_home_banner');
        return app('json')->success($data);
    }

    public function pay_key($key)
    {
        $cache = Cache::store('file');
        if (!$cache->has('pay_key' . $key)) {
            return app('json')->fail('支付链接不存在');
        }
        return app('json')->success($cache->get('pay_key' . $key));
    }

    public function lbs_geocoder()
    {
        $data = explode(',', $this->request->param('location', ''));
        $locationOption = new LocationOption(systemConfig('tx_map_key'));
        $locationOption->setLocation($data[0] ?? '', $data[1] ?? '');
        $location = new Location($locationOption);
        $res = $location->request();
        if ($res->error) {
            return app('json')->fail($res->error);
        }
        if ($res->status) {
            return app('json')->fail($res->message);
        }
        if (!$res->result) {
            return app('json')->fail('获取失败');
        }
        return app('json')->success($res->result);
    }
}
