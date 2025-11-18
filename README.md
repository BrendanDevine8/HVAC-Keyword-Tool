# HVAC Keyword Research & Blog Management Tool

A comprehensive PHP/MySQL application for HVAC businesses to research keywords, manage content, and automate blog posting with AI integration.

## 🚀 Features

### Core Functionality
- **Keyword Research**: Comprehensive HVAC keyword analysis and tracking
- **ZIP Code Targeting**: Location-based content optimization
- **Company Management**: Multi-company support with individual settings
- **Blog Post Generation**: AI-powered content creation using Claude API

### Advanced Features
- **Live HTML Editor**: Split-screen editor with real-time preview
- **AI Content Rewriting**: 9+ AI enhancement modes (professional, casual, SEO, etc.)
- **Version Control**: Complete revision history with delta storage
- **Automated Posting**: Scheduled content publishing with queue management
- **Content Approval Workflow**: Review and approve AI-generated content

### Technical Features
- **Myers Diff Algorithm**: Efficient 80-90% storage compression for revisions
- **RESTful API**: Comprehensive backend API architecture
- **Auto-save**: Real-time content saving with 3-second intervals
- **Database Optimization**: Automated cleanup with 6-month retention
- **Responsive Design**: Works on desktop and mobile devices

## 📋 Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher  
- **Web Server**: Apache/Nginx
- **Claude API Key**: For AI content generation

## 🛠️ Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/hvac-keyword-tool.git
   cd hvac-keyword-tool
   ```

2. **Set up the database**:
   ```bash
   # Import the main database structure
   mysql -u root -p < setup_database.sql
   
   # Set up automation tables
   php setup_automation.php
   
   # Import ZIP codes (optional)
   php setup-zip-codes.php
   ```

3. **Configure the application**:
   ```php
   // Edit config.php with your settings
   $DB_HOST = 'localhost';
   $DB_NAME = 'hvac_keywords';
   $DB_USER = 'your_username';
   $DB_PASS = 'your_password';
   $CLAUDE_API_KEY = 'your_claude_api_key';
   ```

4. **Set up permissions**:
   ```bash
   chmod 755 api/
   chmod 644 *.php
   ```

## 📁 Project Structure

```
hvac-tool/
├── api/                    # Backend API endpoints
│   ├── automation.php      # Automated posting management
│   ├── ai_rewrite.php     # AI content rewriting
│   ├── revisions.php      # Version control
│   └── generate_post.php  # Content generation
├── includes/              # Shared PHP includes
├── admin.php             # Administration interface
├── dashboard.php         # Main dashboard
├── live_editor.php       # Live content editor
├── automation.php        # Automation configuration
├── setup_automation.php  # Database setup utility
└── README.md             # This file
```

## 🎯 Usage

### Getting Started
1. Visit `dashboard.php` to access the main interface
2. Add your company information and keywords
3. Generate blog posts using the AI integration
4. Use the live editor to refine content

### Live Editor
- **Split-screen editing**: HTML on left, preview on right
- **AI rewriting**: Click "🤖 AI Rewrite" for enhancement options
- **Auto-save**: Content saves automatically every 3 seconds
- **Version history**: Access previous versions with diff view

### Automation Setup
1. Go to `automation.php`
2. Configure keyword targeting and ZIP code preferences
3. Set posting schedule and frequency
4. Enable automation and monitor the queue

## 🤖 AI Integration

### Claude API Features
- **Content Generation**: Automatic blog post creation
- **Content Enhancement**: Multiple rewriting modes
- **SEO Optimization**: Keyword-focused improvements
- **Industry Expertise**: HVAC-specific knowledge base

### Available AI Modes
- ✨ Improve Writing Quality
- 🎯 Make Professional  
- 😊 Make Casual
- 🔧 More Technical
- 📝 Simplify for Homeowners
- 🔍 SEO Optimize
- 📈 Expand Content
- ✂️ Shortening Content
- 💭 Custom Prompts

## 🗄️ Database Schema

### Core Tables
- `companies` - Business information
- `blog_posts` - Content storage
- `blog_post_revisions` - Version control
- `auto_posting_keywords` - Keyword targeting
- `auto_posting_queue` - Scheduled posts

### Features
- **Referential Integrity**: Foreign key constraints
- **Automated Cleanup**: 6-month revision retention
- **Delta Storage**: Efficient diff-based storage
- **User Attribution**: Track content modifications

## 🔧 Development

### Running Locally
```bash
# Start MAMP/XAMPP or your preferred local server
# Navigate to http://localhost/hvac-tool/

# For development, enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Contributing
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -m 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit a pull request

## 📊 Performance

- **Version Control**: 80-90% storage compression using Myers diff
- **Auto-save**: Non-blocking 3-second intervals
- **Database Optimization**: Indexed queries and automated cleanup
- **API Response**: < 200ms for most operations
- **Concurrent Users**: Supports multiple simultaneous editors

## 🔐 Security

- **Input Validation**: All user inputs sanitized
- **SQL Injection Protection**: Prepared statements
- **API Authentication**: Secure endpoint access
- **Content Escaping**: XSS prevention
- **File Upload Security**: Restricted file types

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Support

For questions, bug reports, or feature requests:
- Create an issue on GitHub
- Review existing issues before creating new ones

## 🎉 Acknowledgments

- **Claude AI** - For advanced content generation capabilities
- **Myers Algorithm** - For efficient diff computation
- **Bootstrap** - For responsive UI components
- **PHP Community** - For excellent documentation and resources

---

**Built for HVAC professionals who want to automate their content marketing while maintaining quality and brand consistency.**