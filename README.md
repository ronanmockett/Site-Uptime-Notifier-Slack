# Site-Uptime-Notifier
Notifies you on Slack if your site(s) go down. 
This script assumes you are calling the file via a Cron or task scheduling system.

You can find really simple instructions below on how to setup your first Slack App and enable incoming webhooks.
https://api.slack.com/messaging/webhooks

I have provided a sample JSON file, you can either replace this file and update the $filePath in the php file or rewrite the example for your sites.
You could also fetch this file from an external source, however you will need to update the 'file_put_contents' function with your own solution as this tool updates the file. 

Minimum PHP Version: 7 ( Can easily be adapted for lower versions )

**Setup** <br />
---
1. Update **'ENTER_SLACK_APP_WEBHOOK_HERE'** in SiteUpdateNotifier.php, line 13 with your webhook url.
2. Rewrite or replace the example json with your own json file, following the sample schema.
