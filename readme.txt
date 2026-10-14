=== Kitaris Environment Indicator ===
Contributors: kitaris, dannorthern
Tags: environment, staging, development, admin bar, indicator
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See at a glance whether you are on local, development, staging or production, right in the admin bar.

== Description ==

Kitaris Environment Indicator adds a small colored badge to the admin bar showing which environment the site is running as. No more editing production when you meant to edit staging.

* Reads the environment WordPress already knows about (`WP_ENVIRONMENT_TYPE`), so there is nothing to configure.
* A distinct color for each environment: local, development, staging and production.
* Change any label or dot color under Settings → Environment Indicator, or set them in code.
* Need an environment WordPress has no type for, like QA or Client Review? Give it a custom name.
* Shown to anyone who can edit content (contributors and up) by default.
* Lightweight: no front-end assets for visitors, no database tables, one option.

= Set the environment =

Add this to `wp-config.php` (or set the `WP_ENVIRONMENT_TYPE` environment variable):

`define( 'WP_ENVIRONMENT_TYPE', 'staging' );`

Accepted values are `local`, `development`, `staging` and `production`. WordPress treats a site with no setting as `production`.

= Custom environments =

WordPress itself only knows the four types above, and treats any other value as `production`. To show another name, keep `WP_ENVIRONMENT_TYPE` set to the type WordPress should behave as, and add a custom name:

`define( 'KITARIS_ENVIRONMENT_INDICATOR_CUSTOM', 'qa' );`

Or set it with a filter, which wins over the constant:

`add_filter( 'kitaris_environment_indicator_custom', function () { return 'qa'; } );`

It is shown with the Custom color and labeled with the name ("client-review" shows as "Client Review"), unless you set a Custom label in the settings.

= For developers =

Set labels and colors in code. The filter receives the saved settings, keyed by `local`, `development`, `staging`, `production` and `custom`, and what it returns wins over the settings page:

`add_filter( 'kitaris_environment_indicator_config', function ( $config ) { $config['staging'] = [ 'text' => 'QA', 'color' => '#0891b2' ]; return $config; } );`

Remove the settings page (saved settings still apply):

`add_filter( 'kitaris_environment_indicator_settings_page', '__return_false' );`

Who sees the badge is filterable; for example, administrators only:

`add_filter( 'kitaris_environment_indicator_show', function ( $show, $environment ) { return current_user_can( 'manage_options' ); }, 10, 2 );`

Return `false` from the same filter to hide the badge.

== Frequently Asked Questions ==

= The badge does not appear. =

It is only shown to users who can edit posts (contributors, authors, editors and administrators), and only while the admin bar is showing.

= Does it add anything for visitors? =

No. The badge and its styles are only output for users allowed to see it.

== Screenshots ==

1. The environment badge in the admin bar.
2. Settings → Environment Indicator.

== Changelog ==
