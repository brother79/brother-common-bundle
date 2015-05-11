<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 08.05.2015
 * Time: 13:43
 */

namespace Brother\CommonBundle\Model;


use Brother\CommonBundle\AppTools;

class YoutubeApi {

    const DATA_SNIPPET = 'snippet';

    public static function getVideoData($key, $id, $params)
    {
        $url = "https://www.googleapis.com/youtube/v3/videos?id=" . $id . "&key=" . $key . "&fields=items(id,snippet(channelId,title,categoryId),statistics)&part=snippet,statistics";
        return json_decode(AppTools::readUrlHttps($url));
    }
} 