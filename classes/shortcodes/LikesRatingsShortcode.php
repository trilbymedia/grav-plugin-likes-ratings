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
        $likes = $this->grav['likes'];
        $id = $likes->getId($sc->getParameter('id', null));
        $options = array_filter([
            'disable_after_vote' => $sc->getParameter('disable_after_vote'),
            'unique_ip_check' => $sc->getParameter('unique_ip_check'),
            'readonly' => $sc->getParameter('readonly'),
            'twig_template' => $sc->getParameter('twig_template'),
        ], function($value) { return !is_null($value); });
        $likes->saveOptions($id, $options);
        return $likes->generateLikes($id);
    });
  }
}