# SilverStripe Chargify Module

## Installation

There are several steps to setting up the Chargify module, mainly to do with
linking it to your chargify account.

*   Place the "chargify" directory in the root of your SilverStripe installation.
*   Visit example.com/dev/build in your site to rebuild the database.
*   If you visit the site config panel now, you should have a new tab for
    Chargify configuration. Go to this tab and fill out the following details:
    *   "Chargify Domain" - This is the subdomain your Chargify site runs on.
        For example, if your site is example.chargify.com, your chargify domain
        is "example".
    *   "Chargify API Key" - This is your chargify API key, and can be accessed
        by going to your Chargify settings panel and clicking "API Access".
    *   "Chargify Site Shared Key" - This can be accessed by going to your Chargify
        site control panel, clicking on the settings tab and going to the
        "Hosted Page URLs" section.
    *   "Chargify Currency" - The currency your site runs in, e.g. AUD or USD.
    *   _Note_: Any of these settings can also be defined using constants in your
        site's config file.
*   The final step is to set up web hook ingration. The site config panel
    has the URL you need to register with Chargify. Go to your Chargify site
    control panel, go to the settings section and then Webhooks. Place the
    webhook URL there, and check at least the "Signup Success" and "Subscription
    State Change" checkboxes.

## Configuration

Once Chargify is up and running, you can now link the products to security
groups. You do this by selecting a Group inside the Security admin area, going
to the Chargify tab and selecting a product from the dropdown. Now whenever a
user subscribes to that product they will be automatically added to the group,
and removed when their subscription expires.

The next step is to create a page where logged in users can manage their
subscription. To do this create a "Chargify Subscription Page" in the
Pages area, and select the subscriptions that a user can register for via that
page.

## Syncing

If the Chargify and SilverStripe datbases become out of sync, you can run the
SyncChargifyMembersTask task, and then after than run the
SyncChargifySubscriptionsTask build task.