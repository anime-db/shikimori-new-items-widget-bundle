<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Tests\Event\Listener;

use AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Event\Listener\Widget;
use AnimeDb\Bundle\CatalogBundle\Controller\HomeController;

/**
 * Test widget
 *
 * @package AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Tests\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class WidgetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Widget
     *
     * @var \AnimeDb\Bundle\ShikimoriNewItemsWidgetBundle\Event\Listener\Widget
     */
    protected $widget;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->widget = new Widget();
    }

    /**
     * Get places
     *
     * @return array
     */
    public function getPlaces()
    {
        return [
            [HomeController::WIDGET_PALCE_BOTTOM],
            [HomeController::WIDGET_PALCE_TOP]
        ];
    }

    /**
     * Test on get widget
     *
     * @dataProvider getPlaces
     *
     * @param string $place
     */
    public function testOnGetWidget($place)
    {
        $event = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Event\Widget\Get')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getPlace')
            ->willReturn($place);
        if ($place == HomeController::WIDGET_PALCE_BOTTOM) {
            $event
                ->expects($this->once())
                ->method('registr')
                ->with('AnimeDbShikimoriNewItemsWidgetBundle:Widget:index');
        } else {
            $event
                ->expects($this->never())
                ->method('registr');
        }

        $this->widget->onGetWidget($event);
    }
}
