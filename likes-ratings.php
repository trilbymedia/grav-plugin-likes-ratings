<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Common\Uri;
use Grav\Plugin\LikesRatings\Likes;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class LikesRatingsPlugin
 * @package Grav\Plugin
 */
class LikesRatingsPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => [
                ['autoload', 100000],
                ['onPluginsInitialized', 1000]
            ]
        ];
    }

    /**
     * [onPluginsInitialized:100000] Composer autoload.
     *
     * @return ClassLoader
     */
    public function autoload()
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        $likes = new Likes($this->config->get('plugins.likes-ratings'));
        $this->grav['likes'] = $likes;

        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onPageInitialized'     => ['onPageInitialized', 0],
            'onTwigInitialized'     => ['onTwigInitialized', 0],
            'onTwigTemplatePaths'   => ['onTwigTemplatePaths', 0],
            'onTwigSiteVariables'   => ['onTwigSiteVariables', 0],
        ]);
    }

    public function onPageInitialized() {

        $callback = $this->config->get('plugins.likes-ratings.callback');
        $route = $this->grav['uri']->path();
        // Process vote if appropriate
        if ($callback === $route) {

            // try to add the vote
            $result = $this->addVote();

            header('Content-Type: application/json');
            echo json_encode(['status' => $result[0], 'message' => $result[1], 'count' => $result[2] ?? -1]);
            exit();
        }
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onTwigInitialized() {
        $this->grav['twig']->twig()->addFunction(
            new \Twig_SimpleFunction('likes', [$this, 'generateLikes'])
        );
    }

    public function onTwigSiteVariables() {
        if ($this->config->get('plugins.likes-ratings.built_in_css')) {
            $this->grav['assets']
                ->addCss('plugin://likes-ratings/assets/likes-ratings.css');
        }
        $this->grav['assets']
            ->add('jquery', 101)
            ->addJs('plugin://likes-ratings/assets/likes-ratings.js');
    }

    public function generateLikes($id=null, $options = [])
    {
        $twig = $this->grav['twig'];
        $likes = $this->grav['likes'];

        $results = $likes->get($id);

        $callback = Uri::addNonce($this->grav['base_url'] . $this->config->get('plugins.likes-ratings.callback') . '.json','likes-ratings');

        $output = $twig->processTemplate('partials/likes-ratings.html.twig', [
            'id'        => $id,
            'uri'       => $callback,
            'ups'       => $results['ups'] ?? 0,
            'downs'     => $results['downs'] ?? 0,
            'options'   => $options
        ]);

        return $output;
    }

    protected function addVote() {
        $nonce = $this->grav['uri']->param('nonce');
        if (false && !Utils::verifyNonce($nonce, 'likes-ratings')) {
            return [false, 'Invalid security nonce'];
        }

        // get and filter the data
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?? 'ups';

        if ($id && $type) {
            return $this->grav['likes']->add($id);
        } else {
            return [false, 'Missing id or type', -1];
        }


    }
}
