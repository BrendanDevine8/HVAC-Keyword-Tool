# HVAC Keyword Tool with Company Management

## New Features Added

### 📋 Overview
Your HVAC keyword tool now includes comprehensive company management that allows multiple companies to use the system and track their generated content separately.

### 🏢 Company Management Features

1. **Company Registration**
   - Company Name
   - Location/Address  
   - Business Hours
   - Company Type (HVAC, Plumbing, Electric, Commercial HVAC)

2. **Content Tracking**
   - All generated blog posts are linked to companies
   - Track keyword searches per company
   - Word count and generation timestamps
   - Prevent duplicate content generation

3. **Multi-Step Workflow**
   - Step 1: Select/Create Company
   - Step 2: Enter ZIP Code for keyword research
   - Step 3: Generate localized content

### 🗄️ Database Structure

**Companies Table:**
- `id` - Primary key
- `company_name` - Business name
- `location` - Service area
- `hours` - Business hours
- `company_type` - Service category
- `created_at`, `updated_at` - Timestamps

**Blog Posts Table:**
- `id` - Primary key
- `company_id` - Links to companies table
- `zip_code` - Target location
- `keyword` - Search term used
- `title` - Generated title
- `content` - Full HTML content
- `word_count` - Content length
- `generated_at` - Creation timestamp

**Keyword Searches Table:**
- Tracks search frequency per company/zip/keyword
- Used for analytics and optimization

### 📁 File Structure

```
/Applications/MAMP/htdocs/hvac-tool/
├── config.php              # Database & API configuration
├── dashboard.php            # Main interface (updated)
├── admin.php               # Content management interface
├── status.php              # System status checker
├── setup.php               # Database setup runner
├── setup_database.sql      # Database schema
├── api/
│   ├── company.php         # Company CRUD operations
│   ├── generate_post.php   # Content generation (updated)
│   ├── get_keywords.php    # Keyword research
│   └── test_models.php     # API testing
```

### 🚀 Getting Started

1. **Database Setup:**
   ```
   Visit: http://localhost:8888/hvac-tool/setup.php
   ```

2. **Check System Status:**
   ```
   Visit: http://localhost:8888/hvac-tool/status.php
   ```

3. **Main Dashboard:**
   ```
   Visit: http://localhost:8888/hvac-tool/dashboard.php
   ```

4. **Admin Panel:**
   ```
   Visit: http://localhost:8888/hvac-tool/admin.php
   ```

### 🔧 How to Use

#### Creating a Company
1. Go to dashboard.php
2. Click "Create New Company"
3. Fill in company details:
   - Name: "ABC HVAC Services"
   - Location: "Phoenix, AZ"
   - Hours: "Mon-Fri: 8AM-6PM\nEmergency: 24/7"
   - Type: Select from dropdown

#### Selecting Existing Company
1. Click "Select Existing Company"
2. Choose from the list
3. Company info will be displayed

#### Generating Content
1. After selecting company, enter ZIP code
2. Search for keywords
3. Click any keyword to generate localized content
4. Content automatically includes:
   - Company name and details
   - Local area information
   - Business hours where relevant
   - Call-to-action for the company

### 📊 Admin Features

The admin panel (`admin.php`) allows you to:
- View all companies and their content
- See generation statistics
- Access previously generated posts
- Regenerate content with new variations
- Track keyword search patterns

### 🔗 API Endpoints

**Company Management:**
- `GET api/company.php` - List all companies
- `GET api/company.php?id=1` - Get specific company
- `POST api/company.php` - Create new company

**Content Generation:**
- `api/generate_post.php?company_id=1&zip=90001&keyword=ac+repair`

### 🎯 Key Benefits

1. **Multi-Tenant**: Multiple companies can use the same system
2. **Content Tracking**: All generated content is stored and organized
3. **Localization**: Content includes company-specific information
4. **Duplicate Prevention**: Warns when content already exists
5. **Analytics Ready**: Track which keywords and locations perform best
6. **Scalable**: Easy to add new company types and features

### 🔧 Configuration

Update `config.php` for your environment:
- Database credentials
- Claude API key
- API version settings

### 🚀 Next Steps

Potential enhancements:
- User authentication per company
- Content editing interface  
- SEO optimization tools
- Performance analytics dashboard
- Export functionality (PDF, WordPress, etc.)
- Automated posting to CMS systems