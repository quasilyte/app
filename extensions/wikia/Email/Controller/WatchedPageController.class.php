<?php

namespace Email\Controller;

use Email\Check;
use Email\EmailController;

abstract class WatchedPageController extends EmailController {

	/* @var \Title */
	protected $title;
	protected $summary;
	protected $currentRevId;
	protected $previousRevId;

	/**
	 * @return String
	 */
	protected abstract function getSubjectMessageKey();

	protected abstract function getSummaryMessageKey();

	public function getSubject() {

		$msgKey = $this->currentUser->isLoggedIn()
			? 'emailext-watchedpage-article-edited-subject'
			: 'emailext-watchedpage-article-edited-subject-anonymous';

		return wfMessage( $msgKey, $this->title->getPrefixedText(), $this->getCurrentUserName() )
			->inLanguage( $this->targetLang )
			->text();
	}

	public function initEmail() {
		$titleText = $this->request->getVal( 'title' );
		$titleNamespace = $this->request->getVal( 'namespace', NS_MAIN );

		$this->title = \Title::newFromText( $titleText, $titleNamespace );
		$this->summary = $this->getVal( 'summary' );

		$this->assertValidParams();

		$this->currentRevId = $this->getVal('currentRevId');
		if ( empty( $this->currentRevId ) ) {
			$this->currentRevId = $this->title->getLatestRevID( \Title::GAID_FOR_UPDATE );
		}
		$this->previousRevId = $this->getVal('previousRevId');
		if ( empty( $this->previousRevId ) ) {
			$this->previousRevId = $this->title->getPreviousRevisionID( $this->currentRevId, \Title::GAID_FOR_UPDATE );
		}
	}

	/**
	 * Validate the params passed in by the client
	 */
	protected function assertValidParams() {
		$this->assertValidTitle();
	}

	/**
	 * @throws \Email\Check
	 */
	protected function assertValidTitle() {
		if ( !$this->title instanceof \Title ) {
			throw new Check( "Invalid value passed for title (param: title)" );
		}

		if ( !$this->title->exists() && !$this->title->isDeletedQuick() ) {
			throw new Check( "Title doesn't exist." );
		}
	}

	protected function getFooterMessages() {
		$footerMessages = [
			wfMessage( 'emailext-unfollow-text',
				$this->title->getCanonicalUrl( 'action=unwatch' ),
				$this->title->getPrefixedText() )->inLanguage( $this->targetLang )->parse()
		];
		return array_merge( $footerMessages, parent::getFooterMessages() );
	}

	/**
	 * @template avatarLayout
	 */
	public function body() {
		$this->response->setData( [
			'salutation' => $this->getSalutation(),
			'summary' => $this->getSummary(),
			'editorProfilePage' => $this->getCurrentProfilePage(),
			'editorUserName' => $this->getCurrentUserName(),
			'editorAvatarURL' => $this->getCurrentAvatarURL(),
			'details' => $this->getDetails(),
			'buttonText' => $this->getButtonText(),
			'buttonLink' => $this->getButtonLink(),
			'contentFooterMessages' => $this->getContentFooterMessages(),
			'hasContentFooterMessages' => ( bool ) count( $this->getContentFooterMessages() ),
		] );
	}

	/**
	 * @return String
	 */
	private function getSalutation() {
		return wfMessage( 'emailext-watchedpage-salutation',
			$this->targetUser->getName() )->inLanguage( $this->targetLang )->text();
	}

	/**
	 * @return String
	 */
	private function getSummary() {
		return wfMessage( $this->getSummaryMessageKey(),
			$this->title->getFullURL(),
			$this->title->getPrefixedText()
		)->inLanguage( $this->targetLang )->parse();
	}

	/**
	 * @return String
	 */
	private function getDetails() {
		if ( !empty( $this->summary ) ) {
			return $this->summary;
		}
		return wfMessage( 'emailext-watchedpage-no-summary' )->inLanguage( $this->targetLang )->text();
	}

	/**
	 * @return String
	 */
	protected function getButtonText() {
		return wfMessage( $this->getButtonTextMessageKey() )->inLanguage( $this->targetLang )->text();
	}

