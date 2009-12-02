<?php

/**
 * @package MediaWiki
 * @author Krzysztof Krzyżaniak <eloy@wikia.com> for Wikia.com
 * @copyright (C) 2007, Wikia Inc.
 * @licence GNU General Public Licence 2.0 or later
 * @version: $Id: Classes.php 6127 2007-10-11 11:10:32Z eloy $
 */

/*
 * hooks
 */
$wgHooks['SpecialRecentChangesLinks'][] = "Wikia::addRecentChangesLinks";
$wgHooks['SpecialRecentChangesQuery'][] = "Wikia::makeRecentChangesQuery";

/**
 * This class have only static methods so they can be used anywhere
 *
 */

class Wikia {

	private static $vars = array();

	public static function setVar($key, $value) {
		Wikia::$vars[$key] = $value;
	}

	public static function getVar($key) {
		return isset(Wikia::$vars[$key]) ? Wikia::$vars[$key] : null;
	}

	public static function isVarSet($key) {
		return isset(Wikia::$vars[$key]);
	}

	public static function unsetVar($key) {
		unset(Wikia::$vars[$key]);
	}

	/**
	 * @author inez@wikia.com
	 */
	function getThemesOfSkin($skinname = 'quartz') {
		global $wgSkinTheme;

		$themes = array();

		if(isset($wgSkinTheme) && is_array($wgSkinTheme) && isset($wgSkinTheme[$skinname])) {
			foreach($wgSkinTheme[$skinname] as $val) {
				if( $val != 'custom' && ! (isset($wgSkipThemes) && is_array($wgSkipThemes) && isset($wgSkipThemes[$skinname]) && in_array($wgSkipThemes[$skinname],$val))) {
					$themes[] = $val;
				}
			}
		}

		return $themes;
	}



    /**
     * successbox
     *
     * @access public
     * @static
     * @author eloy@wikia
     *
     * @param string $what message for user
     *
     * @return string composed HTML/XML code
     */
    static public function successbox($what) {
        return Xml::element( "div", array(
				"class"=> "successbox", "style" => "margin: 0;margin-bottom: 1em;"
			), $what)
			. Xml::element("br", array( "style" => "clear: both;"));
    }

    /**
     * errorbox
     *
     * return div with error message
     *
     * @access public
     * @static
     * @author eloy@wikia
     *
     * @param string $what message for user
     *
     * @return string composed HTML/XML code
     */
    static public function errorbox($what) {
        return Xml::element( "div", array(
				"class"=> "errorbox", "style" => "margin: 0;margin-bottom: 1em;"
			), $what )
			. Xml::element("br", array( "style" => "clear: both;"));
    }

    /**
     * errormsg
     *
     * return span for error message
     *
     * @access public
     * @static
     * @author eloy@wikia
     *
     * @param string $what message for user
     *
     * @return string composed HTML/XML code
     */
    static public function errormsg($what)
    {
        return Xml::element("span", array( "style"=> "color: #fe0000; font-weight: bold;"), $what);
    }

    /**
     * link
     *
     * return XML/HTML code with link
     *
     * @access public
     * @static
     * @author eloy@wikia
     *
     * @param string $url: message for user
     * @param string $title: link body
     * @param mixed $attribs default null: attribbs for tag
     *
     * @todo safety checking
     *
     * @return string composed HTML/XML code
     */
    static public function link($url, $title, $attribs = null )
    {
        return XML::element("a", array( "href"=> $url), $title);
    }

    /**
     * successmsg
     *
     * return span for success message
     *
     * @access public
     * @static
     * @author eloy@wikia
     *
     * @param string $what message for user
     *
     * @return string composed HTML/XML code
     */
    static public function successmsg($what)
    {
        return Xml::element("span", array( "style"=> "color: darkgreen; font-weight: bold;"), $what);
    }

    /**
     * fixDomainName
     *
     * It takes domain name as param, then checks if it contains more than one
     * dot, then depending on that information adds .wikia.com domain or not.
     * Additionally it lowercase name
     *
     * @access public
     * @static
     * @author eloy@wikia
     *
     * @param string $name Domain Name
     * @param string $language default null - choosen language
     *
     * @return string fixed domain name
     */
    static public function fixDomainName( $name, $language = null )
    {
        if (empty( $name )) {
            return $name;
        }

        $name = strtolower($name);

        if ( !is_null($language) && $language != "en" ) {
            $name = $language.".".$name;
        }

        $aParts = explode(".", trim($name));

        if (is_array( $aParts )) {
            if (count( $aParts ) <= 2) {
                $name = $name.".wikia.com";
            }
        }
        return $name;
    }


