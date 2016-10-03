(function(window,$){

	var WE = window.WikiaEditor = window.WikiaEditor || (new Observable());

	var editorName = function(mode) {
		var RTE = (window.RTE === undefined);

		if (!RTE) {
			return 'rte-' + mode;
		} else if (mode === 'source') {
			return 'sourceedit';
		}

		return mode;
	};

	WE.plugins.flowtracking = $.createClass(WE.plugin,{

		initEditor: function(editor) {
			require(['wikia.flowTracking', 'wikia.querystring', 'mw'], function(flowTrack, QueryString, mw) {
				var namespaceId = mw.config.get('wgNamespaceNumber'),
					articleId = mw.config.get('wgArticleId');

				// Track only creating articles (wgArticleId=0) from namespace 0 (Main)
				// IMPORTANT: on Special:CreatePage even after providing article title the namespace is set to -1 (Special Page)
				if (namespaceId === 0 && articleId === 0) {
					var qs = new QueryString(window.location.href);

					// 'flow' is the parameter passed in the url if user has started a flow already
					var flowParam = qs.getVal('flow', false);

					if (flowParam || document.referrer) {
						//TODO: track middle step for other flows
					} else {
						flowTrack.beginFlow('create-page-direct-url', {editor: editorName(editor.mode)});
					}
				}

			});
		}
	});
})(this,jQuery);
