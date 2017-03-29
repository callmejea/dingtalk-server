<?php
namespace DingTalk;

/**
 * 所有的钉钉返回的会包含:  errcode:0 正常, 其他状态不正常; errmsg: 错误信息
 * User: jea
 * Date: 2017/3/25
 * Time: 15:57
 */

use Httpful\Http;
use Httpful\Mime;
use Httpful\Request;

class DingTalk
{

    private $token         = null;
    private $agentId       = null; //测试应用的APPId
    private $corpId        = null; //钉钉给的APPid
    private $corpSecret    = null; //钉钉给的appSecret
    private $OApiHost      = 'https://oapi.dingtalk.com'; //钉钉OApi的url
    private $hasError      = null;
    private $errorMsg      = null;
    private $tokenDeadLine = 7000; //token过期时间

    /**
     * 设置钉钉获取API的配置
     *
     * @param string $corpId     钉钉给的APPid
     * @param string $corpSecret 钉钉给的appSecret
     * @param string $agentId    微应用的id,如果没有可以留空
     *
     * @return $this
     */
    public function __construct($corpId, $corpSecret, $agentId = '')
    {
        $this->corpId     = $corpId;
        $this->corpSecret = $corpSecret;
        $this->agentId    = $agentId;
        //初始化时候直接获取token
        $this->getToken();

        return $this;
    }