    /**
     * addCredits
     *
     * add html with credits to xml dump
     *
     * @access public
     * @static
     * @author eloy@wikia
     * @author emil@wikia
     *
     * @param object $row: Database Row with page object
     *
     * @return string: HTML string with credits line
     */
    static public function addCredits( $row )
    {
		global $wgIwPrefix, $wgExternalSharedDB, $wgAddFromLink;

        $text = "";

		if ( $wgAddFromLink && ($row->page_namespace != 8) && ($row->page_namespace != 10) ) {
			if (isset($wgIwPrefix)){
				$text .= '<div id="wikia-credits"><br /><br /><small>' . wfMsg('tagline-url-interwiki',$wgIwPrefix) . '</small></div>';
			}
            elseif (isset($wgExternalSharedDB)){
				global $wgServer,$wgArticlePath,$wgSitename;
				$dbr = wfGetDB( DB_SLAVE, array(), $wgExternalSharedDB );
				$oRow = $dbr->selectRow(
                    'interwiki',
                    array( 'iw_prefix' ),
                    array( 'iw_url' => $wgServer.$wgArticlePath ),
                    __METHOD__
                );
				if ($oRow) {
					$text .= '<div id="wikia-credits"><br /><br /><small>' . wfMsg('tagline-url-interwiki',$oRow->iw_prefix) . '</small></div>';
				}
				else {
					$text .= '<div id="wikia-credits"><br /><br /><small>' . wfMsg('tagline-url') . '</small></div>';
				}
			}
            else {
				$text .= '<div id="wikia-credits"><br /><br /><small>' . wfMsg('tagline-url') . '</small></div>';
			}
		}

        return $text;
    }

    /**
     * ImageProgress
     *
     * hmtl code with progress image
     *
     * @access public
     * @static
     * @author eloy@wikia
     *
     * @param string $type: type of progress image, default bar
     *
     * @return string: HTML string with progress image
     */
    static public function ImageProgress( $type = "bar" )
    {
        $sImagesCommonPath = wfGetImagesCommon();
        switch ( $type ) {
            default:
                return wfElement( 'img', array(
                    "src"    => "{$sImagesCommonPath}/skins/quartz/images/progress_bar.gif",
                    "width"  => 100,
                    "height" => 9,
                    "alt"    => ".....",
                    "border" => 0
                ));
        }
    }

    /**
     * json_encode
     *
     * json encoding function
     *
     * @access public
     * @static
     * author eloy@wikia
     *
     * @param mixed $what: structure for encoding
     *
     * @return string: encoded string
     */
    static public function json_encode( $what )
    {
        wfProfileIn( __METHOD__ );

        $sResponse = "";

        if (!function_exists('json_encode'))  { #--- php < 5.2
            $oJson = new Services_JSON();
            $sResponse = $oJson->encode( $what );
        }
        else {
            $sResponse = json_encode( $what );
        }
        wfProfileOut( __METHOD__ );

        return $sResponse;
    }

    /**
     * json_decode
     *
     * json decoding function
     *
     * @access public
     * @static
     * author eloy@wikia
     *
     * @param string $what: json string for decoding
     * @param boolean $assoc: returned object will be converted into associative array
     *
     * @return mixed: decoded structure
     */
    static public function json_decode( $what, $assoc = false )
    {
		wfProfileIn( __METHOD__ );

		$mResponse = null;

		if (!function_exists('json_decode'))  { #--- php < 5.2
		    $oJson = new Services_JSON();
		    $mResponse = $oJson->decode( $what );
		}
		else {
		    $mResponse = json_decode( $what, $assoc );
		}

		wfProfileOut( __METHOD__ );

		return $mResponse;
    }

    /**
     * binphp
     *
     * full path to php binary used in background scripts. wikia uses
     * /opt/wikia/php/bin/php, fp & localhost could use others. Write here Your
     * additional conditions to check
     *
	 * @author Krzysztof Krzyżaniak <eloy@wikia-inc.com>
     * @access public
     * @static
     *
     * @return string: path to php binary
     */
    static public function binphp() {
		wfProfileIn( __METHOD__ );

        $path = ( file_exists( "/opt/wikia/php/bin/php" )
            && is_executable( "/opt/wikia/php/bin/php" ) )
            ? "/opt/wikia/php/bin/php"
            : "/usr/bin/php";

		wfProfileOut( __METHOD__ );

        return $path;
    }

