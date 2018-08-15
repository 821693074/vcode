<?php
/**
 * 获取验证码
 * Created by PhpStorm.
 * User: hugo
 * Date: 2018/8/15
 * Time: 15:21
 */

namespace TengYue\Apps\SaaS\ZSB\Controllers\common\vcode;

use TengYue\Apps\SaaS\ZSB\Controllers\abstracts\wechatController;
use TengYue\Apps\SaaS\ZSB\Models\Services\Vcode\VcodeService;
use Zeus\Mvc\View\EmptyView;

class getvcodeController extends wechatController
{
    const VCODE_TAG = 'login';
    const VCODE_CHECK = 'brush';

    /**
     * 获取验证码
     * @return EmptyView
     */
    public function v1Action()
    {
        $w = self::$REQUEST->getInt('w') ?? 80;
        $h = self::$REQUEST->getInt('h') ?? 30;

        $vcodeService = new VcodeService();
        header("Content-type: image/png");
        echo $image = $vcodeService->getVcode('11111232323', '11.23.1.2', $w, $h, self::VCODE_CHECK, 5, 2);

        return new EmptyView();
    }
}