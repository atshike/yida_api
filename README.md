# 钉钉宜搭api

### 安装
```
composer require atshike/wework-api
```
### 配置
```
// 配置文件
'yida' => [
    'app_type'=> env('YIDA_APP_TYPE'),
    'system_token'=> env('YIDA_SYSTEM_TOKEN'),
    'appkey'=> env('YIDA_APPKEY'),
    'appsecret'=> env('YIDA_APPSECRET'),
    'agent_id' => env('YIDA_AGENT_ID'),
],
```
### 示例
```
// 获取附件地址
$dingtalk = new YiDaService();
$rs = $dingtalk->downloadAttUrl("https://{$params['url']}{$params['file']}");
$url = $rs->body->result;
// 打开远程文件    
$client = new Client(['verify' => false]);
$tempData = $client->request('get', $url)->getBody()->getContents();
// 保存到本地
$rs = Storage::disk('files')->put("/time().xlsx", $tempData);
```