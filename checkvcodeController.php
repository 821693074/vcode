<?php
/**
 *
 * Created by PhpStorm.
 * User: hugo
 * Date: 2018/8/15
 * Time: 16:41
 */

namespace TengYue\Apps\SaaS\ZSB\Controllers\common\vcode;

use TengYue\Apps\SaaS\ZSB\Controllers\abstracts\wechatController;
use TengYue\Apps\SaaS\ZSB\Models\Services\Vcode\VcodeService;
use Zeus\Mvc\View\EmptyView;

class checkvcodeController extends wechatController
{
    const VCODE_TAG = 'login';      //登录
    const VCODE_CHECK = 'brush';    //防刷

    /**
     * 验证图形验证码
     * @return EmptyView
     */
    public function v1Action()
    {
        $code = self::$REQUEST->getString('_vcode');

        $vcodeService = new VcodeService();
        $vcodeService->checkVcode($code, '11111232323', '11.23.1.2', self::VCODE_CHECK);

        return $this->jsonData(true);
    }

}