# HVAC Keyword Research & Blog Management Tool

A comprehensive PHP/MySQL application for HVAC businesses to research keywords, manage content, and automate blog posting with AI integration. Features a complete automation system with queue processing, scheduled publishing, and advanced content management capabilities.

## 🚀 Features

### Core Functionality
- **Keyword Research**: Comprehensive HVAC keyword analysis and tracking
- **ZIP Code Targeting**: Location-based content optimization with 200+ pre-loaded ZIP codes
- **Company Management**: Multi-company support with individual settings and automation
- **Blog Post Generation**: AI-powered content creation using Claude API with SEO optimization

### Advanced Features
- **Live HTML Editor**: Split-screen editor with real-time preview and auto-save
- **AI Content Rewriting**: 9+ AI enhancement modes (professional, casual, SEO, technical, etc.)
- **Version Control**: Complete revision history with delta storage using Myers diff algorithm
- **Automated Posting**: Full automation system with keyword targeting and ZIP code scheduling
- **Content Approval Workflow**: Review, edit, and approve AI-generated content before publishing

### Automation System 🤖
- **Queue Processing**: Background worker processes pending blog posts automatically
- **Intelligent Scheduling**: Hourly, daily, weekly, or monthly posting frequencies
- **Keyword Targeting**: Include/exclude patterns with priority weighting
- **Location Targeting**: ZIP code-based content generation with priority scoring
- **Error Handling**: Comprehensive error tracking and retry mechanisms
- **Queue Management**: Real-time queue monitoring with status tracking
- **Cron Integration**: Easy setup with provided cron job configuration

