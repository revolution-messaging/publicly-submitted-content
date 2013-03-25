## Publicly Submitted Content

Requires at least: Wordpress 3.0
Tested up to: 3.5.1

A wordpress plugin that allows site users (logged in or not) to create fairly complex entries (images, text fields, etc) that can be held in moderation until approved by an admin.

## Description

### Installation

1. Activate plugin
2. Create a form
3. Embed the form in a page with `[psc_form_save id="#"]` or `[psc_form_save slug="string"]`

### Features

* Forms are publicly accessible.
* Optional [reCaptcha](http://recaptcha.net "reCaptcha") integration.
* Image upload functionality available.
* Posts can default to "pending", "draft", or "published".
* Posts can default to any category currently set within your wordpress install.

### Fields Available

* Hidden
* Textarea
* Text
* Select
* Multiselect
* Radio
* Checkbox
* Image

When creating fields, select "use as the 'post content'" in order have the plugin place the content of that field in the body of what will become the Wordpress post.