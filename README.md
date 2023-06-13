moodle-local_ifttt
=========================

Th plugin integrates Workplace with IFTTT service through Dynamic rule actions.
This allows connecting Workplace site with 750+ apps and services in IFTTT and
expand the power of Dynamic rules beyond Workplace site.

Installation
------------

Plugin files need to be placed in `./local/ifttt` directory in
Moodle, then you will need to go through installation process as normal by
loggining in as site admin.

In the plugin configuration, specify Webhooks service key. In order to find it,
navigate to [Webhooks Integrations](https://ifttt.com/maker_webhooks) in IFTTT
user profile and click "Documentation".

How it works
------------

First, create an applet in IFTTT service with a Webhook event trigger ("Receive
a web request"), give it a name and add IFTTT action of your choice.

Once applet is created, create a Dynamic rule with an action to execute the
trigger with an optional extra data denoted as `Value1..3`, which will passed to
IFTTT service.

When Workplace user meets Dynamic Rule conditions, IFTTT service is called with
an optional payload.
