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

class OsuFile
{
    private $beatmapSetId = -1;
    private $beatmapId = -1;
    private $apiKey = "";
    private $OsuFileContent = "";
    private $metaData = array();
    /**
     * OsuFileParse constructor.
     * @param int $BSid BeatMapSet id
     * @param int $BMid BeatMap id
     * @param String $apiKey Api Key,can't be empty
     */
    public function __construct($BSid = -1, $BMid = -1, $apiKey)
    {
        $this->beatmapSetIdId = $BSid;
        $this->beatmapId = $BMid;
        $this->apiKey = $apiKey;
    }

    public function downloadOsuFile()
    {
        $tempBeatmapId = "";
        if ($this->beatmapId == -1) {
            $beatmapClient = new \GuzzleHttp\Client();
            $res = $beatmapClient->request(
                'GET',
                'http://osu.ppy.sh/api/get_beatmaps?k=' . $this->apiKey . '&s=' . $this->beatmapSetId
            );
            if ($res->getStatusCode() != 200) {
                //TODO:Exception handle
            }
            $result = json_decode($res->getBody(), true);
            if (isset($result['error'])) {
                //TODO:Exception handle
            }
            $tempBeatmapId = $result[0]['beatmap_id'];
        } else {
            $tempBeatmapId = $this->beatmapId;
        }
        $osuClient = new \GuzzleHttp\Client();
        $res = $osuClient->request(
            'GET',
            'https://osu.ppy.sh/osu/' . $tempBeatmapId
        );
        if ($res->getStatusCode() != 200) {
            //TODO:Exception handle
        }
        $this->OsuFileContent = $res->getBody();
    }

    public function parse()
    {
        if (empty($this->OsuFileContent)) {
            //TODO:Exception handle
        }
        $osuArray = explode("\r\n", $this->OsuFileContent);
        if (strstr($osuArray[0], "osu file format v") === false) {
            //TODO:not an osu file
        }
        $this->metaData['version'] = substr($osuArray[0], stripos($osuArray[0], 'v') + 1);
        $fatherNode = "";
        foreach (array_slice($osuArray, 1) as $value) {
            if (empty($value)) {
                continue;
            }
            if (stripos($value, "[") == 0 && stripos($value, "]") == strlen($value) - 1) {
                $fatherNode = substr($value, 1, strlen($value) - 2);
                continue;
            }
            $param = trim(substr($value, 0, stripos($value, ':')));
            $v = trim(substr($value, stripos($value, ':') + 1));
            $this->metaData[$fatherNode][$param] = $v;
        }
    }
}
