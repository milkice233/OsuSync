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


class OsuFileParse
{
    private $beatmapSetId = -1;
    private $beatmapId = -1;
    private $apiKey = "";
    private $OsuFileContent = "";

    /**
     * OsuFileParse constructor.
     * @param int $BSid BeatMapSet id,can't be empty
     * @param int $BMid BeatMap id
     * @param String $apiKey Api Key,can't be empty
     */
    public function __construct($BSid, $BMid = -1, $apiKey)
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
                'https://osu.ppy.sh/api/get_beatmaps?k=' . $this->apiKey . '&s=' . $this->beatmapSetId
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
}