<?php

class I18n_Rewrite_Rules {

	private function __construct(){}

	public static function early_init(): void {

		add_filter( 'rewrite_rules_array', [ __CLASS__, 'rewrite_rules_array_correction' ], PHP_INT_MAX );

		add_action( 'init', [ __CLASS__, 'init' ], 0 );
	}

	public static function init(): void {

		// add rw tag + query var
		add_rewrite_tag( '%lang%', '('. Langs()->langs_regex .')' );

		add_filter( 'mod_rewrite_rules', [ __CLASS__, 'fix_home_url_in_mod_rewrite_rules' ] );
	}

	/**
	 * Remove `en/` from `RewriteBase /en/` or RewriteRule . `/en/index.php [L]`.
	 */
	public static function fix_home_url_in_mod_rewrite_rules( string $rules ): string {
		return preg_replace( sprintf( '~/(%s)/~', Langs()->langs_regex ), '/', $rules );
	}

	/**
	 * Overwrites all rewrite rules.
	 */
	public static function rewrite_rules_array_correction( array $rules ): array {

		$new_rules = [];

		// add prefix for all rules
		foreach( $rules as $rule => $query ){

			if( self::is_skip_rewrite_rules_correction( $rule, $query ) ){
				$new_rules[ $rule ] = $query;
				continue;
			}

			// add prefix
			$query = preg_replace_callback( '~matches\[(\d+)\]~', static function( $mm ) {
				return 'matches[' . ( (int) $mm[1] + 1 ) . ']';
			}, $query );

			$new_rules[ '(' . Langs()->langs_regex . ")/$rule" ] = $query;
		}

		// home page
		if( i18n_opt()->home_page_add_prefix ){
			//$new_rules = [ '^(' . Langs()->langs_regex . ')/?$' => 'index.php?lang=$matches[1]' ] + $new_rules;
			$new_rules = [ '^(' . Langs()->langs_regex . ')/?$' => 'index.php' ] + $new_rules;
		}

		return apply_filters( 'i18n__rewrite_rules_array_correction', $new_rules );
	}

	protected static function is_skip_rewrite_rules_correction( string $rule, string $query ): bool {

		if( preg_match( '~^(robots|favicon)~', $rule ) ){
			return true;
		}

		// TODO consider proccess all such rules separatelly
		if( preg_match( '~^\^~', $rule ) ){
			return true;
		}

		// home page paginations
		if( ! i18n_opt()->home_page_add_prefix && preg_match( '~^page/~', $rule ) ){
			return true;
		}

		return apply_filters( 'i18n__is_skip_rewrite_rules_correction', false, $rule, $query );
	}

}



