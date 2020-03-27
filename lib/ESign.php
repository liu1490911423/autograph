<?php
/**
 * Created by PhpStorm.
 * User: sage
 * Date: 2018/9/26
 * Time: 14:59
 */

namespace Sign;


/**
 * 调用接口
 * Class Login
 * @package Common\Logic
 */


class Esign
{
    private $config;

    public $url ;
    public $back_url ;

    public $Link_URL=[
        1=>'/v1/oauth2/access_token?',//token获取
        2=>'/v1/accounts/createByThirdPartyUserId',//个人账户创建
        3=>'/v1/organizations/createByThirdPartyUserId',//企业账户创建
        4=>'/v1/files/getUploadUrl',//上传方式创建文件
        5=>'/v1/signflows',//创建签署流程
        6=>'/v1/signflows/{flowId}/documents',//添加流程文件’；
        7=>'/v1/signflows/{flowId}/signfields/platformSign',//添加平台自动盖章签署区
        8=>'/v1/signflows/{flowId}/signfields/handSign',//添加手动盖章签署区
        9=>'/v1/signflows/{flowId}/start',//签署流程开启
        10=>'/v1/signflows/{flowId}/archive',//签署流程归档
        11=>'/v1/signflows/{flowId}/documents',//流程文档下载
        12=>'/v1/signflows/{flowId}/executeUrl'//获取签署地址
    ];

    function __construct($config) {

        $this->config= $config['config'];
        $this->url  = $config['url'];
        $this->back_url  = $config['back_url'];
    }

    /**
     * post请求参数处理
     * @param $data
     * @return string
     */

    function fixData($data){
        $o = "";
        foreach ( $data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);
        return $post_data;
    }

    /**
     * 返回数据处理
     * @param $res
     * @return array|mixed
     */
    function fixReturn($res){

        $res = (array)json_decode($res);

        if($res['code'] == 0){
            $data = (array)$res['data'];
            return $data;

        }else{
            $log = "./data/logs/error_eSign".date('Y-m-d').".txt";// . time() . '.json';
            $newL =date('Y-m-d H:i:s ',time());
            file_put_contents($log,$newL.'-'.time().':'.'错误数据:'.json_encode($res).PHP_EOL,FILE_APPEND);
            return $res;
        }
    }


    /**
     * @return mixed
     * 获取token
     */
    function getToken(){

        //$tokenCache = new SignTokenCache();
        //$token = $tokenCache->get(1);
        //if((!$token)) {
            $this->config['grantType'] = 'client_credentials';
            $data = $this->fixData($this->config);
            $return_content = doGet($this->url.$this->Link_URL[1].$data);
            $result = $this->fixReturn($return_content);
            $token = $result['token'];
            //echo $token;
        //}
        return $token;

    }

    /**
     * @param $info
     * @param $num
     * @return array|mixed
     * @param string $method
     * 创建账户
     */
    function commonRequest($info,$num,$method='post'){

        $stoken = $this->getToken();
        $data = json_encode($info);

        $url = $this->url.$this->Link_URL[$num];
        if($info['flowId']){
            $url = $this->editUrl($url,$info['flowId']);
        }

        if($method == 'put'){
            $return_content = doPut($url,$this->config['appId'],$stoken);
        }else if($method == 'get'){
            if($info['accountId']){
                $url = $url.'?accountId='.$info['accountId'];
            }
            $return_content = doGet($url,$this->config['appId'],$stoken);
        }else{
            $return_content = doPost($url,$data,$this->config['appId'],$stoken);
        }
        $result = $this->fixReturn($return_content);
        return $result;

    }

    /**
     * @param $info
     * @return array|mixed
     * 个人创建账户
     * 返回个人ID：accountId
     */
    function createAccounts($info){
        return $this->commonRequest($info,2);
    }

    /**
     * @param $info
     * @return array|mixed
     * 创建企业用户
     * 返回企业ID：orgId
     */
    function createOrganizations($info){
        return $this->commonRequest($info,3);
    }

    /**
     * @param $filePath
     * @return string
     * 获取contentMd5
     */
    function getContentBase64Md5($filePath){
        //获取文件MD5的128位二进制数组
        $md5file = md5_file($filePath,true);
        //计算文件的Content-MD5
        $contentBase64Md5 = base64_encode($md5file);
        //echo ("contentBase64Md5=".$contentBase64Md5);
        return $contentBase64Md5;
    }

