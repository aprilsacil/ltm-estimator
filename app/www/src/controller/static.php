<?php //-->

use Cradle\Module\Utility\File;

/**
 * Render the Home Page
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/', function ($request, $response) {
    //Prepare body
    $data = [];

    //Render body
    $class = 'page-home';
    $title = cradle('global')->translate('Cradle OMS');
    $body = cradle('/app/www')->template('index', $data);

    //Set Content
    $response
        ->setPage('title', $title)
        ->setPage('class', $class)
        ->setContent($body);

    //Render blank page
    $this->trigger('www-render-page', $request, $response);
});

/**
 * Render the file preview / download
 * 
 * @param Request $request
 * @param Response $response
 */
$this->post('/cost/calculator', function ($request, $response) {
    $this->method('utility-cost-calculator', $response, $response);
});