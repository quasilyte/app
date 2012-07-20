<?php
class wikiMapSpecialController extends WikiaSpecialPageController {

    public function __construct() {
        parent::__construct( 'WikiMap', '', false );
    }

    public function init() {
        $this->businessLogic = F::build( 'wikiMap', array( 'currentTitle' => $this->app->wg->Title ) );

    }

    public function index() {
        $this->response->addAsset('extensions/wikia/WikiMap/js/d3.v2.min.js');
        $this->response->addAsset('extensions/wikia/WikiMap/js/jquery.xcolor.min.js');
        $this->response->addAsset('extensions/wikia/WikiMap/js/wikiMapIndexContent.js');

        $parameter = $this->getPar();
        $parameterSpaces = str_replace('_', ' ',$parameter);

        $wikiId = $this->getVal( 'wikiId', $this->wg->CityId );
        $this->wg->Out->setPageTitle( $this->wf->msg('wikiMap-specialpage-title'));
        // setting response data
        if (is_null($parameter)){
            $this->setVal( 'header', $this->wf->msg('wikiMap-title-nonparam'));
        }
        else {
            $artPath = $this->app->wg->get('wgArticlePath');
            $path = str_replace('$1', 'Category:', $artPath);
            $path.=$parameter;

            $output = '<a href="' . $path . '">' . $this->wf->msg('wikiMap-category') . $parameterSpaces . '</a>';
            $this->setVal( 'header', $output);



           // $this->setVal( 'header', $this->wf->msg('wikiMap-category') . $parameterNoSpaces . ']]');
        }
        $this->setVal( 'categoriesHeader', $this->wf->msg('wikiMap-categoriesHeader'));

        $this->setVal( 'res', $this->businessLogic->getArticles($parameter));
        //$this->setVal( 'path', $this->app->wg->get('wgArticlePath'));
        $this->setVal( 'colourArray', $this->businessLogic->getColours());
        $this->setVal( 'categories', $this->businessLogic->getListOfCategories());

    }
}