<?php


namespace app\common\middleware;


use app\Request;
use crmeb\exceptions\InviteException;
use think\facade\Log;
use think\Response;

class CheckInviteCode extends BaseMiddleware
{
    public function before(Request $request)
    {
        $userInfo = $request->userInfo();
        $spread_uid = $userInfo['spread_uid'];

        if (!$spread_uid){
            throw new InviteException('请填写邀请码');
        }

    }

    public function after(Response $response)
    {
        // TODO: Implement after() method.
    }

}