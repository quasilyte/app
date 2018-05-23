/*global describe, expect, it, modules*/
describe('ext.wikia.adEngine.ml.modelFactory', function () {
	'use strict';

	var mocks = {
		adContext: {
			addCallback: function () {}
		},
		pageParams: {
			getPageLevelParams: function () {
				return {
					esrb: 'teen'
				};
			}
		},
		deviceDetect: {
			getDevice: function () {
				return 'desktop';
			}
		},
		featuredVideoData: {
			mediaId: 'abc7x7',
			videoTags: [ 'foo' ]
		},
		geo: {
			getCountryCode: function () {
				return 'PL';
			}
		},
		win: {
			wgCityId: '123'
		}
	};

	function getModule() {
		return modules['ext.wikia.adEngine.ml.inputParser'](
			mocks.adContext,
			mocks.pageParams,
			mocks.deviceDetect,
			mocks.geo,
			mocks.win,
			mocks.featuredVideoData
		);
	}

	function assertParsing(property, value, expectedResult) {
		var inputParser = getModule();

		expect(inputParser.parse([{ name: property, value: value }])).toEqual([expectedResult]);
	}

	it('Parse page data to binary values', function () {
		var inputParser = getModule();

		var data = inputParser.parse([
			{ name: 'country', value: 'PL' },
			{ name: 'country', value: 'GT' },
			{ name: 'device', value: 'desktop' },
			{ name: 'wikiId', value: '123' },
			{ name: 'esrb', value: '' },
			{ name: 'videoId', value: null },
			{ name: 'videoTag', value: 'foo' },
			{ name: 'videoTag', value: 'bar' },
		]);

		expect(data).toEqual([
			1,
			0,
			1,
			1,
			0,
			0,
			1,
			0
		]);
	});

	it('Parse country', function () {
		assertParsing('country', 'PL', 1);
		assertParsing('country', 'US', 0);
	});

	it('Parse device', function () {
		assertParsing('device', 'desktop', 1);
		assertParsing('device', 'smartphone', 0);
	});

	it('Parse esrb', function () {
		assertParsing('esrb', 'teen', 1);
		assertParsing('esrb', 'ec10', 0);
	});

	it('Parse videoId', function () {
		assertParsing('videoId', 'abc7x7', 1);
		assertParsing('videoId', 'Dd', 0);
	});

	it('Parse videoTag', function () {
		assertParsing('videoTag', 'foo', 1);
		assertParsing('videoTag', 'bar', 0);
	});

	it('Parse wikiId', function () {
		assertParsing('wikiId', '123', 1);
		assertParsing('wikiId', '789', 0);
	});
});
