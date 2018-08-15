<?php

namespace TengYue\Apps\SaaS\ZSB\Models\Services\Vcode;

use TengYue\Cache\Redis;
use TengYue\Infra\Constants\ZBSApiStatus;

class VcodeService
{
    /**
     * 检查验证码
     * @param string $code
     * @param string $jid
     * @param string $ip
     * @param string $tag 标签，一般为验证位置的标识符,用于区分多个验证位
     * @throws Exception
     */
    public function checkVcode($code, $jid, $ip, $tag = '')
    {
        if (empty($code)) {
            throw new \Exception('vcode.u_vcode_empty', ZBSApiStatus::INVALID_PARAMS);
        }

        $key = md5($tag . $jid . $ip);
        $redis = Redis::getInstance();
        $realCode = $redis->get($key);

        //验证无论成功与否都重新刷新一遍vcode值,防止vcode反复重试
        $redis->delete($key);

        //检查vcode值是否匹配
        if (!$realCode || strtolower($realCode) != strtolower($code)) {
            throw new \Exception('vcode.u_vcode_error', ZBSApiStatus::INVALID_PARAMS);
        }

        return true;
    }

    /**
     * 获取验证码图片
     * @param $jid
     * @param $ip
     * @param $imgWidth
     * @param $imgHeight
     * @param string $tag
     * @param int $fontNum
     * @param int $invertNum
     * @return string
     */
    public function getVcode($jid, $ip, $imgWidth, $imgHeight, $tag = '', $fontNum = 6, $invertNum = 2)
    {
        $outputs = self::getRandHanzis($fontNum, $invertNum);
        $vcode = [];
        foreach ($outputs as $idx => $output) {
            if (!$output[1]) {
                $vcode[] = $idx;
            }
        }
        $vcode = implode('-', $vcode);

        // 验证码5分钟过期
        $key = md5($tag . $jid . $ip);
        $redis = Redis::getInstance();
        $redis->set($key, $vcode, 300);

        return self::getVcodeImage($outputs, $imgWidth, $imgHeight);
    }

    /**
     * 获取随机汉字
     * @param int $num
     * @param int $invertNum
     * @return array
     */
    private static function getRandHanzis($num = 5, $invertNum = 2)
    {
        $segs = [];
        for ($i = 16; $i <= 55; $i++) {
            $segs[] = [$i, 1, 94];
        }
        $len = count($segs);
        $segs[$len - 1][2] = 89;

        $outputs = [];

        for ($i = 0; $i < $num; $i++) {
            $index = rand(0, $len - 1);
            $seg = $segs[$index];

            $high = $seg[0];
            $low = rand($seg[1], $seg[2]);

            // 生成一个基本算是随机的数字
            $outputs[] = [chr(0xa0 + $high) . chr(0xa0 + $low), true, crc32(microtime(true) . $high . $low)];
        }
        $randIndexes = array_rand($outputs, $invertNum);
        if (is_int($randIndexes)) {
            $outputs[$randIndexes][1] = false;
        } else {
            foreach ($randIndexes as $idx) {
                $outputs[$idx][1] = false;
            }
        }
        return $outputs;
    }

    /**
     * 获取验证码图片
     * @param $outputs
     * @return string
     */
    private static function getVcodeImage($outputs, $width, $height)
    {
        $len = count($outputs);
        $fontWidth = $width / $len;
        $width = $fontWidth * $len;
        $fontAngle = 7;

        $image = new \Imagick();
        $draw = new \ImagickDraw();
        $pixel = new \ImagickPixel('white');
        $image->newImage($width, $height, $pixel);
        $draw->setFont(STATIC_ROOT . 'fonts/mini.ttf');
        $draw->setFontSize($fontWidth * 1.1);
        $image->addNoiseImage(\Imagick::NOISE_POISSON, \Imagick::CHANNEL_GRAY);
        for ($i = 0; $i < $len; $i++) {
            $color = sprintf('rgb(%d,%d,%d)', rand(100, 250), rand(0, 160), rand(50, 100));
            if ($outputs[$i][1]) {
                $left = $i * $fontWidth;
                $top = $height * 6 / 7;
                $angle = rand(0, $fontAngle * 2) - $fontAngle;
            } else {
                $left = ($i + 1) * $fontWidth;
                $top = $height / 6;
                $angle = rand(180, 180 + $fontAngle * 2) - $fontAngle;
            }
            $draw->setFillColor($color);
            $draw->setFillOpacity(rand(5, 8) / 10);

            $image->annotateImage($draw, $left, $top, $angle, iconv('gb2312', 'utf-8', $outputs[$i][0]));
        }
        $image->setImageFormat('png');

        // 模糊图像
//         $image->gaussianBlurImage(1, 2);
        return $image->getImageBlob();
    }
}
