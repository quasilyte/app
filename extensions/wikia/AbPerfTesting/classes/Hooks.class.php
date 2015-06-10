<?php

namespace Wikia\AbPerfTesting;

class Hooks {

	/**
	 * Initialize performance experiments when MediaWiki starts the engine
	 */
	static function onSetup() {
		global $wgABPerfTestingExperiments;

		// loop through all registered experiments and run those matching criteria
		foreach( $wgABPerfTestingExperiments as $name => $experiment ) {
			if ( Experiment::isEnabled( $experiment ) ) {
				wfDebug( sprintf("%s: starting '%s' experiment using %s class with %s params\n",
					__METHOD__, $name, $experiment['handler'], json_encode( $experiment['params'] ) ) );

				$reflector = new \ReflectionClass($experiment['handler']);
				$reflector->newInstanceArgs($experiment['params']);

				// mark a transaction with an experiment name
				\Transaction::getInstance()->set(\Transaction::PARAM_AB_PERFORMANCE_TEST, $name);

				// leave now, we handle only a single experiment at a time now
				return;
			}
		}
	}
}