### Technical Features
- **Myers Diff Algorithm**: Efficient 80-90% storage compression for revisions
- **RESTful API**: Comprehensive backend API architecture
- **Auto-save**: Real-time content saving with 3-second intervals
- **Database Optimization**: Automated cleanup with 6-month retention
- **Responsive Design**: Works on desktop and mobile devices
- **Background Processing**: Non-blocking queue execution with detailed logging

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
   
   # Set up automation tables and queue system
   php setup_automation.php
   
   # Import ZIP codes with climate data (optional but recommended)
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
   $CLAUDE_API_VERSION = '2023-06-01';
   ```

4. **Set up automation (critical for automated posting)**:
   ```bash
   # Create logs directory
   mkdir -p logs
   chmod 755 logs
   
   # Set up the cron job for queue processing
   crontab -e
   # Add this line for 5-minute automation:
   */5 * * * * /usr/bin/php /path/to/hvac-tool/queue_processor.php >> /path/to/hvac-tool/logs/cron.log 2>&1
   
   # Or run the setup helper
   php cron_setup.php
   ```

5. **Set up permissions**:
   ```bash
   chmod 755 api/
   chmod 755 includes/
   chmod 644 *.php
   chmod +x queue_processor.php
   ```

## 📁 Project Structure

```
hvac-tool/
├── api/                       # Backend API endpoints
│   ├── automation.php         # Automated posting management & configuration
│   ├── ai_rewrite.php        # AI content rewriting with multiple modes
│   ├── revisions.php         # Version control & diff management
│   ├── company.php           # Company CRUD operations
│   └── generate_post.php     # AI content generation engine
├── includes/                  # Shared PHP includes
│   └── delta_engine.php      # Myers diff algorithm implementation
├── logs/                      # Automation logs (created automatically)
│   ├── queue_processor.log   # Queue processing activity
│   └── cron.log              # Cron job execution logs
├── admin.php                 # Content management interface
├── dashboard.php             # Main dashboard & company selection
├── live_editor.php           # Live HTML editor with preview
├── automation.php            # Automation configuration interface
├── queue_processor.php       # ⭐ Critical: Background queue processor
├── setup_automation.php     # Database setup for automation system
├── debug_automation.php     # System diagnostics & health monitoring
├── cron_setup.php           # Cron job configuration helper
└── README.md                # This documentation
```

### Key Files Explained

**Core Automation:**
- `queue_processor.php` - **Critical component** that processes pending blog posts
- `automation.php` - Web interface for configuring automation settings
- `api/automation.php` - Backend API for automation management

**Content Management:**
- `live_editor.php` - Advanced HTML editor with real-time preview
- `api/ai_rewrite.php` - AI-powered content enhancement
- `api/revisions.php` - Version control with diff visualization

**System Utilities:**
- `debug_automation.php` - Comprehensive system health monitoring
- `cron_setup.php` - Helper for setting up automated execution

## 🎯 Usage

### Getting Started
1. Visit `dashboard.php` to access the main interface
2. Add your company information, business hours, and service type
3. Configure automation settings and keyword targets
4. Generate blog posts using the AI integration
5. Use the live editor to refine and enhance content

### Setting Up Automation 🤖

#### 1. Configure Company Settings
```bash
# Visit automation.php in your browser
http://localhost/hvac-tool/automation.php
```

#### 2. Set Up Keywords & Targeting
- **Add Keywords**: Include/exclude patterns with priority weights
- **ZIP Code Targets**: Add specific service areas with priorities
- **Posting Schedule**: Choose frequency (hourly, daily, weekly, monthly)
- **Enable Automation**: Turn on automated posting

#### 3. Generate Queue Items
- Click "Generate Queue" to create scheduled posts
- System combines keywords × ZIP codes = unique post combinations
- Posts are scheduled based on your frequency settings

#### 4. Monitor Automation
- **Real-time logs**: `tail -f logs/queue_processor.log`
- **System health**: Visit `debug_automation.php`
- **Queue status**: Check automation interface for pending/completed items

### Live Editor Features
- **Split-screen editing**: HTML on left, live preview on right
- **AI rewriting**: Click "🤖 AI Rewrite" for enhancement options
- **Auto-save**: Content saves automatically every 3 seconds  
- **Version history**: Access previous versions with visual diff comparison
- **Full-screen mode**: Distraction-free editing experience

### Automation Management
- **Queue Overview**: View pending, processing, completed, and failed posts
- **Company Statistics**: Track post generation rates and success metrics
- **Keyword Performance**: Monitor which keywords generate the best content
- **Error Handling**: Review and retry failed posts with detailed error logs

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
- `companies` - Business information with automation settings
- `blog_posts` - Content storage with word counts and metadata  
- `blog_post_revisions` - Version control with delta compression
- `zip_codes` - Location data with climate and demographic info

### Automation Tables
- `auto_posting_keywords` - Keyword targeting rules with priorities
- `auto_posting_zip_targets` - Geographic targeting with weights
- `auto_posting_queue` - Scheduled post queue with status tracking
- `auto_posting_stats` - Company-level automation performance metrics

### Features
- **Referential Integrity**: Foreign key constraints with cascade deletes
- **Automated Cleanup**: 6-month revision retention with background processing
- **Delta Storage**: Efficient diff-based storage achieving 80-90% compression
- **User Attribution**: Track content modifications and AI enhancements
- **Queue Management**: Status tracking (pending → processing → completed/failed)
- **Performance Metrics**: Success rates, error tracking, and timing analytics

## 🔧 Development & Troubleshooting

### Running Locally
```bash
# Start MAMP/XAMPP or your preferred local server
# Navigate to http://localhost/hvac-tool/

# For development, enable error reporting in config.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Automation Troubleshooting

#### Check System Status
```bash
# Visit the debug page
http://localhost/hvac-tool/debug_automation.php

# Or check logs manually
tail -f logs/queue_processor.log
tail -f logs/cron.log
```

#### Common Issues & Solutions

**No posts being generated:**
1. Check if cron job is running: `crontab -l`
2. Verify queue processor works: `php queue_processor.php`
3. Check company has automation enabled and keywords/ZIPs configured
4. Look for errors in logs: `tail logs/queue_processor.log`

**API errors:**
1. Verify Claude API key is valid in `config.php`
2. Check API usage limits at console.anthropic.com
3. Review error messages in queue for specific API failures

**Database issues:**
1. Ensure all automation tables exist: `php setup_automation.php`
2. Check database permissions for the web user
3. Verify foreign key constraints are properly set

#### Manual Testing
```bash
# Test queue processor manually
cd /path/to/hvac-tool
php queue_processor.php

# Test specific API endpoints
curl -X POST http://localhost/hvac-tool/api/automation.php

# Check database connectivity
php test-db.php
```

