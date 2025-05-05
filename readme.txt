=== AI Post Summarizer ===
Author: Anntao Diaz
Tags: ai, gemini, summary, post summary, content, automation
Requires at least: 5.0
Tested up to: (WordPress version you tested up to, e.g., 6.5)
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates post summaries using Google Gemini AI and displays them via a shortcode.

== Description ==

This plugin automatically generates a concise, 3-bullet point summary for your new or updated WordPress posts using the Google Gemini API. The summary can then be displayed anywhere in your post content using the shortcode `[ai_summary]`. A footnote indicating AI generation is included.

Features:
* Automatic summary generation on post save (for 'post' type).
* Integration with Google Gemini API (requires an API Key).
* Customizable background color for the summary block.
* Simple shortcode `[ai_summary]` for easy display.
* Disclosure footnote for AI-generated content.

== Installation ==

1.  Upload the `ai-post-summarizer` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to Settings > AI Post Summarizer and enter your Google Gemini API Key.
4.  Customize the summary block background color if desired.
5.  Add the `[ai_summary]` shortcode to your posts.

== Frequently Asked Questions ==

= How do I get a Google Gemini API Key? =
You can obtain an API key from [Google AI Studio](https://aistudio.google.com/).

= The summary is not appearing. What should I do? =
1.  Ensure you have entered a valid Gemini API Key in the plugin settings (Settings > AI Post Summarizer).
2.  Check if there are any error messages displayed in place of the summary (visible to users who can edit posts).
3.  Make sure the post content is not too short or empty.
4.  The summary is generated when you save or update a post. For existing posts, you'll need to update them once for the summary to be generated.
5.  Ensure you are using the correct shortcode: `[ai_summary]`.

== Changelog ==

= 1.0.1 =
* Changed shortcode from `[gemini_post_summary]` to `[ai_summary]`.
* Added disclosure footnote "AI summary created with Google Gemini".
* Updated version numbers.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
The shortcode has been changed to `[ai_summary]`. Please update any existing shortcodes in your posts. Added AI disclosure footnote.