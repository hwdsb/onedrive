# OneDrive #

A WordPress plugin to embed OneDrive items into blog posts. The plugin includes a Gutenberg Block and a `[onedrive]` shortcode for users to paste their shared OneDrive link for embedding.

Optionally, if you create an Azure app, your users can login to OneDrive directly to select a file for embedding all-at-once. For configuration details, read the following wiki article: https://github.com/hwdsb/onedrive/wiki/File-Picker-Set-Up

***

### How to Use

There are two ways to use the plugin:

1. Use the provided **OneDrive** block
2. Use the shortcode method

## (1) Block method

To use:

1. Add the **OneDrive** block to the block editor.
2. Find the shareable link to your OneDrive item by [reading the following guide](https://github.com/hwdsb/onedrive/wiki/Sharing-a-file-and-getting-the-link).
3. Paste the link into the block.
    - For OneDrive Personal users, if you are embedding an audio, image or video file, select the correct Type from the sidebar. This option will **not** work for SharePoint / Microsoft 365 users.
    - That's it!

If your site administrator has set up the [File Picker](https://github.com/hwdsb/onedrive/wiki/File-Picker-Set-Up), steps 2 and 3 can be skipped by clicking on the **Or Select From Drive** button inside the OneDrive block. This will allow you to login to your OneDrive to select a file for embedding.

**Note:** If you are using OneDrive for SharePoint / Microsoft 365, you can only directly embed Word, PowerPoint, Excel and Visio files due to restrictions.


## (2) Shortcode method

The shortcode method is useful for those still using the Classic Editor, but can also be used with the Block Editor using the **Shortcode** block if desired.

[Read the following guide](https://github.com/hwdsb/onedrive/wiki/Generating-the-shortcode) for more information.
