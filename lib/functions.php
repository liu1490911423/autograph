<?php

    function doPost($url, $data, $appID, $stoken)
    {
        list($return_code, $return_content) = $this->http_post_data($url, $data, $appID,$stoken);
        return $return_content;
    }

    /**
     * @param $url
     * @param $data
     * @param $config
     * @param $stoken
     * @return array
     */
    function http_post_data($url, $data, $appID, $stoken) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 跳过检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 跳过检查
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Tsign-Open-App-Id:".$appID, "X-Tsign-Open-Token:".$stoken, "Content-Type:application/json" ));
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return array($return_code, $return_content);
    }

    //get方法

    /**
     * @param $url
     * @param $appID
     * @param $stoken
     * @return mixed
     */
    function doGet($url, $appID='', $stoken='')
    {
        list($return_code, $return_content) = $this->curl_get_https($url,$appID, $stoken);
        return $return_content;
    }

    /**
     * @param $url
     * @param string $appID
     * @param string $stoken
     * @return array
     */
    function curl_get_https($url,$appID='', $stoken='') {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 跳过检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 跳过检查

        if($stoken){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Tsign-Open-App-Id:".$appID, "X-Tsign-Open-Token:".$stoken, "Content-Type:application/json" ));
        }
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($return_code, $return_content);
    }

    function sendHttpPUT($uploadUrls, $contentMd5, $fileContent){
        $header = array(
            'Content-Type:application/pdf',
            'Content-Md5:' . $contentMd5
        );

        $status = '';
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $uploadUrls);
        curl_setopt($curl_handle, CURLOPT_FILETIME, true);
        curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, false);
        curl_setopt($curl_handle, CURLOPT_HEADER, true); // 输出HTTP头 true
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5184000);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');

        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fileContent);
        $result = curl_exec($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

        if ($result === false) {
            $status = curl_errno($curl_handle);
            $result = 'put file to oss - curl error :' . curl_error($curl_handle);
        }
        curl_close($curl_handle);
//    $this->debug($url, $fileContent, $header, $result);
        return $status;
    }


    /**
     * @param $url
     * @param $data
     * @param $appID
     * @param $stoken
     * @return mixed
     */
    function doPut($url, $appID, $stoken)
    {
        list($return_code, $return_content) = $this->http_put_data($url,$appID,$stoken);
        return $return_content;
    }

    /**
     * @param $url
     * @param $data
     * @param $appID
     * @param $stoken
     * @return array
     */


    function http_put_data($url,$appID,$stoken){

        $request_headers = array(
            "X-HTTP-Method-Override: put",
            "X-Tsign-Open-App-Id:".$appID,
            "X-Tsign-Open-Token:".$stoken,
            "Content-Type:application/json" );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//https
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);

        $return_content = curl_exec($ch);
        $return_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);


        curl_close($ch);

        return array($return_code, $return_content);

    }

    function curlDownFile($file_url, $save_path = '', $file_name = '') {
        // 没有远程url或已下载文件，返回false
        if (trim($file_url) == '' || file_exists( $save_path.$file_name )) {
            return false;
        }

        // 若没指定目录，则默认当前目录
        if (trim($save_path) == '') {
            $save_path = './';
        }

        // 若指定的目录没有，则创建
        if (!file_exists($save_path) && !mkdir($save_path, 0777, true)) {
            return false;
        }

        // 若没指定文件名，则自动命名
        if (trim($file_name) == '') {
            $file_ext = strrchr($file_url, '.');
            $file_exts = array('.gif', '.jpg', '.png','mp3');
            if (!in_array($file_ext, $file_exts)) {
                return false;
            }
            $file_name = time() . $file_ext;
        }

        // curl下载文件
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file = curl_exec($ch);
        curl_close($ch);

        // var_dump($file);die();

        // 保存文件到指定路径
        file_put_contents($save_path.$file_name, $file);

        // 释放文件内存
        unset($file);

        // 执行成功，返回true
        return $save_path.$file_name;
    }