	/**
	 * @return String
	 */
	protected function getButtonTextMessageKey() {
		return 'emailext-watchedpage-diff-button-text';
	}

	/**
	 * @return String
	 */
	protected function getButtonLink() {
		return $this->title->getFullUrl( [
			'diff' => $this->currentRevId
		] );
	}

	/**
	 * @return String
	 */
	protected function getArticleLinkText() {
		return wfMessage( 'emailext-watchedpage-article-link-text',
			$this->title->getFullURL( [
				'diff' => 0,
				'oldid' => $this->previousRevId
			] ),
			$this->title->getPrefixedText()
		)->inLanguage( $this->targetLang )->parse();
	}

	/**
	 * @param $title
	 * @return String
	 * @throws \MWException
	 */
	protected function getAllChangesText( $title ) {
		return wfMessage( 'emailext-watchedpage-view-all-changes',
			$title->getFullURL( [
				'action' => 'history'
			] ),
			$title->getPrefixedText()
		)->inLanguage( $this->targetLang )->parse();
	}

	/**
	 * @return Array
	 */
	protected function getContentFooterMessages() {
		return [
			$this->getArticleLinkText(),
			$this->getAllChangesText( $this->title ),
		];
	}
}

class WatchedPageEditedController extends WatchedPageController {
	/**
	 * @return String
	 */
	protected function getSubjectMessageKey() {
		return 'emailext-watchedpage-article-edited-subject';
	}

	/**
	 * @return String
	 */
	protected function getSummaryMessageKey() {
		return 'emailext-watchedpage-article-edited';
	}
}

class WatchedPageProtectedController extends WatchedPageController {
	/**
	 * @return String
	 */
	protected function getSubjectMessageKey() {
		return 'emailext-watchedpage-article-protected-subject';
	}

	/**
	 * @return String
	 */
	protected function getSummaryMessageKey() {
		return 'emailext-watchedpage-article-protected';
	}
}

class WatchedPageUnprotectedController extends WatchedPageController {
	/**
	 * @return String
	 */
	protected function getSubjectMessageKey() {
		return 'emailext-watchedpage-article-unprotected-subject';
	}

	/**
	 * @return String
	 */
	protected function getSummaryMessageKey() {
		return 'emailext-watchedpage-article-unprotected';
	}
}

class WatchedPageDeletedController extends WatchedPageController {
	/**
	 * @return String
	 */
	protected function getSubjectMessageKey() {
		return 'emailext-watchedpage-article-deleted-subject';
	}

	/**
	 * @return String
	 */
	protected function getSummaryMessageKey() {
		return 'emailext-watchedpage-article-deleted';
	}

	/**
	 * @return String
	 */
	protected function getButtonTextMessageKey() {
		return 'emailext-watchedpage-deleted-button-text';
	}

	/**
	 * @return String
	 */
	protected function getButtonLink() {
		return $this->title->getFullUrl();
	}

	/**
	 * @return Array
	 */
	protected function getContentFooterMessages() {
		return [];
	}
}

class WatchedPageRenamedController extends WatchedPageController {
	/** @var \Title */
	protected $newTitle;

	public function initEmail() {
		parent::initEmail();

		$this->newTitle = \WikiPage::factory( $this->title )->getRedirectTarget();
	}

	/**
	 * @return String
	 */
	protected function getSubjectMessageKey() {
		return 'emailext-watchedpage-article-renamed-subject';
	}

	/**
	 * @return String
	 */
	protected function getSummaryMessageKey() {
		return 'emailext-watchedpage-article-renamed';
	}

	/**
	 * Get link to current revision of new title because it's first revision of this title
	 *
	 * @return String
	 */
	protected function getArticleLinkText() {
		return wfMessage( 'emailext-watchedpage-article-link-text',
			$this->newTitle->getFullURL( [
					'diff' => 0,
					'oldid' => $this->currentRevId
			] ),
			$this->newTitle->getPrefixedText()
		)->inLanguage( $this->targetLang )->parse();
	}

	/**
	 * Get url to renamed Title
	 *
	 * @param $title
	 * @return String
	 */
	protected function getAllChangesText( $title ) {
		return parent::getAllChangesText( $this->newTitle );
	}
}