	/**
	 * simple logger which log message to STDERR if devel environment is set
	 *
	 * @example Wikia::log( __METHOD__, "1", "checking" );
	 * @author Krzysztof Krzyżaniak <eloy@wikia-inc.com>
	 *
	 * @param String $method  -- use __METHOD__
	 * @param String $sub     -- if more in one method default false
	 * @param String $message -- additional message default false
	 * @param Boolean $always -- skip checking of $wgErrorLog and write log (or not)
	 *
	 */
	static public function log( $method, $sub = false, $message = false, $always = false ) {
	  global $wgDevelEnvironment, $wgErrorLog, $wgDBname, $wgCityId, $wgCommandLineMode;

		$method = $sub ? $method . "-" . $sub : $method;
		if( $wgDevelEnvironment || $wgErrorLog || $always ) {
			error_log( $method . ":{$wgDBname}/{$wgCityId}:" . $message );
		}
		/**
		 * commandline = echo
		 */
		if( $wgCommandLineMode ) {
			printf( "%s %s:%s/%d: %s\n", wfTimestamp( TS_DB, time() ), $method, $wgDBname, $wgCityId, $message );
		}
		/**
		 * and use wfDebug as well
		 */
		wfDebug( $method . ": " . $message . "\n" );
	}


	/**
	 * get staff person responsible for language
	 *
	 * @author Krzysztof Krzyżaniak <eloy@wikia-inc.com>
	 * @access public
	 * @static
	 *
	 * @param String $lang  -- language code
	 *
	 * @return User -- instance of user object
	 */
	static public function staffForLang( $langCode ) {
		wfProfileIn( __METHOD__ );

		$staffSigs = wfMsgExt('staffsigs', array('language'=>'en')); // fzy, rt#32053
		$staffUser = false;
		if( !empty( $staffSigs ) ) {
			$lines = explode("\n", $staffSigs);

			foreach ( $lines as $line ) {
				if( strpos( $line, '* ' ) === 0 ) {
					$sectLangCode = trim( $line, '* ' );
					continue;
				}
				if( ( strpos( $line, '* ' ) == 1 ) && ( $langCode === $sectLangCode ) ) {
					$user = trim( $line, '** ' );
					$staffUser = User::newFromName( $user );
					$staffUser->load();
					break;
				}
			}
		}

		/**
		 * fallback to Angela
		 */
		if( ! $staffUser ) {
			$staffUser = User::newFromName( "Angela" );
			$staffUser->load();
		}

		wfProfileOut( __METHOD__ );
		return $staffUser;
	}

	/**
	 * View any string as a hexdump.
	 *
	 * This is most commonly used to view binary data from streams
	 * or sockets while debugging, but can be used to view any string
	 * with non-viewable characters.
	 *
	 * @version     1.3.2
	 * @author      Aidan Lister <aidan@php.net>
	 * @author      Peter Waller <iridum@php.net>
	 * @link        http://aidanlister.com/repos/v/function.hexdump.php
	 * @param       string  $data        The string to be dumped
	 * @param       bool    $htmloutput  Set to false for non-HTML output
	 * @param       bool    $uppercase   Set to true for uppercase hex
	 * @param       bool    $return      Set to true to return the dump
	 */
	static public function hex($data, $htmloutput = true, $uppercase = false, $return = false) {
		// Init
		$hexi   = '';
		$ascii  = '';
		$dump   = ($htmloutput === true) ? '<pre>' : '';
		$offset = 0;
		$len    = strlen($data);

		// Upper or lower case hexadecimal
		$x = ($uppercase === false) ? 'x' : 'X';

		// Iterate string
		for ($i = $j = 0; $i < $len; $i++) {
			// Convert to hexidecimal
			$hexi .= sprintf("%02$x ", ord($data[$i]));

			// Replace non-viewable bytes with '.'
			if (ord($data[$i]) >= 32) {
				$ascii .= ($htmloutput === true) ? htmlentities($data[$i]) : $data[$i];
			} else {
				$ascii .= '.';
			}

			// Add extra column spacing
			if ($j === 7) {
				$hexi  .= ' ';
				$ascii .= ' ';
			}

			// Add row
			if (++$j === 16 || $i === $len - 1) {
				// Join the hexi / ascii output
				$dump .= sprintf("%04$x  %-49s  %s", $offset, $hexi, $ascii);

				// Reset vars
				$hexi   = $ascii = '';
				$offset += 16;
				$j      = 0;

				// Add newline
				if ($i !== $len - 1) {
					$dump .= "\n";
				}
			}
		}

		// Finish dump
		$dump .= ($htmloutput === true) ? '</pre>' : '';
		$dump .= "\n";

		// Output method
		if ($return === false) {
			echo $dump;
		} else {
			return $dump;
		}
	}

