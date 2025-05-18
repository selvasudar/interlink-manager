# Interlink Manager 

## Description
Interlink Manager is a WordPress plugin designed to help website owners and content managers efficiently manage internal linking throughout their website. It allows you to upload a CSV file containing source URLs, destination URLs, and keywords, then provides an intuitive interface to select where those keywords should be linked within your content.

## Features
- Upload CSV files with source URLs, destination URLs, and keywords
- Automatically locate and highlight keywords in your content
- Select which instances of keywords should become links
- Track the status of interlinks (pending or approved)
- User-friendly interface with visual context around keywords

## Installation
1. Download the plugin zip file
2. Go to WordPress admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin

## Usage

### Preparing Your CSV File
Create a CSV file with the following columns (header names must match exactly):
- `source_url` - The URL of the page where you want to add links
- `destination_url` - The URL you want to link to
- `keyword` - The keyword that should become a link

Example CSV content:
```
source_url,destination_url,keyword
https://yourdomain.com/blog-post-1,https://yourdomain.com/target-page,keyword phrase
https://yourdomain.com/blog-post-2,https://yourdomain.com/another-page,another keyword
```

### Adding Interlinks
1. Navigate to the Interlink Manager in your WordPress admin menu
2. Upload your CSV file using the upload form
3. Review the uploaded data in the table
4. Click the "Approve" button next to any interlink you want to implement
5. In the popup window, select the checkboxes next to the keyword instances you want to convert to links
6. Click "Confirm" to apply the changes

## Screenshots
1. The main Interlink Manager interface showing uploaded interlinks
2. The keyword selection popup with highlighted keyword instances
3. The CSV upload form

## File Structure
```
interlink-manager/
├── css/
│   └── style.css
├── js/
│   └── script.js
├── interlink-manager.php
└── readme.txt
```

## Technical Details

### Database
The plugin creates a custom table in your WordPress database with the following structure:
- `id` - Auto-incrementing ID
- `source_url` - The URL of the page where the link will be added
- `destination_url` - The URL that will be linked to
- `keyword` - The keyword to be linked
- `status` - Current status (pending or approved)

### JavaScript
The plugin uses jQuery for DOM manipulation and AJAX requests to:
- Fetch keyword instances from content
- Display the keyword selection popup
- Process user selections
- Update content with new links

### PHP
The main plugin file handles:
- Plugin activation and database setup
- Admin menu integration
- CSV file processing
- AJAX request handling
- Content modification

## Frequently Asked Questions

### Q: Why aren't my keywords being found in the content?
A: Make sure your keywords exactly match the text in your content. The plugin performs a case-insensitive search but requires the exact words.

### Q: Can I undo an approved interlink?
A: The current version doesn't support undoing approved interlinks. You'll need to edit the page manually to remove links.

### Q: Is there a limit to how many interlinks I can add?
A: There's no built-in limit, but we recommend using internal linking strategically and not overwhelming your content with too many links.

### Q: Does this plugin work with Gutenberg/Block Editor?
A: Yes, the plugin works with both Classic Editor and Gutenberg as it directly modifies the post content.

## Changelog

### 1.0.0
- Initial release

### 1.0.1
- Fixed popup display issues
- Improved error handling
- Enhanced keyword highlighting
- Added better user feedback

## Support
For support, feature requests, or bug reports, please contact the plugin author or submit an issue via GitHub.

## License
This plugin is licensed under the GPL v2 or later.

## Credits
Developed by Selvakumar Duraipandian