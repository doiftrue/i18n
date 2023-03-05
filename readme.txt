=== i18n ===
Stable tag:        trunk
Tested up to:      6.0
Contributors:      Kama
License:           GPLv2
Tags: multilang

Script for Multi-language site.


== Description ==

Adds `/ru`, `/en` ... prefix to URL. Save current lang to cookies OR `user_lang` metadata.


### Usage

Install this plugin as mu-plugin and init it like this:

```php
add_filter( 'i18n__options', function( $opts ){

	$opts = array_merge( $opts, [
		'default_lang' => 'ru',
		'active_langs' => [ 'ru', 'de', 'fr', 'ua' ],
		'home_page_add_prefix' => false,
	] );

	return $opts;
} );

require_once __DIR__ . '/i18n/i18n.php';
```



== FAQ ==



== Changelog ==

### 1.3.0
* The code was refactored: improved, simplified.
* `wp-sitemap.xml` support and bugfixes.
* Renamed `process_home_url` to `home_page_add_prefix`.
* Many handy hooks was added.

### 1.2.5
* Minor refactoring & code review. Code comments translated to en.

### 1.2.1
* Ability to set options via hook `i18n__options` before plugin init.
* new option `process_home_url`.
* Some Refactor.

### 1.2.0
* Refactor improvements.

### 1.1.2
* Improve for `_get_meta_i18n()`.

### 1.0.1
* NEW: Дополнительный пример в `example_hooks.php`.
* NEW: Параметр `$new_lang` в `uri_replace_lang_prefix()` стал необязательный.

### 1.0
* NEW: Sub-directories support: `https://dom.com/subdir/en`.

