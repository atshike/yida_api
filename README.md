# 钉钉宜搭api

### 安装
```
composer require atshike/yida_api
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
// 批量获取列表
use Atshike\YidaApi\Services\YiDaServices;

$yd = new YiDaServices();
$data = getData($yd,'FORM-******', 1, 'user_id', [ 'feilds' => 'RUNNING']);

function getData($yd, $formUuid, $next, $user_id, $params, $data = [])
{
    $rs = $yd->getFormList($formUuid, $user_id, $params, $next, 10);
    $list = $rs->body?->data;
    $currentPage = $rs->body?->currentPage; // 当前页
    $totalCount = $rs->body?->totalCount; // 实例总数
    $data = array_merge($data, (array) $list);
    if ($currentPage < ceil($totalCount / 100)) {
        echo "下一页：{$currentPage}/$totalCount \n";
        $data = getData($yd, $formUuid, bcadd($next, 1), $user_id, $params, $data);
    }

    return $data;
}
```
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