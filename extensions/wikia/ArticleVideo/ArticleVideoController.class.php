<?php

class ArticleVideoController extends WikiaController {
	public function index() {
		$wg = F::app()->wg;

		$articleId = RequestContext::getMain()->getTitle()->getArticleID();

		$this->setVal( 'thumbnailUrl', $wg->featuredVideos[$articleId]['thumbnailUrl'] );

		// TODO: replace it with DS icon when it's ready (XW-2824)
		$this->setVal( 'closeIconUrl',
			$wg->extensionsPath . '/wikia/ArticleVideo/images/close.svg' );
		$this->setVal( 'videoPlayButtonUrl',
			$wg->extensionsPath . '/wikia/ArticleVideo/images/play-button-solid.svg' );
		$this->setVal( 'videoDetails', $wg->featuredVideos[$articleId] );
	}
}