	/**
	 * Represents a write lock on the key, based in MessageCache::lock
	 */
	static public function lock( $key ) {
		global $wgMemc;
		$timeout = 10;
		$lockKey = wfMemcKey( $key, "lock" );
		for ($i=0; $i < $timeout && !$wgMemc->add( $lockKey, 1, $timeout ); $i++ ) {
			sleep(1);
		}

		return $i >= $timeout;
	}

	/**
	 * Unlock a write lock on the key, based in MessageCache::unlock
	 */
	static public function unlock($key) {
		global $wgMemc;
		$lockKey = wfMemcKey( $key, "lock" );
		return $wgMemc->delete( $lockKey );
	}


	/**
	 * A function for making time periods readable
	 *
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     2.0.0
	 * @link        http://aidanlister.com/2004/04/making-time-periods-readable/
	 * @param       int     number of seconds elapsed
	 * @param       string  which time periods to display
	 * @param       bool    whether to show zero time periods
	 */
	static public function timeDuration( $seconds, $use = null, $zeros = false ) {
		$seconds = ceil( $seconds );
		if( $seconds == 0 || $seconds == 1 ) {
			$str = "{$seconds} sec";
		}
		else {

			// Define time periods
			$periods = array (
				'years'     => 31556926,
				'Months'    => 2629743,
				'weeks'     => 604800,
				'days'      => 86400,
				'hr'        => 3600,
				'min'       => 60,
				'sec'       => 1
				);

			// Break into periods
			$seconds = (float) $seconds;
			foreach ($periods as $period => $value) {
				if ($use && strpos($use, $period[0]) === false) {
					continue;
				}
				$count = floor($seconds / $value);
				if ($count == 0 && !$zeros) {
					continue;
				}
				$segments[strtolower($period)] = $count;
				$seconds = $seconds % $value;
			}

			// Build the string
			foreach ($segments as $key => $value) {
				$segment = $value . ' ' . $key;
				$array[] = $segment;
			}

			$str = implode(', ', $array);
		}
		return $str;
	}
	
	/**
	 * parse additional option links in RC
	 *
	 * @author      Piotr Molski <moli@wikia-inc.com>
	 * @version     1.0.0
	 * @param       RC		 RC - RC object	
	 * @param       Array    links
	 * @param       Array    options - default options
	 * @param       Array    nondefaults - request options
	 */
	static public function addRecentChangesLinks( $RC, &$links, &$defaults, &$nondefaults ) {
		global $wgRequest;
		
		$showhide = array( wfMsg( 'show' ), wfMsg( 'hide' ) );
		if ( !is_array($links) ) {
			$links = array();
		}
		if ( !isset($defaults['hidelogs']) ) {
			$defaults['hidelogs'] = "";
		}
		$nondefaults['hidelogs'] = $wgRequest->getVal( 'hidelogs', 0 );

		$options = $nondefaults + $defaults;
	
		$hidelogslink = $RC->makeOptionsLink( $showhide[1-$options['hidelogs']],
			array( 'hidelogs' => 1-$options['hidelogs'] ), $nondefaults);
		$links[] = wfMsgHtml( 'rcshowhidelogs', $hidelogslink );

		return true;
	}
	
	/**
	 * make query with additional options
	 *
	 * @author      Piotr Molski <moli@wikia-inc.com>
	 * @version     1.0.0
	 * @param       Array    $conds - where conditions in SQL query
	 * @param       Array    $tables - tables used in SQL query
	 * @param       Array    $join_conds - joins in SQL query
	 * @param       FormOptions    $opts - selected options
	 */
	static public function makeRecentChangesQuery ( &$conds, &$tables, &$join_conds, $opts ) {
		global $wgRequest;
		
		if ( $wgRequest->getVal( 'hidelogs', 0 ) > 0 ) {
			$conds[] = 'rc_logid = 0';
		} 
		return true;
	}
	
}
