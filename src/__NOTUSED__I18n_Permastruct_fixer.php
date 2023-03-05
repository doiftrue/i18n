<?php

class __NOTUSED__I18n_Permastruct_fixer {

	public static function early_init(): void {

		add_action( ( is_admin() ? 'setup_theme' : 'wp' ), [ __CLASS__, '_do_parse_request' ], 0 );
	}

	public static function _do_parse_request(): void {

		self::correct_page_permastruct();
		self::correct_author_permastruct();
		self::correct_extra_permastructs();
	}

	protected static function correct_page_permastruct(): void {
		global $wp_rewrite;

		if( ! $wp_rewrite->permalink_structure ){
			$wp_rewrite->page_structure = '';
			return;
		}

		$wp_rewrite->page_structure = I18n_Permalink_Fixer::add_lang_tag_prefix( $wp_rewrite->root . '%pagename%' );
	}

	protected static function correct_author_permastruct(): void {
		global $wp_rewrite;

		if( ! $wp_rewrite->permalink_structure ){
			$wp_rewrite->author_structure = '';
			return;
		}

		$wp_rewrite->author_structure = I18n_Permalink_Fixer::add_lang_tag_prefix( $wp_rewrite->front . $wp_rewrite->author_base . '/%author%' );
	}

	protected static function correct_extra_permastructs(): void {
		global $wp_rewrite;

		foreach( $wp_rewrite->extra_permastructs as & $val ){
			$val['struct'] = I18n_Permalink_Fixer::add_lang_tag_prefix( $val['struct'] );
		}
		unset( $val );
	}

}