    /**
     * @param $filePath
     * @param $fileName
     * @param $fileSize
     * @param $account
     * @return array|mixed
     * 上传合同
     * 返回文件ID:fileId 和上传文件地址:uploadUrl
     */
    function getUploadUrl($filePath,$fileName,$fileSize,$account){

        $info['contentMd5'] = $this->getContentBase64Md5($filePath);
        $info['contentType'] = 'application/pdf';
        $info['convert2Pdf'] = false;
        $info['fileName'] = $fileName;
        $info['fileSize'] = $fileSize;
        $info['accountId'] = $account;

        return $this->commonRequest($info,4);

    }

    /**
     * @param $uploadUrls
     * @param $filePath
     * @return int|mixed|string
     * 上传文件
     */
    public function  upLoadFile($uploadUrls,$filePath){
        //文件内容
        $fileContent = file_get_contents($filePath);
        $contentMd5 = $this->getContentBase64Md5($filePath);
        $status = sendHttpPUT($uploadUrls, $contentMd5, $fileContent);
        return $status;
    }

    /**
     * @param $businessScene
     * @return array|mixed
     * 创建签署流程返回流程ID:flowId
     */
    function signFlows($businessScene){
        $info = array(
            'businessScene'=>$businessScene?$businessScene:'test',
            'autoArchive'=>true,
            'configInfo'=>array(
                'noticeDeveloperUrl'=>$this->back_url
            )
        );

        return $this->commonRequest($info,5);
    }

    /**
     * @param $url
     * @param $flowId
     * @return mixed
     * 处理请求地址
     */
    function editUrl($url,$flowId){
         if(strstr($url,'{flowId}')){
             $url = str_replace('{flowId}',$flowId,$url);
         }

         return $url;
    }

    /**
     * @param $flowId
     * @param $fileId
     * @return array|mixed
     * 流程文档添加
     */
    function addDocuments($flowId,$fileId){
        $info=array(
            'flowId'=>$flowId,
            'docs'=>array(array('fileId'=>$fileId)));
        return $this->commonRequest($info,6);
    }

    /**
     * @param $flowId
     * @param $fileId
     * @param $sealId
     * @param $pos
     * @return array|mixed
     * 平台签
     * 返回用户ID：accountId，文档ID：fileId
     */

    function platformSign($flowId,$fileId,$sealId,$pos){
        $info = array(
            'flowId'=>$flowId,
            'signfields'=>array(array(
                'fileId'=>$fileId,
                'order'=>1,
                'sealId'=>$sealId,
                'signType'=>1,
                'posBean'=>array(
                    'posPage'=>$pos['page'],
                    'posX'=>$pos['x'],
                    'posY'=>$pos['y'],))));
        return $this->commonRequest($info,7);
    }

    /**
     * @param $accountId
     * @param int $actorIndentityType
     * @param $flowId
     * @param $fileId
     * @param $pos
     * @return array|mixed
     * 手动签
     * 返回用户ID：accountId，文档ID：fileId
     */
    function handSign($accountId,$flowId,$fileId,$pos){
        $info = array(
            'flowId'=>$flowId,
            'signfields'=>array(array(
                'fileId'=>$fileId,
                'signerAccountId'=>$accountId,
                'order'=>'1',
                'signType'=>'1',
                'sealType'=>'0',
                'posBean'=>array(
                    'posPage'=>$pos['page'],
                    'posX'=>$pos['x'],
                    'posY'=>$pos['y'],))));
        return $this->commonRequest($info,8);
    }

    /**
     * @param $flowId
     * @return array|mixedsignFlowStart
     * 开启签署流程
     */
    function signFlowStart($flowId){
        $info['flowId'] = $flowId;
        return $this->commonRequest($info,9,'put');
    }

    /**
     * @param $flowId
     * @param $accountId
     * @return array|mixed
     * 获取签署人签署地址
     */
    function  executeUrl($flowId,$accountId){
        $info['flowId'] = $flowId;
        $info['accountId'] = $accountId;
        return $this->commonRequest($info,12,'get');
    }

    /**
     * @param $flowId
     * @return array|mixed
     * 手动签署流程归档
     */
    function signFlowArchive($flowId){
        $info['flowId'] = $flowId;
        return $this->commonRequest($info,10,'put');
    }

    /**
     * @param $flowId
     * @return mixed
     * 下载流程文档
     * 返回 文档Id:fileId，文档名称：fileName，文档地址：fileUrl
     */
    function signLoad($flowId){
        $info['flowId'] = $flowId;
        return $this->commonRequest($info,11,'get');

    }



}
