<?php

declare(strict_types=1);

namespace mulo\library\mail;

use mulo\exception\MuloException;
use think\facade\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use mulo\tpmodel\Config;

class Mail
{

    /**
     * 邮件配置
     */
    protected $config = null;
    /**
     * 网站名称
     */
    protected $siteName = null;
    
    /**
     * 验证码事件，必传
     *
     * @var [type]
     */
    protected $code_event = null;

    protected $expire = 300;

    protected $maxTimes = 3;

    /**
     * mail 实例
     * 
     * @var PHPMailer
     */
    protected $mail = null;

    /**
     * 缓存实例
     */
    protected $cache = null;

    public function __construct()
    {
        $this->config = Config::getConfigs('basic.mail');
        $this->siteName = Config::getConfigField('basic.site.name');
        $this->cache = cache()->store('persistent');

        $this->init();
    }


    /**
     * 设置发送人
     *
     * @param string $email
     * @param string $name
     * @return self
     */
    public function setFrom($mail = '', $name = '')
    {
        $mail = $mail ?: $this->config['from'];
        $name = $name ?: $this->config['from_name'];
        $this->mail->setFrom($mail, $name);

        return $this;
    }


    /**
     * 设置接收人
     *
     * @param [type] $address
     * @param string $name
     * @return self
     */
    public function addAddress($address, $name = '') 
    {
        $this->mail->addAddress($address, $name);
        return $this;
    }


    /**
     * 批量设置接收人
     *
     * @param array $addresses
     * @return self
     */
    public function addAddresses($addresses)
    {
        $this->batchSet($addresses, 'addAddress');

        return $this;
    }


    /**
     * 添加回复人
     *
     * @param [type] $address
     * @param string $name
     * @return void
     */
    public function addReplyTo($address, $name = '') 
    {
        $this->mail->addReplyTo($address, $name);
        return $this;
    }


    /**
     * 批量设置回复人
     *
     * @param array $addresses
     * @return self
     */
    public function addReplyTos($addresses)
    {
        $this->batchSet($addresses, 'addReplyTo');

        return $this;
    }


    /**
     * 添加抄送人
     *
     * @param [type] $address
     * @param string $name
     * @return void
     */
    public function addCC($address, $name = '') 
    {
        $this->mail->addCC($address, $name);
        return $this;
    }


    /**
     * 批量设置抄送人
     *
     * @param array $addresses
     * @return self
     */
    public function addCCs($addresses)
    {
        $this->batchSet($addresses, 'addCC');

        return $this;
    }




    /**
     * 添加秘密抄送人
     *
     * @param [type] $address
     * @param string $name
     * @return void
     */
    public function addBCC($address, $name = '') 
    {
        $this->mail->addBCC($address, $name);
        return $this;
    }


    /**
     * 批量设置秘密抄送人
     *
     * @param array $addresses
     * @return self
     */
    public function addBCCs($addresses)
    {
        $this->batchSet($addresses, 'addBCC');

        return $this;
    }


    /**
     * 添加附件
     *
     * @param string $path
     * @param string $name
     * @param string $encoding
     * @param string $type
     * @param string $disposition
     * @return self
     */
    public function addAttachment(
        $path, 
        $name = '',
        $encoding = PHPMailer::ENCODING_BASE64,
        $type = '',
        $disposition = 'attachment')
    {
        $this->mail->addAttachment($path, $name, $encoding, $type, $disposition);

        return $this;
    }


    /**
     * 批量添加附件
     *
     * @param array $attachments
     * @return self
     */
    public function addAttachments($attachments) 
    {
        $this->batchSet($attachments, 'addAttachment');

        return $this;
    }


    /**
     * 添加邮件主题
     *
     * @param string $subject
     * @return self
     */
    public function setSubject($subject) 
    {
        $this->mail->Subject = $subject;

        return $this;
    }


    /**
     * 添加邮件主体
     *
     * @param string $body
     * @return self
     */
    public function setBody($body) 
    {
        $this->mail->Body = $body;

        return $this;
    }



    /**
     * 添加邮件附件信息
     *
     * @param string $altBody
     * @return self
     */
    public function setAltBody($altBody) 
    {
        $this->mail->AltBody = $altBody;

        return $this;
    }


