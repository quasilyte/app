<?php
namespace Wikia\Search\Test;
use \Wikia\Search\Config, \Solarium_Query_Select, \ReflectionProperty, \ReflectionMethod, \Wikia\Search\Utilities;
/**
 * Tests for Config class
 */
class ConfigTest extends BaseTest {

	public function setUp() {
		$this->interface = $this->getMockBuilder( '\Wikia\Search\MediaWikiService' )
		                        ->disableOriginalConstructor();
		
		$this->config = $this->getMockBuilder( '\\Wikia\Search\Config' )
		                     ->disableOriginalConstructor();

		parent::setUp();
	}
	
	protected function setInterface( $config, $interface ) {
		$refl = new ReflectionProperty( '\\Wikia\\Search\\Config', 'interface' );
		$refl->setAccessible( true );
		$refl->setValue( $config, $interface );
	}
	
	/**
	 * @covers \Wikia\Search\Config::__construct
	 */
	public function testConstructor() {
		$newParams = array( 'rank' => 'newest');
		$config    = new Config( $newParams );
		$this->assertEquals(
				'newest',
				$config->getRank(),
				'Parameters passed during construction with a key equal to a default parameter should be overwritten.'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::__call
	 */
	public function testMagicMethods() {
		$config = new Config();
		$this->assertNull(
				$config->getValueThatDoesntExist(),
				'An accessor method value that has not been set should return null.'
		);
		$this->assertInstanceOf(
				'\Wikia\Search\Config',
				$config->setValueThatDoesntExist( true ),
				'A dynamic mutator method should provide a fluent interface.'
		);
		$this->assertTrue(
				$config->getValueThatDoesntExist(),
				'A dynamic accessor method that has had its value set should return that value.'
		);
		$this->assertEquals(
				$config->getValueThatDoesntExist(),
				$config['valueThatDoesntExist'],
				'Any value set in \Wikia\Search\Config should be exposed via array access.'
		);
		$exception = false;
		try {
			$config->thisIsAMethodIJustMadeUp();
		} catch ( \BadMethodCallException $exception ) { }

		$this->assertInstanceOf(
				'BadMethodCallException',
				$exception
		);
	}

	/**
	 * @covers \Wikia\Search\Config::offsetExists
	 * @covers \Wikia\Search\Config::offsetGet
	 * @covers \Wikia\Search\Config::offsetSet
	 * @covers \Wikia\Search\Config::offsetUnset
	 */
	public function testArrayAccessMethods() {
		$config = new \Wikia\Search\Config();
		$this->assertNull(
		        $config['valueThatDoesntExist'],
		        'Array access for an unknown key should return null.'
		);
		$config['valueThatDoesntExist'] = true;
		$this->assertTrue(
				$config['valueThatDoesntExist'],
				'Array access value setting should result in future array access returning the assigned value.'
		);
		if ( isset( $config['valueThatDoesntExist'] ) ) {
			unset($config['valueThatDoesntExist']);
		}
		$this->assertNull(
		        $config['valueThatDoesntExist'],
		        'Unsetting an array key for a value should result in it returning null in future access.'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::getSize
	 * @covers \Wikia\Search\Config::getLength
	 * @covers \Wikia\Search\Config::getLimit
	 * @covers \Wikia\Search\Config::setLimit
	 */
	public function testGetSize() {
		$config = new \Wikia\Search\Config();
		$this->assertEquals(
		        \Wikia\Search\Config::RESULTS_PER_PAGE,
		        $config->getLength(),
		        '\Wikia\Search\Config getLength should default to constant \Wikia\Search\Config::RESULTS_PER_PAGE.'
		);
		$this->assertEquals(
				$config->getSize(),
				$config->getLength(),
				'\Wikia\Search\Config getSize and getLength methods should be synonymous.'
		);
		$this->assertEquals(
				$config->getSize(),
				$config->getLimit(),
				'\Wikia\Search\Config getSize and getLimit methods should be synonymous without an article match.'
		);
		$mockArticleMatch = $this->getMockBuilder( 'Wikia\Search\Match\Article' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$limit = $config->getLimit();

		$config	->setArticleMatch	( $mockArticleMatch )
				->setStart			( 0 );

		$this->assertEquals(
				\Wikia\Search\Config::RESULTS_PER_PAGE - 1,
				$config->getLength(),
				'A stored article match in \Wikia\Search\Config should result in reducing the length value by 1 if start=0.'
		);
		$this->assertEquals(
				$limit,
				$config->getLimit(),
				'The return value of \Wikia\Search\Config::getLimit should not mutate regardless of article match if start=0.'
		);
		$this->assertEquals(
		        $config->getSize(),
		        $config->getLength(),
		        '\Wikia\Search\Config getSize and getLength methods should be synonymous, even with article match at start=0.'
		);
		$this->assertNotEquals(
				$config->getLimit(),
				$config->getLength(),
				'\Wikia\Search\Config::getLimit and \Wikia\Search\Config::getLength should not be equal if we have an article match at start=0.'
		);

		$config->setStart( 10 );

		$this->assertEquals(
		        \Wikia\Search\Config::RESULTS_PER_PAGE,
		        $config->getLength(),
		        'A stored article match in \Wikia\Search\Config should not result in reducing the length value by 1 if start != 0.'
		);
		$this->assertEquals(
		        $limit,
		        $config->getLimit(),
		        'The return value of \Wikia\Search\Config::getLimit should not mutate regardless of article match or start.'
		);
		$this->assertEquals(
		        $config->getSize(),
		        $config->getLength(),
		        '\Wikia\Search\Config getSize and getLength methods should be synonymous, even with article match, regardless of start.'
		);
		$this->assertEquals(
		        $config->getLimit(),
		        $config->getLength(),
		        '\Wikia\Search\Config::getLimit and \Wikia\Search\Config::getLength should be equal if we have an article match at start > 0.'
		);
		$newLimit = 20;
		$this->assertEquals(
				$config,
				$config->setLimit( $newLimit ),
				'\Wikia\Search\Config::setLimit should provide fluent interface.'
		);
		$this->assertEquals(
				$newLimit,
				$config->getLimit(),
				'Setting a limit should return that value when calling getLimit.'
		);
		$this->assertEquals(
				$newLimit,
				$config->getLength(),
				'Setting a limit should set the same key used by size and length methods.'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::setQuery
	 * @covers \Wikia\Search\Config::getQuery
	 * @covers \Wikia\Search\Config::getNamespaces
	 * @covers \Wikia\Search\Config::getQueryNoQuotes
	 * @todo
	public function testQueryAndNamespaceMethods() {

		$config = new \Wikia\Search\Config();
		$noNsQuery = 'foo';
		$nsQuery = 'File:foo';
		$phantomNsQuery = 'file';

		$searchEngineMock	= $this->getMock( 'SearchEngine', array( 'DefaultNamespaces' ), array() );

		$expectedDefaultNamespaces = array( NS_MAIN );

		$searchEngineMock
			->staticExpects	( $this->at( 0 ) )
			->method		( 'DefaultNamespaces' )
			->will			( $this->returnValue( null ) )
		;
		$searchEngineMock
			->staticExpects	( $this->at( 1 ) )
			->method		( 'DefaultNamespaces' )
			->will			( $this->returnValue( $expectedDefaultNamespaces ) )
		;

		$this->mockClass( 'SearchEngine',	$searchEngineMock );
		$this->mockApp();
		F::setInstance( 'SearchEngine', $searchEngineMock );

		$emptyNamespaces = $config->getNamespaces();

		$this->assertEmpty( $emptyNamespaces );

		$originalNamespaces = $config->getNamespaces();
		$this->assertEquals(
				$expectedDefaultNamespaces,
				$originalNamespaces,
				'\Wikia\Search\Config::getNamespaces should return SearchEngine::DefaultNamespaces if namespaces are not initialized.'
		);
		$this->assertFalse( $config->getQuery(), '\Wikia\Search\Config::getQuery should return false if the query has not been set.');
		$this->assertEquals(
				$config,
				$config->setQuery( $noNsQuery ),
				'\Wikia\Search\Config::setQuery should provide a fluent interface'
		);
		$this->assertEquals(
				$noNsQuery,
				$config->getQuery(),
				'Calling setQuery for a basic query should store the query value, accessible using getQuery.'
		);
		$this->assertEquals(
				$config->getQuery(),
				$config->getOriginalQuery(),
				'The original query and the actual query should match for non-namespaced queries.'
		);
		$this->assertEquals(
				$originalNamespaces,
				$config->getNamespaces(),
				'A query without a valid namespace prefix should not mutate the namespaces stored in the search config.'
		);
		$this->assertEquals(
				$config,
				$config->setQuery( $nsQuery ),
				'\Wikia\Search\Config::setQuery should provide a fluent interface'
		);
		$this->assertEquals(
		        $nsQuery,
		        $config->getOriginalQuery(),
		        'The original query should be stored under the "originalQuery" key regardless of prefix.'
		);
		$this->assertEquals(
				$noNsQuery,
				$config->getQuery(),
				'The namespace prefix for a query should be stripped from the main query value.'
		);
		$this->assertNotEquals(
		        $config->getQuery(),
		        $config->getOriginalQuery(),
		        'The actual query and the original query should not be equivalent when passed a valid namespace prefix query.'
		);
		$this->assertEquals(
		        array_merge( $originalNamespaces, array( NS_FILE ) ),
		        $config->getNamespaces(),
		        'Setting a namespace-prefixed query should result in the appropriate namespace being appended.'
		);
		$tildeQuery = 'foo~';
		$this->assertEquals(
				'foo\~',
				$config->setQuery( $tildeQuery )->getQuery(),
				'A query with a tilde should be escaped in getQuery.'
		);
		$quoteQuery = '"foo bar"';
		$this->assertEquals(
				'\"foo bar\"',
				$config->setQuery( $quoteQuery )->getQuery(),
				'A query with quotes should have the quotes escaped by default in getQuery.'
		);
		$this->assertEquals(
		        '"foo bar"',
		        $config->setQuery( $quoteQuery )->getQuery( true ),
		        'A query with quotes should have its quotes left alone if the first parameter of getQuery is passed as true.'
		);
		$this->assertEquals(
				'foo bar',
				$config->setQuery( $quoteQuery )->getQueryNoQuotes(),
				'A query with double quotes should have its quotes stripped in the default versoin of getQueryNoQuotes.'
		);
		$this->assertEquals(
		        'foo bar\~',
		        $config->setQuery( $quoteQuery.'~' )->getQueryNoQuotes(),
		        'Tildes should be escaped in the default versoin of getQueryNoQuotes.'
		);
		$this->assertEquals(
		        'foo bar~',
		        $config->setQuery( $quoteQuery.'~' )->getQueryNoQuotes( true ),
		        'Tildes should not be escaped in the raw versoin of getQueryNoQuotes.'
		);

		$xssQuery = "foo'<script type='javascript'>alert('xss');</script>";
		$this->assertEquals(
				"foo'alert\\('xss'\\);",
				$config->setQuery( $xssQuery )->getQuery(),
				'Setting a query should result in the sanitization and html entity decoding of that query.'
		);
		$this->assertEquals(
				"foo alert\\(xss\\);",
				$config->getQueryNoQuotes(),
				"Queries with quotes or apostrophes between two letters should be replaced with spaces with getQueryNoQuotes."
		);

		$htmlEntityQuery = "'foo & bar &amp; baz' &quot;";
		$this->assertEquals(
				"'foo & bar & baz' \\\"",
				$config->setQuery( $htmlEntityQuery )->getQuery(),
				"HTML entities in queries should be decoded when being set."
		);
		$this->assertEquals(
		        $config->setQuery( $htmlEntityQuery )->getQuery( \Wikia\Search\Config::QUERY_DEFAULT ),
		        $config->setQuery( $htmlEntityQuery )->getQuery(),
		        "The default behavior of the getQuery method should be identical to passing the Wikia\\Search\\Config::QUERY_DEFAULT constant."
		);

		$this->assertEquals(
		        "'foo & bar & baz' \"",
		        $config->setQuery( $htmlEntityQuery )->getQuery( \Wikia\Search\Config::QUERY_RAW ),
		        "HTML entities in queries should be decoded when being set. Raw-strategy queries shouldn't escape anything."
		);
		$this->assertEquals(
		        "'foo &amp; bar &amp; baz' &quot;",
		        $config->setQuery( $htmlEntityQuery )->getQuery( \Wikia\Search\Config::QUERY_ENCODED ),
		        "HTML entities in queries should be decoded when being set. HTML-decoded queries should properly HTML-encode all entities on access if using encoded strategy."
		);

		$utf8Query = '"аВатаР"';
		$this->assertEquals(
				'\"аВатаР\"',
				$config->setQuery( $utf8Query )->getQuery( \Wikia\Search\Config::QUERY_DEFAULT ),
				'\Wikia\Search\Config::setQuery should not unnecessarily mutate UTF-8 characters. Retrieving them should return those characters, properly encoded.'
		);
		$this->assertEquals(
				'"аВатаР"',
				$config->setQuery( $utf8Query )->getQuery( \Wikia\Search\Config::QUERY_RAW ),
				'\Wikia\Search\Config::getQuery() should not unnecessarily mutate UTF-8 characters, and should not escape quotes when asking for raw query.'
		);
		$this->assertEquals(
		        htmlentities( '"аВатаР"', ENT_COMPAT, 'UTF-8' ),
		        $config->setQuery( $utf8Query )->getQuery( \Wikia\Search\Config::QUERY_ENCODED ),
		        '\Wikia\Search\Config::getQuery() should properly HTML-encode UTF-8 characters when using the encoded query strategy.'
		);

		$config->setQuery( 'foo bar wiki' );
		$config->setIsInterWiki( true );

		$this->assertEquals(
				'foo bar',
				$config->getQuery(),
				'\Wikia\Search\Config::getQuery() should strip the term "wiki" from the set query if the search is interwiki'
		);

		$config->setQuery( $phantomNsQuery );
		$this->assertEquals(
				$phantomNsQuery,
				$config->getQuery(),
				'A query that initially matches a namespaces but does not end with a colon should not strip namespaces'
		);

	}*/

	/**
	 * @covers \Wikia\Search\Config::getSort
	 */
	public function testGetSort() {
		$config = new \Wikia\Search\Config;

		$defaultRank = array( 'score',		Solarium_Query_Select::SORT_DESC );

		$this->assertEquals(
				$defaultRank,
				$config->getSort(),
				'Search config should sort by score descending by default.'
		);

		$config->setRank( 'foo' );

		$this->assertEquals(
		        $defaultRank,
		        $config->getSort(),
		        'A malformed rank key should return the default sort.'
		);

		$config->setRank( 'newest' );

		$this->assertEquals(
				array( 'created',	Solarium_Query_Select::SORT_DESC ),
				$config->getSort(),
				'A well-formed rank key should return the appropriate sort array.'
		);

		$config->setSort( array( 'created', 'asc' ) );

		$this->assertEquals(
				array( 'created', 'asc' ),
				$config->getSort(),
				'\Wikia\Search\Config::getSort should return a value set by setSort if it has been invoked'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::hasArticleMatch
	 * @covers \Wikia\Search\Config::setArticleMatch
	 * @covers \Wikia\Search\Config::getArticleMatch
	 * @covers \Wikia\Search\Config::hasMatch
	 * @covers \Wikia\Search\Config::getMatch
	 */
	public function testArticleMatching() {
		$mockArticleMatch = $this->getMockBuilder( 'Wikia\Search\Match\Article' )
		                         ->disableOriginalConstructor()
		                         ->getMock();
		$config = new \Wikia\Search\Config();

		$this->assertFalse(
				$config->hasArticleMatch(),
				'\Wikia\Search\Config should not have an article match by default.'
		);
		$this->assertNull(
				$config->getArticleMatch(),
				'\Wikia\Search\Config should return null when getting an uninitialized article match'
		);
		$this->assertEquals(
				$config,
				$config->setArticleMatch( $mockArticleMatch ),
				'\Wikia\Search\Config::setArticleMatch should provide a fluent interface.'
		);
		$this->assertEquals(
				$mockArticleMatch,
				$config->getArticleMatch(),
				'\Wikia\Search\Config::getArticleMatch should return the appropriate article match once set.'
		);
		$this->assertTrue(
				$config->hasMatch()
		);
		$this->assertEquals(
				$mockArticleMatch,
				$config->getMatch(),
				'\Wikia\Search\Config::getMatch should return either article or wiki match.'
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::hasWikiMatch
	 * @covers \Wikia\Search\Config::setWikiMatch
	 * @covers \Wikia\Search\Config::getWikiMatch
	 * @covers \Wikia\Search\Config::hasMatch
	 * @covers \Wikia\Search\Config::getMatch
	 */
	public function testWikiMatching() {
		$mockWikiMatch = $this->getMockBuilder( 'Wikia\Search\Match\Wiki' )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$config = new \Wikia\Search\Config();

		$this->assertFalse(
				$config->hasWikiMatch(),
				'\Wikia\Search\Config should not have an wiki match by default.'
		);
		$this->assertNull(
				$config->getWikiMatch(),
				'\Wikia\Search\Config should return null when getting an uninitialized wiki match'
		);
		$this->assertEquals(
				$config,
				$config->setWikiMatch( $mockWikiMatch ),
				'\Wikia\Search\Config::setWikiMatch should provide a fluent interface.'
		);
		$this->assertEquals(
				$mockWikiMatch,
				$config->getWikiMatch(),
				'\Wikia\Search\Config::getWikiMatch should return the appropriate wiki match once set.'
		);
		$this->assertEquals(
				$mockWikiMatch,
				$config->getMatch(),
				'\Wikia\Search\Config::getMatch should return either article or wiki match.'
		);
		$this->assertTrue(
				$config->hasMatch()
		);
	}

	/**
	 * @covers \Wikia\Search\Config::isInterWiki
	 * @covers \Wikia\Search\Config::setIsInterWiki
	 * @covers \Wikia\Search\Config::getIsInterWiki
	 */
	public function testInterWiki() {
		$config	= new \Wikia\Search\Config;

		$this->assertFalse(
				$config->getIsInterWiki() || $config->getInterWiki() || $config->getIsInterWiki(),
				'Interwiki accessor methods should be false by default.'
		);
		$this->assertEquals(
				$config,
				$config->setIsInterWiki( true ),
				'WikiaSearch::setIsInterWiki should provide fluent interface.'
		);
		$this->assertTrue(
				$config->getIsInterWiki() && $config->getInterWiki() && $config->isInterWiki(),
				'Interwiki accessor methods should always have the same value, regardless of previous mutated state.'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::getTruncatedResultsNum
	 */
	public function testGetTruncatedResultsNum() {
		$config	= new \Wikia\Search\Config;

		$singleDigit = 9;

		$config->setResultsFound( $singleDigit );

		$this->assertEquals(
				$singleDigit,
				$config->getTruncatedResultsNum(),
				"We should not truncate a single digit result number value."
		);

		$doubleDigit = 26;

		$config->setResultsFound( $doubleDigit );

		$this->assertEquals(
				30,
				$config->getTruncatedResultsNum(),
				"We should round only for double digits."
		);

		$tripleDigit = 492;

		$config->setResultsFound( $tripleDigit );

		$this->assertEquals(
				500,
				$config->getTruncatedResultsNum(),
				"We should round to hundreds for triple digits."
		);

		$bigDigit = 55555;

		$config->setResultsFound( $bigDigit );

		$this->assertEquals(
				56000,
				$config->getTruncatedResultsNum(),
				"Larger digits should round to the nearest n-1 radix."
		);
		
		$interface = $this->interface->setMethods( array( 'formatNumber' ) )->getMock();
		$interface
		    ->expects( $this->once() )
		    ->method ( 'formatNumber' )
		    ->with   (56000)
		    ->will   ( $this->returnValue( '56,000' ) )
	    ;
		$this->setInterface( $config, $interface );
		$this->assertEquals(
				'56,000',
				$config->getTruncatedResultsNum( true )
		);
		
	}

	/**
	 * @covers \Wikia\Search\Config::getNumPages
	 */
	public function testGetNumPages() {
		$config = new \Wikia\Search\Config;

		$this->assertEquals(
				0,
				$config->getNumPages(),
				'Number of pages should default to zero.'
		);

		$numFound = 50;
		$config->setResultsFound( $numFound );

		$this->assertEquals(
				ceil( $numFound / \Wikia\Search\Config::RESULTS_PER_PAGE ),
				$config->getNumPages(),
				'Number of pages should be divided by default number of results per page by if no limit is set.'
		);

		$newLimit = 20;
		$config->setLimit( $newLimit );

		$this->assertEquals(
		        ceil( $numFound / $newLimit ),
		        $config->getNumPages(),
		        'Number of pages should be informed by limit set by user.'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::getCityId
	 * @covers \Wikia\Search\Config::setCityID
	 */
	public function testGetCityId() {
		$config = new Config;

		$mockCityId = 123;
		global $wgCityId;

		$config->setInterWiki( true );
		$this->assertEquals(
				0,
				$config->getCityId(),
				'City ID should be zero by default, but only when interwiki.'
		);

		$this->assertEquals(
				$wgCityId,
				$config->setIsInterWiki( false )->getCityId(),
				'City ID should default to wgCityId if the config is not interwiki.'
		);
		$this->assertEquals(
				456,
				$config->setCityID( 456 )->getCityId(),
				'If we set a different city ID, we should get a different city ID.'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::getSearchProfiles
	 */
	public function testGetSearchProfiles() {
		$config 			= new Config;
		$searchEngineMock	= $this->getMock( 'SearchEngine', array( 'defaultNamespaces', 'searchableNamespaces', 'namespacesAsText' ), array() );

		$searchEngineMock
			->staticExpects	( $this->any() )
			->method		( 'searchableNamespaces' )
			->will			( $this->returnValue( array( NS_MAIN, NS_TALK, NS_CATEGORY, NS_FILE, NS_USER ) ) )
		;
		$searchEngineMock
			->staticExpects	( $this->any() )
			->method		( 'defaultNamespaces' )
			->will			( $this->returnValue( array( NS_FILE, NS_CATEGORY ) ) )
		;
		$searchEngineMock
			->staticExpects	( $this->any() )
			->method		( 'namespacesAsText' )
			->will			( $this->returnValue( 'Article', 'Category' ) )
		;

		$this->mockClass( 'SearchEngine', $searchEngineMock );
		$this->mockApp();

		$profiles = $config->getSearchProfiles();
		$profileConstants = array( SEARCH_PROFILE_DEFAULT, SEARCH_PROFILE_IMAGES, SEARCH_PROFILE_USERS, SEARCH_PROFILE_ALL );
		foreach ( $profileConstants as $profile ) {
			$this->assertArrayHasKey(
					$profile,
					$profiles
			);
		}
	}

	/**
	 * @covers \Wikia\Search\Config::getActiveTab
	 */
	public function testGetActiveTab() {
		$config = $this->config->setMethods( array( 'getAdvanced', 'getNamespaces', 'getSearchProfiles' ) )->getMock();
		$config
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getAdvanced' )
		    ->will   ( $this->returnValue( true ) )
		;
		$this->assertEquals(
				'advanced',
				$config->getActiveTab()
		);
		$config
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getAdvanced' )
		    ->will   ( $this->returnValue( false ) )
		;
		$config
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getNamespaces' )
		    ->will   ( $this->returnValue( array( 0, 14 ) ) )
		;
		$config
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getSearchProfiles' )
		    ->will   ( $this->returnValue( array( 'default' => array( 'namespaces' => array( 0, 14 ) ), 'images' => array( 'namespaces' => array( 6 ) ) ) ) )
		;
		$this->assertEquals(
				'default',
				$config->getActiveTab()
		);
		$config
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getAdvanced' )
		    ->will   ( $this->returnValue( false ) )
		;
		$config
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getNamespaces' )
		    ->will   ( $this->returnValue( array( 0, 14, 123 ) ) )
		;
		$config
		    ->expects( $this->at( 2 ) )
		    ->method ( 'getSearchProfiles' )
		    ->will   ( $this->returnValue( array( 'default' => array( 'namespaces' => array( 0, 14 ) ), 'images' => array( 'namespaces' => array( 6 ) ) ) ) )
		;
		$this->assertEquals(
				'advanced',
				$config->getActiveTab()
		);
	}

	/**
	 * @covers \Wikia\Search\Config::setFilterQuery
	 * @covers \Wikia\Search\Config::setFilterQueries
	 * @covers \Wikia\Search\Config::getFilterQueries
	 * @covers \Wikia\Search\Config::setFilterQueryByCode
	 * @covers \Wikia\Search\Config::setFilterQueriesFromCodes
	 */
	public function testFilterQueryMethods() {
		$config	= new Config;
		$fqAttr	= new ReflectionProperty( '\Wikia\Search\Config', 'filterQueries' );
		$fqAttr->setAccessible( true );

		$this->assertFalse(
				$config->hasFilterQueries(),
				'\Wikia\Search\Config::hasFilterQueries should return false if no filter queries have been explicitly set.'
		);
		$this->assertEquals(
				$config,
				$config->setFilterQuery( 'foo:bar' ),
				'\Wikia\Search\Config::setFilterQuery should provide a fluent interface.'
		);
		$this->assertArrayHasKey(
				'fq1',
				$fqAttr->getValue( $config ),
				'\Wikia\Search\Config::setFilterQuery should assign an auto-incremented key when a key is not provided'
		);
		$this->assertArrayHasKey(
				'foo',
				$fqAttr->getValue( $config->setFilterQuery( 'bar:foo', 'foo' ) ),
				'\Wikia\Search\Config::setFilterQuery should store the filter query by the provided key'
		);
		$this->assertContains(
				array( 'key' => 'foo', 'query' => 'bar:foo' ),
				$fqAttr->getValue( $config ),
				'\Wikia\Search\Config::setFilterQuery should store the key and query as associative values in the value array per Solarium expected format'
		);
		$this->assertTrue(
				$config->hasFilterQueries(),
				'\Wikia\Search\Config::hasFilterQueries should return true if filter queries have been set'
		);
		$this->assertEquals(
				$fqAttr->getValue( $config ),
				$config->getFilterQueries(),
				'\Wikia\Search\Config::getFilterQueries should return the filterQueries attribute'
		);
		$this->assertEquals(
				$config,
				$config->setFilterQueries( array() ),
				'\Wikia\Search\Config::setFilterQueries should provide a fluent interface'
		);
		$this->assertEmpty(
				$fqAttr->getValue( $config ),
				'Passing an empty array to \Wikia\Search\Config::setFilterQueries should remove all filter queries.'
		);
		$this->assertEquals(
				0,
				\Wikia\Search\Config::$filterQueryIncrement,
				'\Wikia\Search\Config::setFilterQueries should reset the filter query increment'
		);

		$config->setFilterQueries( array(
				array(
						'query' => 'foo:bar',
						'key'   => 'baz',
				),
				'qux',
				true
		));
		$this->assertArrayHasKey(
				'baz',
				$fqAttr->getValue( $config ),
				'A properly formatted filter query array passed as a value to the array argument of '
				.' \Wikia\Search\Config::setFilterQueries should respect the previously set key'
		);
		$this->assertArrayHasKey(
				'fq1',
				$fqAttr->getValue( $config ),
				'Values in the argument array that are string-typed should receive '
				.' an auto-incremented key per \Wikia\Search\Config::setFilterQuery'
		);
		$this->assertEquals(
				2,
				count( $fqAttr->getValue( $config ) ),
				'Values in the array passed to \Wikia\Search\Config::setFilterQueries that are not properly formatted should be ignored'
		);

		// resetting
		$config->setFilterQueries( array() );

		$this->assertEquals(
				$config,
				$config->setFilterQueryByCode( 'is_video' ),
				'\Wikia\Search\Config::setFilterQueryByCode should provide a fluent interface'
		);
		$fqArray = $fqAttr->getValue( $config );
		$this->assertArrayHasKey(
				'is_video',
				$fqArray,
				'\Wikia\Search\Config::setFilterQueryByCode should set the code as the key for the new filter query'
		);

		$fcAttr = new ReflectionProperty( '\Wikia\Search\Config', 'filterCodes' );
		$fcAttr->setAccessible( true );
		$filterCodes = $fcAttr->getValue( $config );

		$this->assertEquals(
				array( 'key' => 'is_video', 'query' => $filterCodes['is_video'] ),
				$fqArray['is_video'],
				'\Wikia\Search\Config::setFilterQueryByCode should set exactly the query string that is '
				.'the value in \Wikia\Search\Config::filterCodes, keyed by the code provided'
		);

		$mockWikia = $this->getMock( 'Wikia', array( 'log' ) );
		$mockWikia
			->staticExpects	( $this->any() )
			->method		( 'log' )
		;
		$this->mockClass( 'Wikia', $mockWikia );
		$this->mockApp();
		// this satisfies the above expectation
		$config->setFilterQueryByCode( 'notacode' );

		$this->assertEquals(
				$config,
				$config->setFilterQueriesFromCodes( array( 'is_video', 'is_image' ) ),
				'\Wikia\Search\Config::setFilterQueriesFromCodes should provide a fluent interface'
		);
		$this->assertEquals(
				2,
				count( $fqAttr->getValue( $config ) ),
				'\Wikia\Search\Config::setFilterQueriesFromCode should function over each array '
				.' value provided as a code key to \Wikia\Search\Config::setFilterQueryByCode. '
				.' This test also proves a vital part of filter query data architecture: overwriting a key is allowed, '
				.' and warnings are not issues if you do so.'
		);
		// needs resetting to get the testing environment back in shape
		\Wikia\Search\Config::$filterQueryIncrement = 0;
	}

	/**
	 * @covers \Wikia\Search\Config::getRequestedFields
	 */
	public function testGetRequestedFields() {
		$config = new Config;

		$config->setRequestedFields( array( 'html' ) );

		$fields = $config->getRequestedFields();

		$this->assertContains(
				Utilities::field( 'html' ),
				$fields,
				'\Wikia\Search\Config::getRequestedFields() should perform language field transformation'
		);
		$this->assertContains(
				'id',
				$fields,
				'\Wikia\Search\Config::getRequestedFields() should always include an id'
		);
	}

	/**
	 * @covers \Wikia\Search\Config::getPublicFilterKeys
	 */
	public function testGetPublicFilterKeys() {
		$config = new Config;
		
		$config->setFilterQueryByCode( 'is_image' );
		
		$this->assertContains(
				'is_image',
				$config->getPublicFilterKeys(),
				'A public filter key registered in \Wikia\Search\Config::publicFilterKeys should be returned by \Wikia\Search\Config::getPublicFilterKeys'
		);
		
	}
	
	/**
	 * @covers \Wikia\Search\Config::setQueryField
	 */
	public function testSetQueryField() {
		$config = new \Wikia\Search\Config();
		$this->assertEquals(
				$config,
				$config->setQueryField( 'foo' )
		);
		$this->assertEquals(
				$config,
				$config->setQueryField( 'bar', 2 )
		);
		$queryFieldsToBoostsRefl = new ReflectionProperty( '\Wikia\Search\Config', 'queryFieldsToBoosts' );
		$queryFieldsToBoostsRefl->setAccessible( true );
		$fields = $queryFieldsToBoostsRefl->getValue( $config );
		$this->assertArrayHasKey(
				'foo',
				$fields
		);
		$this->assertArrayHasKey(
				'bar',
				$fields
		);
		$this->assertEquals(
				1,
				$fields['foo'],
				'\Wikia\Search\Config::setQueryField should set the boost value to 1 for a key by default'
		);
		$this->assertEquals(
				2,
				$fields['bar'],
				'\Wikia\Search\Config::setQueryField should set the boost value as passed in the second parameter'
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::setQueryFields
	 */
	public function testSetQueryFields() {
		$config = new \Wikia\Search\Config();
		$config->setQueryFields( array( 'foo', 'bar', 'baz' ) );
		$queryFieldsToBoostsRefl = new ReflectionProperty( '\Wikia\Search\Config', 'queryFieldsToBoosts' );
		$queryFieldsToBoostsRefl->setAccessible( true );
		$fields = $queryFieldsToBoostsRefl->getValue( $config );
		$this->assertEquals(
				array( 'foo' => 1, 'bar' => 1, 'baz' => 1 ),
				$fields,
				'If passed a flat array, \Wikia\Search\Config::addQueryFields should set the boost for each as 1'
		);
		$sentFields = array( 'foo' => 1, 'bar' => 2, 'baz' => 3 );
		$this->assertEquals(
				$config,
				$config->setQueryFields( $sentFields )
		);
		$fields = $queryFieldsToBoostsRefl->getValue( $config );
		$this->assertEquals(
				$sentFields,
				$fields,
				'If passed a flat array, \Wikia\Search\Config::addQueryFields should set the boost for each as 1'
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::addQueryFields
	 */
	public function testAddQueryFields() {
		$config = $this->config->setMethods( array( 'setQueryField' ) )->getMock();
		$config
		    ->expects( $this->at( 0 ) )
		    ->method ( 'setQueryField' )
		    ->with   ( 'foo', 1 )
		;
		$this->assertEquals(
				$config,
				$config->addQueryFields( array( 'foo' ) )
		);
		$config
		    ->expects( $this->at( 0 ) )
		    ->method ( 'setQueryField' )
		    ->with   ( 'foo', 5 )
		;
		$this->assertEquals(
				$config,
				$config->addQueryFields( array( 'foo' => 5 ) )
		); 
	}
	
	/**
	 * @covers \Wikia\Search\Config::getQueryFieldsToBoosts
	 */
	public function testGetQueryFieldsToBoosts() {
		$config = new \Wikia\Search\Config();
		$queryFieldsToBoostsRefl = new ReflectionProperty( '\Wikia\Search\Config', 'queryFieldsToBoosts' );
		$queryFieldsToBoostsRefl->setAccessible( true );
		$fields = $queryFieldsToBoostsRefl->getValue( $config );
		$this->assertEquals(
				$fields,
				$config->getQueryFieldsToBoosts(),
				'\Wikia\Search\Config::getQueryFieldsToBoosts should return the qf to boost array'
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::hasFilterQueries
	 */
	public function testHasFilterQueries() {
		$config = new \Wikia\Search\Config;
		$this->assertFalse(
				$config->hasFilterQueries()
		);
		$config->setFilterQuery( 'foo', 'bar' );
		$this->assertTrue(
				$config->hasFilterQueries()
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::importQueryFieldBoosts
	 */
	public function testImportQueryFieldBoosts() {
		$config = $this->getMockBuilder( '\Wikia\Search\Config' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'setQueryField' ) )
		               ->getMock();
		
		$interface = $this->getMockBuilder( '\Wikia\Search\MediaWikiService' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'getGlobalWithDefault' ) )
		                  ->getMock();
		
		$interface
		    ->expects( $this->once() )
		    ->method ( 'getGlobalWithDefault' )
		    ->with   ( 'SearchBoostFor_title', 5 )
		    ->will   ( $this->returnValue( 5 ) ) // value doesn't matter -- that's why we test this method separately 
		;
		$config
		    ->expects( $this->once() )
		    ->method ( 'setQueryField' )
		    ->with   ( 'title', 5 )
		;
		
		$fieldsrefl = new ReflectionProperty( '\Wikia\Search\Config', 'queryFieldsToBoosts' );
		$fieldsrefl->setAccessible( true );
		$fieldsrefl->setValue( $config, array( 'title' => 5 ) );
		
		$interfacerefl = new ReflectionProperty( '\Wikia\Search\Config', 'interface' );
		$interfacerefl->setAccessible(true );
		$interfacerefl->setValue( $config, $interface );
		
		$methodrefl = new ReflectionMethod( '\Wikia\Search\Config', 'importQueryFieldBoosts' );
		$methodrefl->setAccessible( true );
		$this->assertEquals(
				$config,
				$methodrefl->invoke( $config )
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::getQueryFields
	 */
	public function testGetQueryFields() {
		$config = new \Wikia\Search\Config();
		$fieldsToBoosts = $config->getQueryFieldsToBoosts();
		$this->assertEquals(
				array_keys( $fieldsToBoosts ),
				$config->getQueryFields()
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::setQuery
	 */
	public function testSetQuery() {
		$interface = $this->interface->setMethods( array( 'getNamespaceIdForString' ) )->getMock();
		$config = $this->config->setMethods( array( 'getNamespaces' ) )->getMock();
		$this->setInterface( $config, $interface );
		
		$query = 'Category:Foo';
		
		$interface
		    ->expects( $this->once() )
		    ->method ( 'getNamespaceIdForString' )
		    ->with   ( 'Category' )
		    ->will   ( $this->returnValue( 14 ) )
		;
		$config
		    ->expects( $this->once() )
		    ->method ( 'getNamespaces' )
		    ->will   ( $this->returnValue( array( 0 ) ) )
		;
		$this->assertEquals(
				$config,
				$config->setQuery( $query )
		);
		$paramsRefl = new ReflectionProperty( '\Wikia\Search\Config', 'params' );
		$paramsRefl->setAccessible( true );
		$params = $paramsRefl->getValue( $config );
		$this->assertEquals(
				$query,
				$params['originalQuery']
		);
		$this->assertEquals(
				'Foo',
				$params['query']
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::getNamespaces
	 */
	public function testGetNamespaces() {
		$config = $this->config->setMethods( null )->getMock();
		$interface = $this->config->setMethods( array( 'getDefaultNamespacesFromSearchEngine' ) )->getMock();
		$this->setInterface( $config, $interface );
		$config->setQueryNamespace( 123 );
		$interface
		    ->expects( $this->once() )
		    ->method ( 'getDefaultNamespacesFromSearchEngine' )
		    ->will   ( $this->returnValue( array( 0, 14 ) ) )
	    ;
		$this->assertEquals(
				array( 0, 14, 123 ),
				$config->getNamespaces()
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::getQueryNoQuotes
	 */
	public function testGetQueryNoQuotes() {
		$query = '"foo" and: \'bar\'';
		$config = new \Wikia\Search\Config;
		$this->assertEquals(
				"foo and\\: bar",
				$config->setQuery( $query )->getQueryNoQuotes()
		);
		$query = '"foo" and:\'bar\'';
		$this->assertEquals(
				"foo and:bar",
				$config->setQuery( $query )->getQueryNoQuotes( true )
		);
	}
	
	/**
	 * @covers \Wikia\Search\Config::getQuery
	 */
	public function testGetQuery() {
		$config = new \Wikia\Search\Config;
		$this->assertFalse(
				$config->getQuery()
		);
		$query = "foo and: bar & baz";
		$config->setQuery( $query );
		$this->assertEquals(
				"foo and\\: bar & baz",
				$config->getQuery()
		);
		$this->assertEquals(
				"foo and: bar &amp; baz",
				$config->getQuery( \Wikia\Search\Config::QUERY_ENCODED )
		);
		$this->assertEquals(
				$query,
				$config->getQuery( \Wikia\Search\Config::QUERY_RAW )
		);
		$config->setQuery( 'foo wiki' )->setIsInterWiki( true );
		$this->assertEquals(
				'foo',
				$config->getQuery()
		);
	}
}