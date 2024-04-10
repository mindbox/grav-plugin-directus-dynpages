<?php
namespace Grav\Plugin\DirectusDynpages;

use Grav\Common\Grav;

class Utils
{
    public function pagination( $collection = null )
    {
        if (!$collection)
        {
            return false;
        }
        $grav = Grav::instance();
        $config = $grav['config'];
        $uri = $grav['uri'];

        $news_config = $config->get( 'plugins.directus-dynpages' );
        $current_page = $uri->currentPage();
        $param_sep = $config->get('system.param_sep');
        $delta = $news_config['pagination_delta'];
        $limit = $news_config['news_per_page'];

        // preserve url params, e.g. search
        $url_params = $this->url_params();
        $page_base = $grav['page']->url() . $url_params . '/page' . $param_sep;

        // how many entries?
        $collection_count = count( $collection );
        // how may paginated pages? round up for fractions
        $pagination_count = ceil( $collection_count / $limit );

        // prepare output
        $output = [
            'isFirst' => ( $current_page == 1 ),
            'isLast' => ( $current_page == $pagination_count ),
            'current' => $current_page,
            'stack' => [],
        ];

        if ( !$output['isFirst'] ) {
            $output['newerUrl'] = $page_base . ( $current_page - 1 );
        }

        if ( !$output['isLast'] ) {
            $output['olderUrl'] = $page_base . ( $current_page + 1 );
        }

        // spit out the list of pages with some info
        for ($i=1; $i < $pagination_count + 1; $i++)
        {
            $output['stack'][$i] = [
                'url' => $page_base . $i,
                'number' => $i,
                'isCurrent' => ($i == $current_page ),
                'isInDelta' => ( !$delta || abs( $current_page - $i) < $delta + 1 ),
                'isDeltaBorder' => ( $delta && abs( $current_page - $i) == $delta + 1 ),
            ];
        }

        // $grav['debugger']->addMessage( $output );

        return $output;
    }

    static function url_params()
    {
        $grav = Grav::instance();
        $config = $grav['config'];
        $uri = $grav['uri'];

        $url_params = explode( '/', ltrim((string) $uri->params() ?: '', '/') );
        foreach ($url_params as $key => $value) {
            if (strpos($value, 'page' . $config->get('system.param_sep')) !== false) {
                unset($url_params[$key]);
            }
        }

        $url_params = '/'.implode('/', $url_params);

        // check for empty params
        if ($url_params === '/') {
            $url_params = '';
        }

        return $url_params;
    }

    public static function getCategories()
    {
        $flex = Grav::instance()['flex'] ?? null;
        $collection = $flex ? $flex->getCollection('zbr_blogpost') : null;

        if ( $collection )
        {
            $raw = $collection->getDistinctValues( 'zbr_category' );
            $tags = [];
            foreach ( $raw as $tag )
            {
                $tags[] = $tag;
            }
            sort( $tags );
            return $tags;
        }

        return null;
    }

}