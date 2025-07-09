<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use Twig\TwigFunction;
use Grav\Plugin\DirectusDynpages\Utils;

/**
 * Class DirectusDynpagesPlugin
 * @package Grav\Plugin
 */
class DirectusDynpagesPlugin extends Plugin
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
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0]
            ],
            'onTwigExtensions'      => ['onTwigExtensions', 0],
            'onPagesInitialized'    => ['onPagesInitialized', 0],
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin())
        {
            return;
        }

        // Enable the main events we are interested in
        $this->enable([
            // Put your main events here
        ]);
    }

    public function onTwigExtensions()
    {
        $this->grav['twig']->twig()->addFunction(
            new TwigFunction('news_pagination', [new Utils, 'pagination'])
        );

        $this->grav['twig']->twig()->addFunction(
            new TwigFunction('news_categories', [new Utils, 'getCategories'])
        );
    }

    /**
     * hook into page routing to place our detail pages
     */
    public function onPagesInitialized($event)
    {
        $current = Uri::getCurrentRoute();
        $route = $current->getRoute();
        $this->simpleRouting($route);
    }


    private function simpleRouting(string $route)
    {
        $config = $this->config->get('plugins.directus-dynpages');

        $normalized = trim($route, '/');
        if (!$normalized)
        {
            return;
        }

        $parts = explode('/', $normalized );
        $entity = array_pop( $parts );
        $path = '/' . implode( '/', $parts );

        if ( $entity && in_array( $path, $config['routes'] ) )
        {
            $pages = $this->grav['pages'];
            $page = $pages->find( $path );
            $this->addPage( $page, $entity );
        }
    }

    /**
     * create a page on the fly by using a vessel page
     */
    protected function addPage( mixed $page, string $key ): void
    {
        if ( $page )
        {
            $config = $this->config->get('plugins.directus-dynpages');
            $template = $page->children()->first();

            if ( !$template || $template->folder() == $key )
            {
                return;
            }

            $flex = $this->grav['flex'];
            $pages = $this->grav['pages'];
            $route = $page->route() . '/' . $key; //rebuild the route

            // get the data from flex storage
            $collection_key = $template->header()->flex['collection'];
            $collection = $flex->getCollection( $collection_key );
            $object = $collection->filterBy( [ $config['slugField'] => $key ] )->first();

            // no entry - try full path to be sure
            if ( ! $object
                && property_exists( $page->header(), 'allow_full_path' )
                && $page->header()->allow_full_path
            )
            {
                $object = $collection->filterBy( [ $config['slugField'] => $route ] )->first();
            }

            // still no entry - no page
            if ( ! $object )
            {
                return;
            }

            // prepare the template
            $template->id($template->modified() . md5($route));
            $template->slug(basename($route));
            $template->folder(basename($route));
            $template->route($route);
            $template->rawRoute($route);
            $template->menu( $object['zbr_title'] );
            $template->title( $object['zbr_title'] );
            $template->modifyHeader( 'title', $object['zbr_title'] );
            $template->modifyHeader( 'flex', [ 'id' => $object->id, 'collection' => $collection_key ] );

            // publish page
            $pages->addPage($template, $route);
        }
    }
}
