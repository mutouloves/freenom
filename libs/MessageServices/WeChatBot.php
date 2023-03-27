<?php
/**
 * 企业微信机器人
 *
 * @author loong <mutouloves@gmail.com>
 * @date 2023/3/27
 * @time 15:23
 */

namespace Luolongfei\Libs\MessageServices;

use GuzzleHttp\Client;
use Luolongfei\Libs\Connector\MessageGateway;

class WeChatBot extends MessageGateway
{
    const TIMEOUT = 33;

    /**
     * @var string 机器人KEY
     */
    protected $token;

    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        $this->token = config('message.wechatbot.token');

        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'cookies' => false,
            'timeout' => self::TIMEOUT,
            'verify' => config('verify_ssl'),
            'debug' => config('debug'),
        ]);
    }


    /**
     * 生成域名文本
     *
     * @param array $domains
     *
     * @return string
     */
    public function genDomainsText(array $domains)
    {
        $domainsText = '';

        foreach ($domains as $domain) {
            $domainsText .= sprintf('<a href="http://%s">%s</a> ', $domain, $domain);
        }

        $domainsText = trim($domainsText, ' ') . "\n";

        return $domainsText;
    }

    /**
     * 获取页脚
     *
     * @return string
     */
    public function getFooter()
    {
        $footer = '';

        $footer .= lang('100116');

        return $footer;
    }

    /**
     * 生成域名状态文本
     *
     * @param array $domainStatus
     *
     * @return string
     */
    public function genDomainStatusText(array $domainStatus)
    {
        if (empty($domainStatus)) {
            return lang('100118');
        }

        $domainStatusText = '';

        foreach ($domainStatus as $domain => $daysLeft) {
            $domainStatusText .= sprintf(lang('100119'), $domain, $domain, $domain, $daysLeft);
        }

        $domainStatusText = rtrim(rtrim($domainStatusText, ' '), '，,') . lang('100120');

        return $domainStatusText;
    }

    /**
     * 生成域名续期结果文本
     *
     * @param string $username
     * @param array $renewalSuccessArr
     * @param array $renewalFailuresArr
     * @param array $domainStatus
     *
     * @return string
     */
    public function genDomainRenewalResultsText(string $username, array $renewalSuccessArr, array $renewalFailuresArr, array $domainStatus)
    {
        $text = sprintf(lang('100121'), $username);

        if ($renewalSuccessArr) {
            $text .= lang('100122');
            $text .= $this->genDomainsText($renewalSuccessArr);
        }

        if ($renewalFailuresArr) {
            $text .= lang('100123');
            $text .= $this->genDomainsText($renewalFailuresArr);
        }

        $text .= lang('100124');
        $text .= $this->genDomainStatusText($domainStatus);

        $text .= $this->getFooter();

        return $text;
    }

    /**
     * 生成域名状态完整文本
     *
     * @param string $username
     * @param array $domainStatus
     *
     * @return string
     */
    public function genDomainStatusFullText(string $username, array $domainStatus)
    {
        $markDownText = sprintf(lang('100125'), $username);

        $markDownText .= $this->genDomainStatusText($domainStatus);

        $markDownText .= $this->getFooter();

        return $markDownText;
    }

    /**
     * 送信
     *
     * 由于腾讯要求 markdown 语法消息必须使用 企业微信 APP 才能查看，然而我并不想单独安装 企业微信 APP，故本方法不使用 markdown 语法，
     * 而是直接使用纯文本 text 类型，纯文本类型里腾讯额外支持 a 标签，所以基本满足需求
     *
     * 参考：
     * https://work.weixin.qq.com/api/doc/90000/90135/91039
     * https://work.weixin.qq.com/api/doc/90000/90135/90236#%E6%96%87%E6%9C%AC%E6%B6%88%E6%81%AF
     *
     * @param string $content
     * @param string $subject
     * @param int $type
     * @param array $data
     * @param string|null $recipient
     * @param mixed ...$params
     *
     * @return bool
     * @throws \Exception
     */
    public function send(string $content, string $subject = '', int $type = 1, array $data = [], ?string $recipient = null, ...$params)
    {
        $this->check($content, $data);

        $commonFooter = '';

        if ($type === 1 || $type === 4) {
            $this->setCommonFooter($commonFooter, "\n", false);
        } else if ($type === 2) {
            $this->setCommonFooter($commonFooter, "\n", false);
            $content = $this->genDomainRenewalResultsText($data['username'], $data['renewalSuccessArr'], $data['renewalFailuresArr'], $data['domainStatusArr']);
        } else if ($type === 3) {
            $this->setCommonFooter($commonFooter);
            $content = $this->genDomainStatusFullText($data['username'], $data['domainStatusArr']);
        } else {
            throw new \Exception(lang('100003'));
        }

        $content .= $commonFooter;

        if ($subject !== '') {
            $content = $subject . "\n\n" . $content;
        }

        try {
            $accessToken = $this->token;

            $body = [
                'msgtype' => 'text', // 消息类型，text 类型支持 a 标签以及 \n 换行，基本满足需求。由于腾讯要求 markdown 语法必须使用 企业微信APP 才能查看，不想安装，故弃之
                'text' => [
                    'content' => $content, // 消息内容，最长不超过 2048 个字节，超过将截断
                ],
                'enable_duplicate_check' => 1,
                'duplicate_check_interval' => 60,
            ];

            return $this->doSend($accessToken, $body);
        } catch (\Exception $e) {
            system_log(sprintf(lang('100126'), $e->getMessage()));

            return false;
        }
    }

    /**
     * 执行送信
     *
     * @param string $accessToken
     * @param array $body
     * @param int $numOfRetries
     *
     * @return bool
     * @throws \Exception
     */
    private function doSend(string $accessToken, array $body, int &$numOfRetries = 0)
    {
        $resp = $this->client->post(
        sprintf('https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=%s', $this->token),
        [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
        ]);

        $resp = (string)$resp->getBody();
        $resp = (array)json_decode($resp, true);

        if (!isset($resp['errcode']) || !isset($resp['errmsg'])) {
            throw new \Exception(lang('100127') . json_encode($resp, JSON_UNESCAPED_UNICODE));
        }

        if ($resp['errcode'] === 0) {
            return true;
        } else if ($resp['errcode'] === 40014) { // invalid access_token
            $accessToken = $this->token;

            if ($numOfRetries > 2) {
                throw new \Exception(lang('100128') . $resp['errmsg']);
            }

            $numOfRetries++;

            return $this->doSend($accessToken, $body, $numOfRetries);
        }

        throw new \Exception($resp['errmsg']);
    }
}
