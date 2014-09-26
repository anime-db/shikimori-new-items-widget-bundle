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
use Symfony\Component\HttpFoundation\Response;
use AnimeDb\Bundle\CatalogBundle\Entity\Widget\Item;

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
        $response = new Response();
        $response->setMaxAge(self::CACHE_LIFETIME);
        $response->setSharedMaxAge(self::CACHE_LIFETIME);
        $response->setExpires((new \DateTime())->modify('+'.self::CACHE_LIFETIME.' seconds'));

        // update cache if app update
        if ($last_update = $this->container->getParameter('last_update')) {
            $response->setLastModified(new \DateTime($last_update));
        }
        // check items last update
        /* @var $repository \AnimeDb\Bundle\CatalogBundle\Repository\Item */
        $repository = $this->getDoctrine()->getRepository('AnimeDbCatalogBundle:Item');
        $last_update = $repository->getLastUpdate();
        if ($response->getLastModified() < $last_update) {
            $response->setLastModified($last_update);
        }

        $response->setEtag(md5($repository->count()));
        /* @var $widget \AnimeDb\Bundle\ShikimoriWidgetBundle\Service\Widget */
        $widget = $this->get('anime_db.shikimori.widget');

        /* @var $browser \AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser */
        $browser = $this->get('anime_db.shikimori.browser');
        $list = $browser->get(str_replace('#LIMIT#', self::LIST_LIMIT, self::PATH_NEW_ITEMS));
        $list = array_slice($list, 0, self::LIST_LIMIT); // see #2

        // create cache Etag by list items
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
