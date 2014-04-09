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
use Doctrine\ORM\EntityRepository;
use AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser;
use AnimeDb\Bundle\ShikimoriFillerBundle\Service\Filler;
use AnimeDb\Bundle\CatalogBundle\Entity\Source;
use AnimeDb\Bundle\CatalogBundle\Entity\Widget\Item;
use AnimeDb\Bundle\CatalogBundle\Entity\Widget\Genre;
use AnimeDb\Bundle\CatalogBundle\Entity\Widget\Type;

/**
 * New items widget
 *
 * @package AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class WidgetController extends Controller
{
    /**
     * Cache expire on 1 day
     *
     * @var integer
     */
    const CACHE_EXPIRES = 86400;

    /**
     * Size of list items
     *
     * @var integer
     */
    const LIST_LIMIT = 4;

    /**
     * API path for get new items
     *
     * @var string
     */
    const PATH_NEW_ITEMS = '/animes?limit=#LIMIT#';

    /**
     * API path for get item info
     *
     * @var string
     */
    const PATH_ITEM_INFO = '/animes/#ID#';

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
        $response->setExpires(new \DateTime('@'.(time()+self::CACHE_EXPIRES)));
        // update cache if app update and Etag not Modified
        if ($last_update = $this->container->getParameter('last_update') && $request->getETags()) {
            $response->setLastModified(new \DateTime($last_update));
        }
        // check items last update
        /* @var $repository \AnimeDb\Bundle\CatalogBundle\Repository\Item */
        $repository = $this->getDoctrine()->getRepository('AnimeDbCatalogBundle:Item');
        $last_update = $repository->getLastUpdate();
        if ($response->getLastModified() < $last_update) {
            $response->setLastModified($last_update);
        }
        $etag = $repository->count().':';

        /* @var $browser \AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser */
        $browser = $this->get('anime_db.shikimori.browser');
        $list = $browser->get(str_replace('#LIMIT#', self::LIST_LIMIT, self::PATH_NEW_ITEMS));

        // TODO bug in API #2
        $list = array_slice($list, 0, self::LIST_LIMIT);

        // create cache Etag by list items
        if ($list) {
            $ids = [];
            foreach ($list as $item) {
                $ids[] = $item['id'];
            }
            $etag .= implode(',', $ids);
        }
        $response->setEtag(md5($etag));

        // response was not modified for this request
        if ($response->isNotModified($request)) {
            return $response;
        }

        $repository = $this->getDoctrine()->getRepository('AnimeDbCatalogBundle:Source');
        $locale = substr($request->getLocale(), 0, 2);
        $filler = null;
        if ($this->has('anime_db.shikimori.filler')) {
            $filler = $this->get('anime_db.shikimori.filler');
        }

        // build list item entities
        foreach ($list as $key => $item) {
            $list[$key] = $this->buildItem($item, $locale, $repository, $browser, $filler);
        }

        return $this->render('AnimeDbShikimoriNewItemsWidgetBundle:Widget:index.html.twig', ['items' => $list], $response);
    }

    /**
     * Build item entity
     *
     * @param array $item
     * @param string $locale
     * @param \Doctrine\ORM\EntityRepository $repository
     * @param \AnimeDb\Bundle\ShikimoriBrowserBundle\Service\Browser $browser
     * @param \AnimeDb\Bundle\ShikimoriFillerBundle\Service\Filler $filler
     *
     * @return \AnimeDb\Bundle\CatalogBundle\Entity\Widget\Item
     */
    protected function buildItem(
        array $item,
        $locale,
        EntityRepository $repository,
        Browser $browser,
        Filler $filler = null
    ) {
        $entity = new Item();
        if ($locale == 'ru' && $item['russian']) {
            $entity->setName($item['russian']);
        } else {
            $entity->setName($item['name']);
        }
        $entity->setLink($browser->getHost().$item['url']);
        $entity->setCover($browser->getHost().$item['image']['original']);

        /* @var $source \AnimeDb\Bundle\CatalogBundle\Entity\Source|null */
        $source = $repository->findOneByUrl($entity->getLink());
        if ($source instanceof Source) {
            $entity->setItem($source->getItem());
        } elseif ($filler instanceof Filler) {
            $entity->setLinkForFill($filler->getLinkForFill($browser->getHost().$item['url']));
        }

        return $entity;
    }
}