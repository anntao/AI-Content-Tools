=== AI Post Summarizer & Polls ===
Contributors: Anntao Diaz
Tags: ai, gemini, summary, poll, post summary, post poll, content, automation, interactive content
Requires at least: 5.0
Tested up to: 5.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generates post summaries and interactive polls using Google Gemini AI. Display with shortcodes [ai_summary] and [ai_poll].

== Description ==

Enhance your WordPress posts with AI-powered content! This plugin automatically generates:
1.  Concise, 3-bullet point summaries for your posts.
2.  Engaging, opinion-based poll questions with 3 answer options related to your post content.

Both features leverage the Google Gemini API. Generated content can be easily displayed using simple shortcodes. Disclosure footnotes are included for transparency.

**Features:**

* **AI Summaries:**
    * Automatic summary generation on post save (for 'post' type).
    * Summaries are presented in a maximum of 3 bullet points.
    * Customizable background color for the summary block.
    * Display with `[ai_summary]` shortcode.
    * Includes "AI summary created with Google Gemini" footnote.
* **AI Polls:**
    * Automatic opinion-based poll generation (question + 3 options) on post save (if enabled).
    * Polls designed to spark discussion based on article themes.
    * Interactive voting for website visitors.
    * AJAX-powered voting (no page reload).
    * Results displayed after voting (percentage-based).
    * Cookie-based vote tracking to prevent multiple votes per poll.
    * Display with `[ai_poll]` shortcode.
    * Includes "AI poll created with Google Gemini" footnote.
* **General:**
    * Integration with Google Gemini API (requires an API Key).
    * Admin settings page to manage API key, summary appearance, and enable/disable polls.

== Installation ==

1.  Download the zip file from "Releases" and upload to WP
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings > AI Content Tools** in your WordPress admin area.
4.  Enter your Google Gemini API Key.
5.  Customize the summary block background color if desired.
6.  Check the "Enable AI-Generated Polls" option if you want to use the poll feature.
7.  Save settings.
8.  Add the `[ai_summary]` shortcode to your posts where you want summaries to appear.
9.  Add the `[ai_poll]` shortcode to your posts where you want polls to appear.

== Frequently Asked Questions ==

= How do I get a Google Gemini API Key? =
You can obtain an API key from [Google AI Studio](https://aistudio.google.com/).

= Summaries or Polls are not appearing. What should I do? =
1.  Ensure you have entered a valid Gemini API Key in the plugin settings (Settings > AI Content Tools).
2.  For polls, ensure the "Enable AI-Generated Polls" option is checked in the plugin settings.
3.  Check if there are any error messages displayed in place of the content (visible to users who can edit posts).
4.  Make sure the post content is not too short or empty, as AI generation depends on sufficient context.
5.  Summaries and polls are generated when you save or update a post. For existing posts, you'll need to update them once for the content to be generated.
6.  Ensure you are using the correct shortcodes: `[ai_summary]` for summaries and `[ai_poll]` for polls.

= How are votes for polls tracked? =
Votes are tracked using browser cookies to prevent a single user from voting multiple times on the same poll from the same browser.

== Changelog ==

= 1.1.0 =
* NEW: Added AI-generated polls feature.
    * Generates an opinion-based question and 3 options from post content.
    * Shortcode `[ai_poll]` for display.
    * AJAX voting and results display.
    * Cookie-based vote tracking.
    * Admin setting to enable/disable poll generation.
    * Added "AI poll created with Google Gemini" footnote.
* Plugin name and description updated to reflect new poll functionality.
* Admin settings page retitled to "AI Content Tools" and reorganized.
* Updated `readme.txt` comprehensively.

= 1.0.1 =
* Changed summary shortcode from `[gemini_post_summary]` to `[ai_summary]`.
* Added disclosure footnote "AI summary created with Google Gemini" for summaries.
* Updated version numbers.

= 1.0.0 =
* Initial release: AI Post Summarizer.
    * Generated 3-bullet point summaries.
    * Shortcode `[gemini_post_summary]`.
    * Admin settings for API key and summary background color.

== Upgrade Notice ==

= 1.1.0 =
Major feature update! This version introduces AI-generated polls (`[ai_poll]`). Please review the new "Enable AI-Generated Polls" option in the plugin settings (Settings > AI Content Tools). The plugin name and admin menu have also been updated.
