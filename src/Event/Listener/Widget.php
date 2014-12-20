<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Event\Listener;

use AnimeDb\Bundle\AppBundle\Event\Widget\Get;
use AnimeDb\Bundle\CatalogBundle\Controller\HomeController;

/**
 * Widget
 *
 * @package AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Widget
{
    /**
     * Registr widget
     *
     * @param \AnimeDb\Bundle\AppBundle\Event\Widget\Get $event
     */
    public function onGetWidget(Get $event)
    {
        if ($event->getPlace() == HomeController::WIDGET_PALCE_BOTTOM) {
            $event->registr('AnimeDbShikimoriNewItemsWidgetBundle:Widget:index');
        }
    }
}
