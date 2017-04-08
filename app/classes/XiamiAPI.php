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
use Sunra\PhpSimple\HtmlDomParser;

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
            'QRCode' => 'data:image/png;base64,' . base64_encode($res->getBody()),
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
        $jarFile->save(""); //TODO:Specify the cookie location
        return json_encode(array('status' => $globalStatus, 'statusCode' => $statusCode));
    }

    public function getCollectionList($cookie)
    {
        $getCollectionListRequest = new GuzzleHttp\Client();
        $jarFile = new \GuzzleHttp\Cookie\FileCookieJar("", true);//TODO:Specify the cookie location
        $res = $getCollectionListRequest->request('GET', 'http://www.xiami.com/space/collect/u', [
            'cookies' => $jarFile
        ]);
        if ($res->getStatusCode() != 200) {
            //TODO:Exception handle
        }
        $html = HTMLDomParser::str_get_html($res->getBody());
        $collections = array();
        foreach ($html->find('a[href^=/collect/]') as $element) {
            if (!empty($element->title)) {
                $collection = array();
                if (preg_match('/-?[1-9]\d*/', $element->href, $collection)) { //TODO:Exception handle
                    $collections[$collection[0]] = $element->title;
                }
            }
        }
        return json_encode(array(
            'status' => 'true',
            'collectionList' => $collections
        ));
    }

    public function addSongToCollection($cookie, $songId, $collectionId, $tag = '', $comment = '')
    {
        $addSongToCollectionRequest = new GuzzleHttp\Client();
        $jarFile = new \GuzzleHttp\Cookie\FileCookieJar("", true);//TODO:Specify the cookie location
        $xiamiToken = "";
        foreach ($jarFile->toArray() as $value) {
            if ($value['Name'] == '_xiamitoken') {
                $xiamiToken = $value['Value'];
            }
        }
        $globalResult = false;
        $message = '';
        if (empty($tag) && empty($comment)) {
            $res = $addSongToCollectionRequest->request(
                'POST',
                'http://www.xiami.com/collect/ajax-add-song', [
                'cookies' => $jarFile,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
                    'Accept' => '*/*',
                    'X-Requested-With' => ' XMLHttpRequest',
                    'Origin' => 'http://www.xiami.com',
                    'Referer' => 'http://www.xiami.com/collect/' . $collectionId
                ],
                'form_params' => [
                    'list_id' => $collectionId,
                    'song_id' => $songId,
                    '_xiamitoken' => $xiamiToken
                ]
            ]);
            if ($res->getStatusCode() != 200) {
                //TODO:Exception handle
            }
            $result = json_decode($res->getBody(), true);
            $globalResult = $result['state'];
            $message = $result['message'];
        } else {
            $res = $addSongToCollectionRequest->request(
                'POST',
                'http://www.xiami.com/song/collect', [
                'cookies' => $jarFile,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
                    'Accept' => '*/*',
                    'X-Requested-With' => ' XMLHttpRequest',
                    'Origin' => 'http://www.xiami.com',
                    'Referer' => 'http://www.xiami.com/space/lib-song?spm=a1z1s.6843761.226669510.3.DffY3f'
                ],
                'form_params' => [
                    '_xiamitoken' => $xiamiToken,
                    'id' => $songId,
                    'list_id' => $collectionId,
                    'tag_name' => $tag,
                    'description' => $comment,
                    'submit' => '保 存'
                ]
            ]);
            if ($res->getStatusCode() != 200) {
                //TODO:Exception handle
            }
            $globalResult = strpos($res->getBody(), '添加成功') === false ? false : true;
        }
        return json_encode(array(
            'status' => $globalResult ? 'success' : 'failure',
            'message' => $message
        ));


    }
}
