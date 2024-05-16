<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Inflector;
use Grav\Plugin\PageToc\UniqueSlugify;
use Grav\Plugin\PageTOCPlugin;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;

class LikesRatingsShortcode extends Shortcode
{
  public function init()
  {
    $this->shortcode->getHandlers()->add('likes-ratings', function(ProcessedShortcode $sc) {
        $this->shortcode->addAssets('css', 'plugin://shortcode-ui/css/ui-browser.css');
        $id = $sc->getParameter('id', null);
        return$this->grav['likes']->generateLikes($id, []);
    });
  }
}