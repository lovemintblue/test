<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * 验证码
 */
class CaptchaUtil
{

    /**
     * 验证码中使用的字符，01IO容易混淆，建议不用
     * @var string
     */
    public $codeSet = '3456789ABCDEFGHJKMPQRTWXY';
    public $fontSize = 25;  // 验证码字体大小(px)
    public $useCurve = true; // 是否画混淆曲线
    public $useNoise = true; // 是否添加杂点
    public $imageH = 50;  // 验证码图片宽
    public $imageL = 130;  // 验证码图片长
    public $length = 4;  // 验证码位数
    public $fonts = array('Nexabold.ttf', 'Arimo.ttf', 'alger.ttf');


    protected $code = "";
    protected $_image = null;  // 验证码图片实例
    protected $_color = null;  // 验证码字体颜色

    protected $_mode = 1;

    protected $_isBase64 = false;


    /**
     * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数)
     *
     *  高中的数学公式咋都忘了涅，写出来
     * 正弦型函数解析式：y=Asin(ωx+φ)+b
     *  各常数值对函数图像的影响：
     *  A：决定峰值（即纵向拉伸压缩的倍数）
     *  b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
     *  φ：决定波形与X轴位置关系或横向移动距离（左加右减）
     *  ω：决定周期（最小正周期T=2π/∣ω∣）
     *
     */
    protected function _writeCurve()
    {

        $A = mt_rand(1, intval($this->imageH / 2));     // 振幅
        $b = mt_rand(intval(-$this->imageH / 4), intval($this->imageH / 4)); // Y轴方向偏移量
        $f = mt_rand(intval(-$this->imageH / 4), intval($this->imageH / 4)); // X轴方向偏移量
        $T = mt_rand(intval($this->imageH * 1.5), intval($this->imageL * 2)); // 周期
        $w = (2 * M_PI) / $T;

        $px1 = 0; // 曲线横坐标起始位置
        $px2 = mt_rand(intval($this->imageL / 2), intval($this->imageL * 0.667)); // 曲线横坐标结束位置
        for ($px = $px1; $px <= $px2; $px = $px + 0.9) {
            if ($w != 0) {
                $py = (int)($A * sin($w * $px + $f) + $b + $this->imageH / 2); // y = Asin(ωx+φ) + b
                $i = (int)(($this->fontSize - 6) / 4);
                while ($i > 0) {
                    imagesetpixel($this->_image, intval($px + $i), intval($py + $i), $this->_color); // 这里画像素点比imagettftext和imagestring性能要好很多
                    $i--;
                }
            }
        }

        $A = mt_rand(1, intval($this->imageH / 2));     // 振幅
        $f = mt_rand(intval(-$this->imageH / 4), intval($this->imageH / 4)); // X轴方向偏移量
        $T = mt_rand(intval($this->imageH * 1.5), intval($this->imageL * 2)); // 周期
        $w = (2 * M_PI) / $T;
        $b = $py - $A * sin($w * $px + $f) - $this->imageH / 2;
        $px1 = $px2;
        $px2 = $this->imageL;
        for ($px = $px1; $px <= $px2; $px = $px + 0.9) {
            if ($w != 0) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i = (int)(($this->fontSize - 8) / 4);
                while ($i > 0) {
                    imagesetpixel($this->_image, intval($px + $i), intval($py + $i), $this->_color); // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多
                    $i--;
                }
            }
        }
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    protected function _writeNoise()
    {
        for ($i = 0; $i < 10; $i++) {
            //杂点颜色
            $noiseColor = imagecolorallocate(
                $this->_image,
                mt_rand(150, 225),
                mt_rand(150, 225),
                mt_rand(150, 225)
            );
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring(
                    $this->_image,
                    5,
                    mt_rand(-10, $this->imageL),
                    mt_rand(-10, $this->imageH),
                    $this->codeSet[mt_rand(0, 27)], // 杂点文本为随机的字母或数字
                    $noiseColor
                );
            }
        }
    }

    //输出
    protected function outPut($isBase64 = false)
    {
        $tempFile = BASE_PATH . '/runtime/captcha/';
        if (!file_exists($tempFile)) {
            mkdir($tempFile, 0777, true);
        }
        $tempFile .= md5(microtime(true) . '');
        // 输出图像
        imagepng($this->_image, $tempFile);
        imagedestroy($this->_image);
        if ($isBase64) {
            $byte = $this->base64EncodeImage($tempFile);
        } else {
            $byte = file_get_contents($tempFile);
        }
        unlink($tempFile);
        return $byte;
    }

    /**
     * @param $isBase64
     */
    public function setOutBase64($isBase64)
    {
        $this->_isBase64 = $isBase64 ? true : false;
    }

    /**
     * 加密图片
     * @param  $imageFile
     * @return string
     */
    public function base64EncodeImage($imageFile)
    {
        $image_info = getimagesize($imageFile);
        $file = fopen($imageFile, 'r');
        $image_data = fread($file, filesize($imageFile));
        fclose($file);
        return 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
    }


    //获取验证码
    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * 输出验证码并把验证码的值保存的session中
     * @return false|string
     */
    public function doImg()
    {

        // 图片宽(px)
        $this->imageL || $this->imageL = $this->length * $this->fontSize * 1.5 + $this->fontSize * 1.5;

        // 图片高(px)
        $this->imageH || $this->imageH = $this->fontSize * 2;
        // 建立一幅 $this->imageL x $this->imageH 的图像

        // 设置背景
        $this->_image = imagecreatetruecolor($this->imageL, $this->imageH);
        $color = imagecolorallocate($this->_image, mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255));
        imagefilledrectangle($this->_image, 0, $this->imageH, $this->imageL, 0, $color);

        // 验证码字体随机颜色
        $this->_color = imagecolorallocate($this->_image, mt_rand(1, 120), mt_rand(1, 120), mt_rand(1, 120));

        if ($this->useNoise) {
            // 绘杂点
            self::_writeNoise();
        }
        if ($this->useCurve) {
            // 绘干扰线
            self::_writeCurve();
        }

        $fontName = $this->fonts[rand(0, count($this->fonts) - 1)];
        $font = BASE_PATH . '/app/Resource/fonts/' . $fontName;
        $_x = $this->imageL / $this->length;


        // 绘验证码
        $code = array(); // 验证码
        //$codeNX = 0; // 验证码第N个字符的左边距
        for ($i = 0; $i < $this->length; $i++) {
            $code[$i] = $this->codeSet[mt_rand(0, strlen($this->codeSet) - 1)];
            //$codeNX += mt_rand($this->fontSize, $this->fontSize*1.2);
            // 写一个验证码字符
            imagettftext($this->_image, $this->fontSize, mt_rand(-30, 30), intval($_x * $i + mt_rand(1, 5)), intval($this->imageH / 1.4), $this->_color, $font, $code[$i]);
        }
        $this->code = join('', $code);

        return $this->outPut($this->_isBase64);
    }
}
