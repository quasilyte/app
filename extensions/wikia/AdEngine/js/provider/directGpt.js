/*global define*/
/*jshint maxlen: 150*/
define('ext.wikia.adEngine.provider.directGpt', [
	'ext.wikia.adEngine.adContext',
	'ext.wikia.adEngine.provider.factory.wikiaGpt',
	'ext.wikia.adEngine.slotTweaker',
	'wikia.log'
], function (adContext, factory, slotTweaker, log) {
	'use strict';

	var context = adContext.getContext(),
		logGroup = 'ext.wikia.adEngine.provider.directGpt',
		gptFlushed = false,
		pendingSlots = [],
		delayedSlotsQueue = [],
		slotMap = {
			CORP_TOP_LEADERBOARD:       {size: '728x90,1030x130,1030x65,1030x250,970x365,970x250,970x90,970x66,970x180,980x150', loc: 'top'},
			CORP_TOP_RIGHT_BOXAD:       {size: '300x250,300x600,300x1050', loc: 'top'},
			EXIT_STITIAL_BOXAD_1:       {size: '300x250,600x400,800x450,550x480', loc: 'exit'},
			HOME_TOP_LEADERBOARD:       {size: '728x90,1030x130,1030x65,1030x250,970x365,970x250,970x90,970x66,970x180,980x150', loc: 'top'},
			HOME_TOP_RIGHT_BOXAD:       {size: '300x250,300x600,300x1050', loc: 'top'},
			HUB_TOP_LEADERBOARD:        {size: '728x90,1030x130,1030x65,1030x250,970x365,970x250,970x90,970x66,970x180,980x150', loc: 'top'},
			INCONTENT_1A:               {size: '300x250', loc: 'middle', pos: 'incontent_1'},
			INCONTENT_1B:               {size: '300x250,160x600', loc: 'middle', pos: 'incontent_1'},
			INCONTENT_1C:               {size: '300x250,160x600,300x600', loc: 'middle', pos: 'incontent_1'},
			INCONTENT_2A:               {size: '300x250', loc: 'middle', pos: 'incontent_2'},
			INCONTENT_2B:               {size: '300x250,160x600', loc: 'middle', pos: 'incontent_2'},
			INCONTENT_2C:               {size: '300x250,160x600,300x600', loc: 'middle', pos: 'incontent_2'},
			INCONTENT_3A:               {size: '300x250', loc: 'middle', pos: 'incontent_3'},
			INCONTENT_3B:               {size: '300x250,160x600', loc: 'middle', pos: 'incontent_3'},
			INCONTENT_3C:               {size: '300x250,160x600,300x600', loc: 'middle', pos: 'incontent_3'},
			INCONTENT_BOXAD_1:          {size: '300x250', loc: 'middle'},
			INCONTENT_LEADERBOARD_1:    {size: '728x90,468x90', loc: 'middle'},
			INCONTENT_LEADERBOARD_2:    {size: '728x90,468x90', loc: 'middle'},
			INCONTENT_LEADERBOARD_3:    {size: '728x90,468x90', loc: 'middle'},
			INVISIBLE_SKIN:             {size: '1000x1000,1x1', loc: 'top'},
			LEFT_SKYSCRAPER_2:          {size: '160x600', loc: 'middle'},
			LEFT_SKYSCRAPER_3:          {size: '160x600', loc: 'footer'},
			MODAL_INTERSTITIAL:         {size: '300x250,600x400,800x450,550x480', loc: 'modal'},
			MODAL_INTERSTITIAL_1:       {size: '300x250,600x400,800x450,550x480', loc: 'modal'},
			MODAL_INTERSTITIAL_2:       {size: '300x250,600x400,800x450,550x480', loc: 'modal'},
			MODAL_INTERSTITIAL_3:       {size: '300x250,600x400,800x450,550x480', loc: 'modal'},
			MODAL_INTERSTITIAL_4:       {size: '300x250,600x400,800x450,550x480', loc: 'modal'},
			MODAL_INTERSTITIAL_5:       {size: '300x250,300x600,728x90,970x250,160x600', loc: 'modal'},
			MODAL_RECTANGLE:            {size: '300x100', loc: 'modal'},
			PREFOOTER_LEFT_BOXAD:       {size: '300x250', loc: 'footer'},
			PREFOOTER_RIGHT_BOXAD:      {size: '300x250', loc: 'footer'},
			TOP_LEADERBOARD:            {size: '728x90,1030x130,1030x65,1030x250,970x365,970x250,970x90,970x66,970x180,980x150', loc: 'top'},
			TOP_RIGHT_BOXAD:            {size: '300x250,300x600,300x1050', loc: 'top'},
			GPT_FLUSH:                  {skipCall: true}
		},
		gptConfig = { // slots to use SRA with
			CORP_TOP_LEADERBOARD: 'wait',
			HUB_TOP_LEADERBOARD:  'wait',
			TOP_LEADERBOARD:      'wait',
			HOME_TOP_LEADERBOARD: 'wait',
			INVISIBLE_SKIN:       'wait',
			CORP_TOP_RIGHT_BOXAD: 'flush',
			TOP_RIGHT_BOXAD:      'flush',
			HOME_TOP_RIGHT_BOXAD: 'flush',
			GPT_FLUSH:            'flush'
		},
		provider = factory.createProvider(
			logGroup,
			'DirectGpt',
			'gpt',
			slotMap,
			{
				beforeSuccess: function (slotName) {
					slotTweaker.removeDefaultHeight(slotName);
					slotTweaker.removeTopButtonIfNeeded(slotName);
					slotTweaker.adjustLeaderboardSize(slotName);
					removePendingSlotAndPushDelayedQueue(slotName);
				},
				beforeHop: function (slotName) {
					removePendingSlotAndPushDelayedQueue(slotName);
				},
				shouldFlush: function (slotName) {
					log(['shouldFlush', slotName]);
					if (gptConfig[slotName] === 'flush') {
						gptFlushed = true;
					}

					log(['shouldFlush', slotName, gptFlushed]);
					return gptFlushed;
				}
			}
		);

	function removePendingSlotAndPushDelayedQueue(slotName) {
		if (!context.opts.delayBtf) {
			return;
		}
		var index = pendingSlots.indexOf(slotName);
		if (index > -1) {
			pendingSlots.splice(index, 1);
			if (pendingSlots.length === 0 && delayedSlotsQueue.length) {
				pushDelayedQueue();
			}
		}
	}

	function pushDelayedQueue() {
		delayedSlotsQueue.forEach(function (slot) {
			if (window.ads.runtime.disableBtf) {
				slot.success({
					adType: 'blocked'
				});
				slotTweaker.hide(slot.slotName);
				return;
			}
			provider.fillInSlot(slot.slotName, slot.success, slot.hop);
		});
	}

	function delayBtfSlot(slotName, success, hop) {
		if (!!gptConfig[slotName]) {
			if (!gptFlushed) {
				pendingSlots.push(slotName);
			}
			provider.fillInSlot(slotName, success, hop);
			return;
		}

		delayedSlotsQueue.push({
			slotName: slotName,
			success: success,
			hop: hop
		});
	}

	function fillInSlotWithDelay(slotName, success, hop) {
		if (context.opts.delayBtf && (!gptFlushed || pendingSlots.length > 0)) {
			delayBtfSlot(slotName, success, hop);
		} else {
			provider.fillInSlot(slotName, success, hop);
		}
	}

	return {
		name: provider.providerName,
		canHandleSlot: provider.canHandleSlot,
		fillInSlot: fillInSlotWithDelay
	};
});
