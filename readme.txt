=== Meta Box ===
Stable tag:        trunk
Tested up to:      6.0
Contributors:      Kama
License:           GPLv2
Tags: multilang

Script for Multi-language site.


== Description ==

Adds `/ru`, `/en` ... prefix to URL. Save current lang to cookies.


### Usage

Install this code as MU-Plugin. And init the plugin like this:

```
add_filter( 'i18n__options', function( $opts ){

	$opts = array_merge( $opts, [
		'default_lang' => 'ru',
		'active_langs' => [ 'ru' ],
		'process_home_url' => false,
	] );

	return $opts;
} );

require_once __DIR__ . '/i18n/i18n.php';
```



== Installation ==


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

### 1.2.0
- Refactor improvements.

### 1.1.2
- Improve for `_get_meta_i18n()`.

### 1.0.1
- NEW: Дополнительный пример в `example_hooks.php`.
- NEW: Параметр `$new_lang` в `uri_replace_lang_prefix()` стал необязательный.

### 1.0
- NEW: Sub-directories support: `https://dom.com/subdir/en`.

