=== Kitaris Environment Indicator ===
Contributors: kitaris, dannorthern
Tags: environment, staging, development, admin bar, indicator
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See at a glance whether you are on local, development, staging or production, right in the admin bar.

== Description ==

Kitaris Environment Indicator adds a small coloured badge to the admin bar showing which environment the site is running as. No more editing production when you meant to edit staging.

* Reads the environment WordPress already knows about (`WP_ENVIRONMENT_TYPE`), so there is nothing to configure.
* A distinct colour for each environment: local, development, staging and production.
* Change any label or dot colour under Settings → Environment Indicator.
* Shown to administrators only by default.
* Lightweight: no front-end assets for visitors, no database tables, one option.

= Set the environment =

Add this to `wp-config.php` (or set the `WP_ENVIRONMENT_TYPE` environment variable):

`define( 'WP_ENVIRONMENT_TYPE', 'staging' );`

Accepted values are `local`, `development`, `staging` and `production`. WordPress treats a site with no setting as `production`.

= For developers =

Who sees the badge is filterable:

`add_filter( 'siratik_environment_indicator_show', function ( $show, $environment ) { return current_user_can( 'edit_posts' ); }, 10, 2 );`

== Frequently Asked Questions ==

= The badge does not appear. =

It is only shown to users who can manage options, and only while the admin bar is showing.

= Does it add anything for visitors? =

No. The badge and its styles are only output for users allowed to see it.

== Screenshots ==

1. The environment badge in the admin bar.
2. Settings → Environment Indicator.

== Changelog ==

= 0.1.0 =
* First release.
