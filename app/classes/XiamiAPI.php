<?php

/*
  _____                    ____
/\  __`\                 /\  _`\
\ \ \/\ \    ____  __  __\ \,\L\_\  __  __    ___     ___
 \ \ \ \ \  /',__\/\ \/\ \\/_\__ \ /\ \/\ \ /' _ `\  /'___\
  \ \ \_\ \/\__, `\ \ \_\ \ /\ \L\ \ \ \_\ \/\ \/\ \/\ \__/
   \ \_____\/\____/\ \____/ \ `\____\/`____ \ \_\ \_\ \____\
    \/_____/\/___/  \/___/   \/_____/`/___/> \/_/\/_/\/____/
                                        /\___/
                                        \/__/

 * */

require '../../vendor/autoload.php';

class XiamiAPI
{
    /**
     * Initial Login(Receive Login QRCode)
     * This function will request a login QRCode and save cookie file for further login process
     *
     * @param string $cookie
     * @return string Returns the status & data url of the image & lgToken
     */
    public function initLogin($cookie){
        $initialRequest = new GuzzleHttp\Client();
        $jarFile = new \GuzzleHttp\Cookie\FileCookieJar("",true); //TODO:Specify the cookie location

        $res = $initialRequest->request('GET', 'http://www.xiami.com', [
            'cookies' => $jarFile
        ]);
        //Init the cookie file

        if($res->getStatusCode()!=200){
            //TODO:Exception handle
        }

        $QRCodeRequest=new GuzzleHttp\Client();
        $res= $QRCodeRequest->request('GET','https://login.xiami.com/member/generate-qrcodelogin?from=xiami&size=150&t='.microtime(),[
            'cookies' => $jarFile
        ]);
        //Request the qrCode

        if($res->getStatusCode()!=200){
            //TODO:Exception handle
        }

        $replyData=json_decode($res->getBody(),true);
        if(!isset($replyData['data'])){
            //TODO:Exception handle
        }
        $pngURI=$replyData['data']['url'];
        $jarFile->save(""); //TODO:Specify the cookie location

        $QRCodeDownloader=new GuzzleHttp\Client();
        $res= $QRCodeDownloader->request('GET','https:'.$pngURI,[
            'cookies' => $jarFile
        ]);

        if($res->getStatusCode()!=200){
            //TODO:Exception handle
        }

        return json_encode(array(
            'status'=>'true',
            'QRCode'=>'data:image/gif;base64,'.base64_encode($res->getBody()),
            'lgToken'=>$replyData['data']['lgToken']
        ));
    }

    /**
     * @param $cookie
     * @param $lgToken
     * @return string
     */
    public function checkStatus($cookie, $lgToken){
        $checkRequest = new GuzzleHttp\Client();
        $jarFile = new \GuzzleHttp\Cookie\FileCookieJar("",true);//TODO:Specify the cookie location
        $res= $checkRequest->request('GET','https://login.xiami.com/member/qrcodelogin?lgToken='.$lgToken."&defaulturl=http%3A%2F%2Fwww.xiami.com%2F&t=".microtime(),[
            'cookies' => $jarFile
        ]);
        if($res->getStatusCode()!=200){
            //TODO:Exception handle
        }
        $statusCode=json_decode($res->getBody(),true)['data']['code'];
        $globalStatus=0;
        switch($statusCode){
            case 10000:
                //Haven't scanned QRCode
                $globalStatus=0;
                break;
            case 10001:
                //QRCode Scanned
                $globalStatus=0;
                break;
            case 10004:
                //QRCode expired
                $globalStatus=-1;
                //TODO:Re-Request QRCode for scanning
                break;
            case 10006:
                //Logged in
                $globalStatus=1;
                break;
            default:
                //TODO:Other status code?
                break;
        }
        return json_encode(array('status'=>$globalStatus,$statusCode));
    }
}