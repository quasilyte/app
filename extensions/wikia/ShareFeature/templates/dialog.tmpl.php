<div id="shareFeatureRound" title="<?= wfMsg( 'sf-link' ) ?>" >
        <div>
		<div>
			<ul>
			<?php
				global $wgExtensionsPath;
				foreach( $sites as $site) {
			?>
				<li><img src="<?= $wgExtensionsPath ?>/wikia/ShareFeature/images/<?= strtolower( $site['name'] ) ?>.png" alt="<?= $site['name'] ?>"/><a href="<?= $site['url'] ?>" target="_blank" onclick="ShareFeature.ajax( <?= $site['id'] ?>  )"><?= $site['name'] ?></a></li>
			<?php
				}
			?>
			</ul>
		</div>
        </div>
</div>

