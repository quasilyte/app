<? if ( !$isBlocked && $commentingAllowed ): ?>
	<? if ( $isMiniEditorEnabled ): ?>
		<?= $app->renderView( 'MiniEditorController', 'Setup' ) ?>
	<? endif ?>
	<div id="article-comm-info" class="article-comm-info"></div>
	<? if ( $isMiniEditorEnabled ): ?>
		<?= $app->getView( 'MiniEditorController', 'Header', [
			'attributes' => [
				'id' => 'article-comments-minieditor-newpost',
				'data-min-height' => 100,
				'data-max-height' => 400
			]
		] )->render()
		?>
	<? endif ?>
	<div class="session">
		<?= $avatar ?>
		<? if ( $isAnon ): ?>
			<?= wfMessage( 'oasis-comments-anonymous-prompt' )->parse() ?>
		<? endif ?>
	</div>
	<form action="<?= $title->getFullURL() ?>" method="post" class="article-comm-form" id="article-comm-form">
		<input type="hidden" name="wpArticleId" value="<?= $title->getArticleId() ?>" />
		<? if ( $isMiniEditorEnabled ): ?>
			<?= $app->getView( 'MiniEditorController', 'Editor_Header' )->render() ?>
		<? endif ?>
		<textarea name="wpArticleComment" id="article-comm"></textarea>
		<? if ( $isMiniEditorEnabled ): ?>
			<?= $app->getView( 'MiniEditorController', 'Editor_Footer' )->render() ?>
		<? endif ?>
		<? if ( !$isReadOnly ): ?>
			<div class="buttons" data-space-type="buttons">
				<input type="submit" name="wpArticleSubmit" id="article-comm-submit" class="wikia-button actionButton" value="<?= wfMessage( 'article-comments-post' )->escaped() ?>" />
				<img src="<?= $ajaxicon ?>" class="throbber" />
			</div>
		<? endif ?>
	</form>
	<? if ( $isMiniEditorEnabled ): ?>
		<?= $app->getView( 'MiniEditorController', 'Footer' )->render() ?>
	<? endif ?>
<? elseif ( $isBlocked ): ?>
	<p><?= wfMessage( 'article-comments-comment-cannot-add' )->escaped() ?></p>
	<p><?= $reason ?></p>
<? elseif ( !$commentingAllowed ): ?>
	<p class="no-comments-allowed">
		<?= $isAnon ? wfMessage( 'article-comments-login' )->parse() : wfMessage( 'article-comments-comment-cannot-add' )->escaped() ?>
	</p>
<? endif ?>