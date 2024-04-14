=== Mormat Scheduler ===
Contributors: mormat
Donate link: https://www.buymeacoffee.com/mormat
Tags: shortcode, scheduler, agenda, planner, calendar
Requires at least: 6.4
Tested up to:      6.5
Requires PHP:      7.2
License: GPLv2 or later
Stable tag: 0.1.2

Add a Google-like scheduler to your WordPress site

== Description ==

**[mormat_scheduler]** is a shortcode that render an event scheduler component. The purpose was to provide an alternative to Google's scheduler.

The scheduler offers three calendar views : `day`, `week` or `month`. 

Events can be created, edited and deleted directly from the scheduler (which can be done by logged in users only).

Drag and drop is also available.

= Shortcode parameters =
- **height** : Define the height of the scheduler. It must be a css compatible value (for instance "640px" or "70vh"). 
- **initial_date** : A date from which the scheduler will start displaying the events. If not provided, the current date system will be used. A string formatted as "yyyy-mm-dd" can be provided or any value compatible with the `Date` javascript object. 
- **default_view**: The default view used for displaying events. Allowed values are "day", "week" or "month". Default value is "week"
- **events_namespace**: Useful only if you need to display and manage a specific set of events. The default value is "".
- **locale** : i18n locale used for displaying dates (For instances `en`, `en_US` or `en_GB`). Default value is the website's current locale.

== Frequently Asked Questions ==
 
= Can I ask you to change something for me? =
 
Yes absolutely.
 
== Upgrade Notice ==

na


== Changelog ==

= 0.1.2 =
i18n

= 0.1.1 =
Day names are shortened in month view

= 0.1.0 =
Scheduler is rendered with shortcode

= 0.0.1 =
Initial release.

== Screenshots ==
1. month view
2. week view
