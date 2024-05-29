<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Utils;
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
     */
    public static function getSubscribedEvents()
    {
        return [
            'onCliInitialize' => [
                ['autoload', 100000],
                ['register', 1000]
            ],
            'onPluginsInitialized' => [
                ['autoload', 100000],
                ['register', 1000],
                ['onPluginsInitialized', 1000],

            ],
            'onShortcodeHandlers'       => ['onShortcodeHandlers', 0],
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
     * Register the service
     */
    public function register()
    {
        $this->grav['likes'] = function ($c) {
            return new Likes($c['config']->get('plugins.likes-ratings'));
        };
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onPageInitialized'     => ['onPageInitialized', 0],
            'onTwigInitialized'     => ['onTwigInitialized', 0],
            'onTwigTemplatePaths'   => ['onTwigTemplatePaths', 0],
            'onTwigSiteVariables'   => ['onTwigSiteVariables', 0],
            'onTwigLoader'          => ['onTwigLoader', 0],
        ]);
    }

    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/classes/shortcodes');
    }

    // Add images to twig template paths to allow inclusion of SVG files
    public function onTwigLoader()
    {
        $theme_paths = $this->grav['locator']->findResources('plugins://likes-ratings/assets');
        foreach($theme_paths as $images_path) {
            $this->grav['twig']->addPath($images_path, 'likes-ratings');
        }
    }

    public function onPageInitialized(Event $e)
    {
        $callback = $this->config->get('plugins.likes-ratings.callback');
        $route = $this->grav['uri']->path();
        // Process vote if appropriate
        if ($callback === $route) {

            // try to add the vote
            $result = $this->addVote();

            header('Content-Type: application/json');
            echo json_encode(['status' => $result[0], 'error' => $result[1], 'content' => $result[2] ?? 'Error: missing content...']);
            exit();
        }
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onTwigInitialized() {
        $this->grav['twig']->twig()->addFunction(
            new \Twig_SimpleFunction('likes_ratings', [$this, 'generateLikes'], ['is_safe' => ['html']])
        );
    }

    public function onTwigSiteVariables()
    {
        if ($this->config->get('plugins.likes-ratings.built_in_css')) {
            $this->grav['assets']
                ->addCss('plugin://likes-ratings/assets/likes-ratings.css');
        }
        $this->grav['assets']
            ->addJs('plugin://likes-ratings/assets/likes-ratings.js');
    }

    /**
     * @param mixed|null $id
     * @param array $options
     * @return string
     */
    public function generateLikes($id = null, $options = [])
    {
        /** @var Likes $likes */
        $likes = $this->grav['likes'];
        $id = $likes->getId($id);

        if (null === $id) {
            return '';
        }

        if (!empty($options)) {
            $likes->saveOptions($id, $options);
        }
        return $this->grav['likes']->generateLikes($id);
    }

    protected function addVote()
    {
        if (!Utils::verifyNonce($this->grav['uri']->param('nonce'), 'likes-ratings')) {
            return [false, 'Invalid security nonce'];
        }

        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $id = $data['id'] ?? null;
            $type = $data['type'] ?? null;

            if ($id && $type) {
                /** @var Likes $likes */
                $likes = $this->grav['likes'];
                $likes->mergeSavedOptions($id);
                return $likes->add($id, $type, 1);
            }
        } else {
            return [false,  "Failed to decode JSON. Error: " . json_last_error_msg(), -1];
        }

        return [false, 'Missing id or type', -1];
    }


}