    /**
     * 设置邮件语言
     *
     * @param string $language
     * @param string $lang_path
     * @return self
     */
    public function setLanguage($language = 'zh_cn', $lang_path = '') 
    {
        $this->mail->setLanguage($language, $lang_path);

        return $this;
    }



    /**
     * 发送邮件
     *
     * @param string $mail 接收人
     * @param string $name 接收人姓名
     * @return boolean
     */
    public function send($mail = '', $name = '')
    {
        try {
            if ($mail) {
                $this->addAddress($mail, $name);
            }
            return $this->mail->send();
        } catch (Exception $e) {
            throw new MuloException($this->mail->ErrorInfo);
        }
    }


    /**
     * 发送验证码（不需要设置邮件内容，会自动生成）
     *
     * @param string $code_event 验证码事件
     * @return boolean
     */
    public function sendCode($mail, $code_event)
    {
        try {
            $this->code_event = $code_event;

            $template = $this->config['code_template'];
            $code = $this->makeCode($mail);

            $body = str_replace('{$code}', $code, $template);
            if ($body == $template) {
                $body .= $code;
            }

            // 设置收件人
            $this->addAddress($mail);
            // 设置邮件主题
            $this->setSubject($this->siteName . ' - 邮箱验证码');
            // 设置邮件内容
            $this->setBody($body);

            return $this->mail->send();
        } catch (Exception $e) {
            throw new MuloException($this->mail->ErrorInfo);
        }
    }



    /**
     * 检测验证码是否正确
     *
     * @param string $mail
     * @param string $code_event
     * @param string $code
     * @return boolean
     */
    public function check($mail, $code_event, $code, $exception = true) : bool
    {
        $this->code_event = $code_event;
        $code_key = $this->getKeyName($mail);
        $times_key = $this->getKeyName($mail, 'times');

        $cacheCode = $this->cache->get($code_key);
        $times = $this->cache->get($times_key);

        if (!$cacheCode || $times >= $this->maxTimes) {
            // 过期则清空该手机验证码
            $this->cache->delete($code_key);
            $this->cache->delete($times_key);
            if ($exception) throw new MuloException('验证码不正确');
            return false;
        }

        if ($code != $cacheCode) {
            $this->cache->set($times_key, ($times + 1), $this->expire);
            if ($exception) throw new MuloException('验证码不正确');
            return false;
        }

        return true;
    }


    /**
     * 生成验证码
     *
     * @param string $mail 接收人邮箱
     * @return string
     */
    private function makeCode($mail) : string
    {
        $code = strval(mt_rand(100000, 999999));

        // 缓存,code
        $this->cache->set($this->getKeyName($mail), $code, $this->expire);
        // 清空尝试次数
        $this->cache->set($this->getKeyName($mail, 'times'), 0, $this->expire);

        return $code;
    }


    /**
     * 缓存key
     *
     * @param string $mail  接收人邮箱
     * @param string $type  cache 缓存名字，times 验证次数的缓存名字
     * @return string
     */
    private function getKeyName($mail, $type = 'cache') : string
    {
        return 'mailcode' . ':' . $this->code_event . ':' . $mail . ':' . $type;
    }

    /**
     * 初始化 mail 实例
     *
     * @return void
     */
    private function init()
    {
        $this->mail = new PHPMailer(true);      // 返回错误信息，抛出异常

        $this->mail->CharSet = "UTF-8";
        $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
        $this->mail->isSMTP();
        $this->mail->Host       = $this->config['smtp_host'];
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $this->config['smtp_user'];
        $this->mail->Password   = $this->config['smtp_pass'];
        $this->mail->SMTPSecure = $this->config['verify_type'];
        $this->mail->Port       = $this->config['smtp_port'];
        $this->mail->isHTML(true);
        $this->setFrom();       // 设置发送人
    }


    /**
     * 批量设置
     *
     * @param [type] $addresses
     * @param [type] $type
     * @return void
     */
    private function batchSet($items, $type)
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->{$type}(...$item);
            } else {
                $this->{$type}($item);
            }
        }
    }
}
