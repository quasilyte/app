var LandingPage = {
	track: function(url) {
		$.tracker.byStr('landingpage/' + url);
	},

	init : function() {
		var self = LandingPage;

		$('#WikiaArticle').children('.LandingPage').click(function(ev) {
			var node = $(ev.target),
				itemIndex;

			// go to the parent node when image is clicked
			if (node.is('img')) {
				node = node.parent();
			}

			// track clicks on links only
			if (!node.is('a')) {
				return;
			}

			// screenshots
			if (node.hasParent('.LandingPageScreenshots')) {
				self.track('examples');
			}
			// three buttons
			else if (node.hasParent('.LandingPageButtons')) {
				itemIndex = node.parent().index();

				switch(itemIndex) {
					case 0:
						self.track('aboutwikia');
						break;

					case 1:
						self.track('faq');
						break;

					case 2:
						self.track('transition');
						break;
				}
			}
			// social icons
			else if (node.hasParent('.LandingPageLinks')) {
				itemIndex = node.parent().index();

				switch(itemIndex) {
					case 0:
						self.track('facebook');
						break;

					case 1:
						self.track('wikiablog');
						break;

					case 2:
						self.track('twitter');
						break;
				}
			}
		});

		// track page views
		self.track('view');
	}
};

$(function() {
	LandingPage.init();
});