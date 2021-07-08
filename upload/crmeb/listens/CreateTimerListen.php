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


namespace crmeb\listens;


use app\common\repositories\store\broadcast\BroadcastGoodsRepository;
use app\common\repositories\store\broadcast\BroadcastRoomRepository;
use crmeb\interfaces\ListenerInterface;
use Swoole\Process;
use Swoole\Server;
use Swoole\Timer;
use think\facade\Log;
use think\swoole\FileWatcher;
use function Swoole\Coroutine\run;

class CreateTimerListen implements ListenerInterface
{

    public function handle($event): void
    {
        $process = new Process(function () {
            app()->event->trigger('create_timer');
        }, false, 0, true);

        app()->make(Server::class)->addProcess($process);
    }
}