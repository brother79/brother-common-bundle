<?php
use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\AppTools;

/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 15.01.2015
 * Time: 14:42
 */
class CommentApi
{

    const URL_CREATE = 'http://c1api.hypercomments.com/1.0/comments/create';

    /**
     * @param $widgetId int    ID виджета для которого выполняется запрос обязательный
     * @param $link string    URL страницы, комментарии которой необходимо получить
     * @param $title string    Название страницы с комментарием
     * @param $text string    Текст комментария
     * @param $auth string Строка авторизации пользователя, который отправляет комментарий Правило формирования строки авторизации.
     * @param $parentId string ID родительского комментария
     * @param null $xid string Идентификатор страницы, к которому привязаны комментарии
     */
    public static function create($secret, $widgetId, $link, $title, $text, $auth = null, $parentId = null, $xid = null)
    {
        $body = array(
            'widget_id' => $widgetId,
            'link' => $link,
            'title' => $title,
            'text' => $text,
            'auth' => $auth,
            'parent_id' => $parentId,
            'xid' => $xid
        );
        $signature = sha1(json_encode($body) . $secret);
        AppDebug::_dx($body);
        $r = AppTools::readUrl(self::URL_CREATE, 'get', array('body' => json_encode($body), 'signature' => $signature));
        AppDebug::_dx($r);
    }

} 