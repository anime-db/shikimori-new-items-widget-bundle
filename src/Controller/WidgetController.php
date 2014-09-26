<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * New items widget
 *
 * @package AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class WidgetController extends Controller
{
    /**
     * Size of list items
     *
     * @var integer
     */
    const LIST_LIMIT = 6;

    /**
     * API path for get new items
     *
     * @var string
     */
    const PATH_NEW_ITEMS = '/animes?limit=#LIMIT#';

    /**
     * Cache lifetime 1 day
     *
     * @var integer
     */
    const CACHE_LIFETIME = 86400;

    /**
     * New items
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $response = $this->get('cache_time_keeper')->getResponse([], self::CACHE_LIFETIME);
        /* @var $widget \AnimeDb\Bundle\ShikimoriWidgetBundle\Service\Widget */
        $widget = $this->get('anime_db.shikimori.widget');

        $list = $this->get('anime_db.shikimori.browser')
            ->get(str_replace('#LIMIT#', self::LIST_LIMIT, self::PATH_NEW_ITEMS));
        $list = array_slice($list, 0, self::LIST_LIMIT); // see #2

        // add Etag from list items
        $response->setEtag($widget->hash($list));

        // response was not modified for this request
        if ($response->isNotModified($request) || !$list) {
            return $response;
        }

        // build list item entities
        foreach ($list as $key => $item) {
            $list[$key] = $widget->getWidgetItem($widget->getItem($item['id']));
        }

        return $this->render(
            'AnimeDbShikimoriNewItemsWidgetBundle:Widget:index.html.twig',
            ['items' => $list],
            $response
        );
    }
}
