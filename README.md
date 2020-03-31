# autograph
接E签宝封装

   use Sign\Esign;
   
   class Talk{

    private $sign;

    function __construct(){

        $config['config']=[
            'appId'=>'',//E签宝的appID 
            'secret'=>'',//E签宝的secret 
        ];

        $config['url']  = 'https://smlopenapi.esign.cn';//接口请求地址
        $config['back_url']  = '';//完成回调地址
        $config['redis_url']  = 'tcp://127.0.0.1:6379';//redis连接地址
        $this->init($config);
    }

    private function init($config){

       $this->sign = new Esign($config);

    }
    
    //功能开始....按照E签宝的接口交互时序图流程图调用接口
    public function account($info){

        $accountResult = $this->sign->createAccounts($info);
        return $accountResult;
    }


}