### Development Workflow
```bash
# Create feature branch
git checkout -b feature/automation-enhancement

# Make changes and test
php queue_processor.php  # Test automation
php debug_automation.php > test_results.html  # Check system health

# Commit and push
git add .
git commit -m "Add automation enhancement"
git push origin feature/automation-enhancement
```

### Performance Monitoring
- **Queue Processing Time**: Monitor logs for slow operations
- **API Response Times**: Track Claude API latency 
- **Database Query Performance**: Use EXPLAIN for slow queries
- **Memory Usage**: Monitor PHP memory consumption during batch processing
- **Disk Space**: Monitor log file growth and implement rotation

### Contributing
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Test automation thoroughly: `php debug_automation.php`
4. Commit changes: `git commit -m 'Add new feature'`
5. Push to branch: `git push origin feature/new-feature`
6. Submit a pull request with automation test results

## 📊 Performance & Scalability

### Automation Performance
- **Queue Processing**: Handles 10 posts per execution cycle (configurable)
- **Execution Time**: 5-minute maximum with timeout protection
- **API Rate Limiting**: 0.1-second delays between API calls to prevent throttling
- **Memory Efficiency**: Processes queue items individually to minimize memory usage
- **Error Recovery**: Failed posts are marked and logged without blocking other items

### Storage Optimization
- **Version Control**: 80-90% storage compression using Myers diff algorithm
- **Auto-save**: Non-blocking 3-second intervals with change detection
- **Database Optimization**: Indexed queries on frequently accessed columns
- **Log Rotation**: Automatic cleanup of old log files (configurable retention)
- **Revision Cleanup**: 6-month automatic purging of old content versions

### Scalability Considerations
- **Concurrent Processing**: Queue processor designed for single-instance execution
- **Multi-Company Support**: Isolated automation settings per company
- **API Quota Management**: Built-in monitoring and error handling for API limits
- **Database Partitioning**: Ready for partitioning by company_id if needed
- **Horizontal Scaling**: Stateless design allows multiple web server instances

### Monitoring Metrics
- **Posts Generated**: Track successful automation across all companies
- **API Usage**: Monitor Claude API consumption and costs
- **Queue Health**: Pending vs completed vs failed post ratios
- **System Load**: Processing time and resource utilization tracking

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

- **Claude AI (Anthropic)** - For advanced content generation capabilities and reliable API
- **Myers Algorithm** - For efficient diff computation enabling space-efficient version control
- **Bootstrap** - For responsive UI components and consistent styling
- **PHP Community** - For excellent documentation and robust ecosystem
- **HVAC Industry Experts** - For domain knowledge and keyword insights

---

**Built for HVAC professionals who want to automate their content marketing while maintaining quality and brand consistency.**

## 🚀 Quick Start Guide

### For Immediate Automation Setup:

1. **Install & Configure**:
   ```bash
   git clone [repository]
   php setup_automation.php
   # Edit config.php with your Claude API key
   ```

2. **Add Your Company**:
   - Visit `dashboard.php`
   - Add company details and service information
   - Configure business hours and service areas

3. **Set Up Automation**:
   - Go to `automation.php`
   - Add 5-10 relevant HVAC keywords
   - Add your service ZIP codes
   - Set posting frequency (start with daily)
   - Enable automation

4. **Enable Queue Processing**:
   ```bash
   # Add to crontab:
   */5 * * * * php /path/to/hvac-tool/queue_processor.php
   ```

5. **Monitor & Optimize**:
   - Check `debug_automation.php` for system health
   - Review generated content quality
   - Adjust keywords and frequency as needed

**You'll have automated, SEO-optimized HVAC blog posts generating within hours!**

## 🔮 Roadmap & Future Features

### Planned Enhancements
- **Content Performance Analytics**: Integration with Google Analytics/Search Console
- **Advanced Keyword Intelligence**: Search volume and difficulty metrics
- **Multi-Language Support**: Spanish and other language content generation
- **Social Media Integration**: Automated posting to social platforms
- **Client Portal**: White-label interface for HVAC companies
- **Advanced Scheduling**: Seasonal content calendars and holiday-aware posting

### Integration Opportunities  
- **CRM Integration**: HubSpot, Salesforce connectivity
- **Website Integration**: WordPress, Squarespace plugins
- **Email Marketing**: Mailchimp, Constant Contact automation
- **Local SEO**: Google My Business posting integration