# Site-Uptime-Notifier
A small script which will notify you on Slack if your site(s) go down.

You can find really simple instructions below on how to setup your first Slack App and enable incoming webhooks.
https://api.slack.com/messaging/webhooks

You can preview a sample JSON file, you can either replace this file and update the filePath in the php file or rewrite the example for your sites.

*SETUP*
Update 'ENTER_SLACK_APP_WEBHOOK_HERE' on SiteUpdateNotifier.php, line 13 with your webhook url.
Rewrite or replace the example json with your own json file, following the sample schema.
