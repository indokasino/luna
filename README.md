# Luna Chatbot

A PHP-based chatbot system with hybrid response capabilities - database lookups with fallback to GPT-4.1 and GPT-4o.

## System Overview

Luna is a modular, lightweight chatbot webhook system designed to:

1. Receive webhook requests from Chatbot.com
2. Search a local database for matching questions/answers
3. Fallback to OpenAI's GPT-4.1 when no database match is found
4. Further fallback to GPT-4o if GPT-4.1 fails
5. Log all interactions for review and training

The system includes a complete admin panel for managing Q&A pairs, reviewing GPT responses, viewing logs, and configuring system settings.

## Directory Structure

```
/luna/
├── admin/                  # Admin panel
│   ├── includes/           # Common admin includes
│   │   ├── header.php      # Admin header
│   │   └── footer.php      # Admin footer
│   ├── delete.php          # Delete Q&A entries
│   ├── edit.php            # Add/edit Q&A entries
│   ├── history.php         # View interaction logs
│   ├── index.php           # Admin dashboard
│   ├── login.php           # Admin login
│   ├── logout.php          # Admin logout
│   ├── maintenance.php     # System maintenance
│   ├── review.php          # Review GPT responses
│   └── settings.php        # System settings
├── api/                    # API endpoints
│   └── webhook-handler.php # Main webhook endpoint
├── assets/                 # Static assets
│   ├── css/                # Stylesheets
│   │   ├── admin.css       # Admin styles
│   │   └── style.css       # Frontend styles
│   └── js/                 # JavaScript
│       └── validation.js   # Form validation
├── inc/                    # Core includes
│   ├── auth.php            # Authentication
│   ├── db.php              # Database connection
│   └── functions.php       # Helper functions
├── logs/                   # Log directory (created automatically)
├── migrations/             # Database migrations
│   └── create-tables.sql   # Database schema
├── index.php               # Main index (redirects to admin)
├── qna.php                 # Q&A export utility
└── prompt-luna.txt         # GPT system prompt template
```

## Installation

1. **Upload Files**

   Upload all files to your web server, maintaining the directory structure.

2. **Database Setup**

   Create a new MySQL database for Luna and adjust connection settings in `inc/db.php`:

   ```php
   private $host = 'localhost';
   private $dbname = 'your_database_name';
   private $username = 'your_database_user';
   private $password = 'your_database_password';
   ```

3. **Create Database Tables**

   Run the SQL script in `migrations/create-tables.sql` to create the necessary tables.

4. **Permissions**

   Make sure the following directories are writable:
   - `/logs/` - For system logs
   - `/prompt-luna.txt` - For updating the system prompt

5. **Initial Login**

   Access the admin panel at `http://your-domain.com/luna/admin/login.php`
   
   Default credentials:
   - Username: `admin`
   - Password: `admin123`
   
   **Important:** Change the default password immediately after first login via the settings page.

## Configuration

1. **OpenAI API Key**

   Set your OpenAI API key in the admin panel under Settings.

2. **System Prompt**

   Customize the system prompt template (`prompt-luna.txt`) to define your chatbot's personality and behavior.

3. **API Token**

   Generate a secure API token in the admin panel. This token is required for external services to communicate with your webhook.

4. **GPT Models**

   Configure primary and fallback GPT models in the settings. The default configuration is:
   - Primary: `gpt-4.1` (maps to `gpt-4-turbo` in OpenAI API)
   - Fallback: `gpt-4o`

## Webhook Integration with Chatbot.com

1. In your Chatbot.com dashboard, navigate to Settings > Integrations
2. Add a new Webhook integration
3. Set the webhook URL to:
   ```
   https://your-domain.com/luna/api/webhook-handler.php
   ```
4. Add your API token to the authorization header:
   ```
   Authorization: Bearer YOUR_API_TOKEN
   ```
5. Set the content type to `application/json`
6. Configure the chatbot to forward messages to your webhook

## Adding Q&A Entries

1. Manually add entries through the admin panel at `/admin/edit.php`
2. Review and approve GPT-generated responses at `/admin/review.php`
3. Import bulk Q&A data using the database import functionality

## Exporting Knowledge Base

Use the `/qna.php` page to access all Q&A entries in a format suitable for copying to Chatbot.com's Knowledge Base.

## Maintenance

1. **Clean Old Logs**

   Use the maintenance page to clean up old logs according to your retention policy.

2. **Database Backup**

   Regularly backup your database to preserve your Q&A entries.

3. **Check Logs**

   Monitor the log files in the `/logs/` directory for any errors or issues.

## Security Considerations

1. **API Token**

   Keep your API token secure and regenerate it periodically.

2. **Password Security**

   Change the default admin password immediately and use a strong password.

3. **File Permissions**

   Ensure only the necessary files and directories are writable.

## Support and Troubleshooting

If you encounter issues:

1. Check the log files in the `/logs/` directory
2. Verify your OpenAI API key is valid and has sufficient quota
3. Make sure your server meets the requirements:
   - PHP 7.4 or later
   - MySQL 5.7 or later
   - PDO extension enabled
   - Curl extension enabled

## License

This software is proprietary. Unauthorized copying, modification, distribution, or use is strictly prohibited.