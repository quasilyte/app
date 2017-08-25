(function(window,$){
		console.trace('WE.plugins');
	var WE = window.WikiaEditor = window.WikiaEditor || (new Observable()),
		requires = ['core', 'noticearea', 'loadingstatus','pagecontrols', /*'restoreedit',*/ 'autoresizer','edittools',
			'widemodemanager', 'railminimumheight', 'tracker', 'cssloadcheck', 'preloads',
			'leaveconfirm', 'addfile', 'editorsurvey', 'flowtracking'];

	if (window.enableWikitextSyntaxHighlighting) {
		requires.push('syntaxhighlighterqueueinit');
	}

	if (window.enableTemplateClassificationEditorPlugin) {
		requires.push('templateclassificationeditorplugin');
	}

	/**
	 * Shortcut to automatically add all Wikia specific plugins
	 */
	WE.plugins.wikiacore = $.createClass(WE.plugin,{

		requires: requires

	});

})(this,jQuery);
