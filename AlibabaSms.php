<?php
/**
 * Copyright (c) 2020
 *  @file  AlibabaSms.php
 *  @author  USPilot
 *  @date  2020/10/14
 *  @time  16:04
 *
 */
namespace USPilot;

use \AlibabaCloud\Client\AlibabaCloud;
use \AlibabaCloud\Client\Exception\ClientException;
use \AlibabaCloud\Client\Exception\ServerException;
use Exception;
use \IniParser\IniParser;
use \RosterInfo\proxy;

class AlibabaSms
{
    public $PhoneNumbers;
    public $SignNames = [];
    public $Templates = [];
    public $TemplateParam = [];
    public $OutId;
    public $SmsUpExtendCode;
    public $RegionId;
    public $result = [];
    private $useProxy = false;
    private $proxy = null;
    private $ActiveTemplate = '';
    private $ActiveSignName = '';

    /**
     * AlibabaSms constructor.
     * @param string $iniFile
     * @param bool $useProxy
     * @throws Exception
     */
    public function __construct(string $iniFile = 'ini/sms.php', bool $useProxy = false)
    {
        $this->Init($iniFile, $useProxy);
    }

    /**
     * @param string $iniFile
     * @param bool $useProxy
     * @return AlibabaSms
     * @throws Exception
     */
    public function Init(string $iniFile = 'ini/sms.php', bool $useProxy = false)
    {
        $this->PhoneNumbers = '';
        $this->ActiveSignName = '';
        $this->ActiveTemplate = '';
        $this->OutId = '';
        $this->result = [];
        $this->SmsUpExtendCode = [];
        $this->TemplateParam = [];

        if (!file_exists($iniFile)) throw new Exception('INI file not found',9001);
        $iniParser = new IniParser($iniFile);
        $ini = $iniParser->parse();
        if (empty($ini['SignName'])) throw new Exception('Alibaba `SignName` is missing in INI file',9002);
        if (empty($ini['Template'])) throw new Exception('Alibaba `Template` is missing in INI file',9002);
        if (empty($ini['AccessKeyId'])) throw new Exception('Alibaba `AccessKeyId` is missing in INI file',9002);
        if (empty($ini['AccessKeySecret'])) throw new Exception('Alibaba `AccessKeySecret` is missing in INI file',9002);
        if (empty($ini['RegionId'])) $ini['RegionId'] = 'cn-hangzhou';

        $this->SignNames = $ini['SignName'];
        $this->Templates = $ini['Template'];
        $this->setUseProxy($useProxy);

        AlibabaCloud::accessKeyClient($ini['AccessKeyId'], $ini['AccessKeySecret'])
            ->regionId($ini['RegionId'])
            ->asDefaultClient();
        if ($this->useProxy) AlibabaCloud::getDefaultClient()->proxy($this->proxy);
        return $this;
    }

    /**
     * @return array
     * @throws ClientException
     * @throws ServerException
     */
    public function sendSms()
    {
        $res = AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->version('2017-05-25')
            ->action('SendSms')
            ->method('POST')
            ->options([
                'query' => [
                    'RegionId' => $this->RegionId,
                    'PhoneNumbers' => $this->PhoneNumbers,
                    'SignName' => $this->ActiveSignName,
                    'TemplateCode' => $this->ActiveTemplate,
                    'TemplateParam' => empty($this->TemplateParam)? '' : json_encode($this->TemplateParam),
                    'SmsUpExtendCode' => empty($this->SmsUpExtendCode)? '' : $this->SmsUpExtendCode,
                    'OutId' => empty($this->OutId)? '' : $this->OutId,
                ],
            ])
            ->request();
        $this->result = $res->toArray();
        return $this->result;
    }

    /**
     * @param bool $useProxy
     * @param string $type
     * @return AlibabaSms
     */
    public function setUseProxy($useProxy = true, $type = 'http')
    {
        if ($useProxy) {
            proxy::checkProxy(false);
            if ($type != 'socks5') {
                $this->proxy = empty(proxy::$host)? null : proxy::$user.'@'.proxy::$host;
            }
            else {
                seld::$proxy = empty(proxy::$socks5Host)? null : 'socks5://'.proxy::$socks5User.'@'.proxy::$socks5Host;
            }
        } else {
            $this->proxy = null;
        }
        $this->useProxy = $this->proxy? true : false;
        return $this;
    }

    /**
     * @param string $ActiveSignName
     * @return AlibabaSms
     */
    public function setSignName(string $ActiveSignName)
    {
        $this->ActiveSignName = empty($this->SignNames[$ActiveSignName])? '' : $this->SignNames[$ActiveSignName];
        return $this;
    }

    /**
     * @param string $ActiveTemplate
     * @return AlibabaSms
     */
    public function setTemplate(string $ActiveTemplate)
    {
        $this->ActiveTemplate = empty($this->Templates[$ActiveTemplate])? '' : $this->Templates[$ActiveTemplate];
        return $this;
    }

    /**
     * @param array $TemplateParam
     * @return AlibabaSms
     */
    public function setTemplateParam(array $TemplateParam)
    {
        $this->TemplateParam = $TemplateParam;
        return $this;
    }

    /**
     * @param mixed $OutId
     * @return AlibabaSms
     */
    public function setOutId($OutId)
    {
        $this->OutId = $OutId;
        return $this;
    }

    /**
     * @param mixed $SmsUpExtendCode
     * @return AlibabaSms
     */
    public function setSmsUpExtendCode($SmsUpExtendCode)
    {
        $this->SmsUpExtendCode = $SmsUpExtendCode;
        return $this;
    }

    /**
     * @param mixed $PhoneNumbers
     * @return AlibabaSms
     */
    public function setPhoneNumbers($PhoneNumbers)
    {
        $this->PhoneNumbers = $PhoneNumbers;
        return $this;
    }

}