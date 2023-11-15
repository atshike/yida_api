<?php

namespace Atshike\Dingoa\Services;


use Services\YiDaServices;

class DingNoticeService
{
    /**
     * 服务器异常
     *  发送卡片消息
     *   https://open.dingtalk.com/document/isvapp/send-job-notification.
     *
     * @param array $userid_list 接收用户ID [userid1,userid2...]
     * @param string|null $str 消息内容
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendNotify(array $userid_list, string $content): void
    {
        $dingtalk = new YiDaServices();
        try {
            $dingtalk->httpSend(
                'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2',
                [
                    'agent_id' => config('services.yida.agent_id'),
                    'userid_list' => implode(',', $userid_list),
                    'msg' => json_encode([
                        'msgtype' => 'text', // text image{MediaId}  file{MediaId}
                        'text' => [
                            'content' => $content,
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
                'POST'
            );
        } catch (\Exception $err) {
            info($err->getMessage());
        }
    }
}