    /**
     * 上传文件
     *
     * @param string $filePath
     * @param string $fileType 文件类型, 支持的类型有: 媒体文件类型，分别有图片（image）、语音（voice）、普通文件(file)
     *
     * @return mixed
     */
    public function uploadFile($filePath, $fileType)
    {
        $allowTypes = array('image', 'voice', 'file');
        if (!in_array($fileType, $allowTypes)) {
            return '错误的文件类型,必须是: 图片（image）、语音（voice）、普通文件(file)';
        }
        $uri  = $this->OApiHost . '/media/upload?access_token=' . $this->token . '&type=' . $fileType;
        $http = Request::init(Http::POST, Mime::UPLOAD);
        $res  = $http->attach(array('media' => $filePath))
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * 创建微应用
     *
     * @param string $icon          图标, 通过upload接口上传之后得到的
     * @param string $appName       应用名
     * @param string $appDesc       应用介绍
     * @param string $homepageUrl   应用移动端主页
     * @param string $pcHomepageUrl 应用pc端主页
     * @param string $ompLink       应用oa管理后台主页, 以http||https开头
     *
     * @return mixed
     */
    public function createMicroApp($icon, $appName, $appDesc, $homepageUrl, $pcHomepageUrl = '', $ompLink = '')
    {
        $uri  = $this->OApiHost . '/microapp/create?access_token=' . $this->token;
        $data = array(
            "appIcon"       => $icon,
            "appName"       => $appName,
            "appDesc"       => $appDesc,
            "homepageUrl"   => $homepageUrl,
            "pcHomepageUrl" => $pcHomepageUrl,
            "ompLink"       => $pcHomepageUrl,
        );
        $http = Request::init(Http::POST, Mime::JSON);
        $res  = $http->body(json_encode($data))
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * 给钉钉用户发送消息
     *
     * @param integer $userId     钉钉用户id
     * @param string  $msgContent 消息的详情
     *
     * @return mixed
     */
    public function sendTextMessage($toUserId, $msgContent)
    {
        $uri  = $this->OApiHost . '/message/send?access_token=' . $this->token;
        $data = array(
            'touser'  => $toUserId,
            'agentid' => $this->agentId,
            'msgtype' => 'text',
            'text'    => array(
                'content' => $msgContent,
            ),
        );
        $http = Request::init(Http::POST, Mime::JSON);
        $res  = $http->body(json_encode($data))
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * 获取钉钉的部门列表
     *
     * @return mixed
     */
    public function getDepartmentList()
    {
        $uri = $this->OApiHost . '/department/list?access_token=' . $this->token;
        $res = Request::init(Http::GET)
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * 根据部门id获取成员信息,这里获取的详细信息
     *
     * @param int $id 1是取得所有用户
     *
     * @return mixed
     */
    public function getDepartmentMemberById($id = 1)
    {
        $uri = $this->OApiHost . '/user/list?access_token=' . $this->token . '&department_id=' . $id;
        $res = Request::init(Http::GET)
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * 根据钉钉的用户id来获取详细的用户信息
     *
     * @param string $userId 用户id
     *
     * @return mixed
     */
    public function getUserContent($userId)
    {
        $uri = $this->OApiHost . '/user/get?access_token=' . $this->token . '&userid=' . $userId;
        $res = Request::init(Http::GET)
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * 创建用户
     * 允许的字段:
     * https://open-doc.dingtalk.com/docs/doc.htm?spm=a219a.7629140.0.0.2Mu0u6&treeId=172&articleId=104979&docType=1#s7
     * @return mixed
     */
    public function addUser($userData)
    {
        $uri  = $this->OApiHost . '/user/create?access_token=' . $this->token;
        $http = Request::init(Http::POST, Mime::JSON);
        $res  = $http->body(json_encode($userData))
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * @param array $ids 被删除用户的id like array(id1,id2,.....,idn)
     *
     * @return mixed
     */
    public function deleteUserByIds($ids)
    {
        $uri  = $this->OApiHost . '/user/batchdelete?access_token=' . $this->token;
        $data = array(
            'useridlist' => $ids,
        );
        $http = Request::init(Http::POST, Mime::JSON);
        $res  = $http->body(json_encode($data))
            ->uri($uri)
            ->send();

        return $this->out($res->raw_body);
    }

    /**
     * 更新用户信息
     */
    public function updateUser()
    {
        $uri  = $this->OApiHost . '/user/update?access_token=' . $this->token;
        $data = array(
            'userid' => '055727136036622819',
            'email'  => 'jeajin@zhen22.com',
        );
        $http = Request::init(Http::POST, Mime::JSON);
        $res  = $http->body(json_encode($data))
            ->uri($uri)
            ->send();
        print_r($res->raw_body);
    }

    /**
     * 统一返回数据处理 钉钉返回的数据一定是json格式, 并且有 errmsg 和errcode,
     *
     * @param string $json 钉钉返回的数据, 一定是json格式的
     */
    public function out($json)
    {
        return json_decode($json, true);
    }

    /**
     * 获取token, 默认从缓存的文件, token.php中获取, 如果定义时间> 配置的时间, 就重新获取
     * @return array|mixed
     */
    private function getToken()
    {
        if ($this->corpId === null || $this->corpSecret === null) {
            $this->hasError = true;
            $this->errorMsg = 'must set corpId and corpSecret';
        }
        //token缓存的文件
        $cacheFile = dirname(__FILE__) . '/token.php';
        $cache     = file_get_contents($cacheFile);

        if (!empty($cache)) {
            $config = unserialize($cache);
            if (isset($config['access_token']) && isset($config['time'])) {
                //过期时间少于7200秒
                if ((time() - $config['time']) < $this->tokenDeadLine) {
                    $this->token = $config['access_token'];

                    return null;
                }
            }
        } else {
            $token = $this->getTokenFromRemote();
            //获取到了token
            if ($this->hasError === null) {
                $data = array('time' => time(), 'access_token' => $token);
                file_put_contents($cacheFile, serialize($data));
            }
            $this->token = $token;

            return null;
        }
        $this->hasError = false;
        $this->errorMsg = 'unknown error at get access_token';

        return null;
    }

    /**
     * 去远程获取新的 access_token
     * @return mixed
     */
    private function getTokenFromRemote()
    {
        $uri = $this->OApiHost . '/gettoken?corpid=' . $this->corpId . '&corpsecret=' . $this->corpSecret;
        $res = Request::init(Http::GET)
            ->uri($uri)
            ->send();
        $response = json_decode($res->raw_body, true);
        if ($response['errmsg'] !== 'ok') {
            $this->hasError = true;
            $this->errorMsg = 'error return from ding server: ' . $res->raw_body;

            return '';
        }

        return $response['access_token'];
    }
}
