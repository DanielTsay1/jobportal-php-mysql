# JobPortal - Advanced Job Portal System

## 🏆 FBLA Website Coding & Development Competition Entry

A sophisticated, full-featured job portal system built with PHP, MySQL, and modern JavaScript. This project demonstrates advanced web development techniques, professional user experience design, and comprehensive functionality for both job seekers and employers.

## ✨ Key Features

### 🎯 Core Functionality
- **Dual User System**: Separate interfaces for job seekers (Type B) and recruiters (Type A)
- **Job Posting & Management**: Complete job lifecycle management with approval workflows
- **Application System**: Multi-resume support with cover letter uploads
- **Real-time Search**: Advanced filtering with AJAX-powered instant results
- **Analytics Dashboard**: Comprehensive recruiter analytics with Chart.js visualizations

### 🚀 Advanced Features

#### Real-time Search & Filtering
- **Instant Search**: AJAX-powered search with debounced input handling
- **Advanced Filters**: Location, salary range, job category, and keyword filtering
- **Dynamic Results**: Real-time result updates without page refresh
- **Smart Suggestions**: Autocomplete for job titles and companies

#### Interactive Dashboard
- **Recruiter Analytics**: 
  - Application status distribution charts
  - Job performance metrics
  - Real-time statistics
  - Recent application tracking
- **Job Seeker Dashboard**:
  - Application status tracking
  - Resume management
  - Profile analytics

#### Notification System
- **Real-time Notifications**: WebSocket-like polling system
- **Smart Badges**: Animated notification counters
- **Context-aware**: Different notifications for job seekers vs recruiters
- **Mark as Read**: Individual and bulk read status management

#### Multi-Resume Management
- **Multiple Resumes**: Users can upload and manage multiple resume versions
- **Smart Selection**: Choose from existing resumes or upload new ones during applications
- **Inline Editing**: Rename resume files with real-time updates
- **File Validation**: Secure file upload with type and size restrictions

#### Advanced Form Handling
- **Progressive Validation**: Real-time form validation with visual feedback
- **File Upload Preview**: Drag-and-drop file upload with preview
- **Error Handling**: Comprehensive error messages and recovery
- **CSRF Protection**: Secure form submissions

### 🎨 User Experience Enhancements

#### Modern UI/UX
- **Responsive Design**: Mobile-first approach with Bootstrap 5
- **Smooth Animations**: CSS transitions and JavaScript animations
- **Loading States**: Professional loading indicators and spinners
- **Interactive Elements**: Hover effects and micro-interactions

#### Accessibility Features
- **ARIA Labels**: Semantic HTML with proper accessibility attributes
- **Keyboard Navigation**: Full keyboard accessibility
- **Screen Reader Support**: Proper heading hierarchy and alt text
- **Color Contrast**: WCAG compliant color schemes

#### Professional Polish
- **Error Handling**: Graceful error handling with user-friendly messages
- **Loading Optimizations**: Lazy loading and performance optimizations
- **Security Features**: Input sanitization and SQL injection prevention
- **Code Quality**: Well-documented, maintainable code structure

## 🛠 Technical Implementation

### Backend Architecture
```php
// Advanced database queries with prepared statements
$stmt = $conn->prepare("
    SELECT j.*, c.name as company_name, c.location as company_location 
    FROM `job-post` j 
    JOIN company c ON j.compid = c.compid 
    WHERE $where_clause 
    ORDER BY j.created_at DESC
");

// Real-time notification system
function getNotifications($conn, $userid, $recid) {
    // Context-aware notification retrieval
    // Supports both job seekers and recruiters
}
```

### Frontend Technologies
- **Bootstrap 5**: Modern responsive framework
- **Chart.js**: Interactive data visualizations
- **Fetch API**: Modern AJAX implementation
- **ES6+ JavaScript**: Classes, async/await, arrow functions

### Database Design
```sql
-- Advanced schema with proper relationships
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `jobid` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_userid` (`userid`),
  KEY `idx_jobid` (`jobid`)
);
```

## 📊 FBLA Competition Compliance

### ✅ Required Features
- [x] **Job Posting System**: Complete job creation and management
- [x] **Approval Panel**: Admin approval workflow for job postings
- [x] **Application System**: Comprehensive application process
- [x] **Responsive Design**: Mobile and desktop compatibility
- [x] **Error Handling**: Professional error management
- [x] **User Authentication**: Secure login/logout system

### 🏅 Advanced Features (Bonus Points)
- [x] **Real-time Search**: AJAX-powered instant search
- [x] **Analytics Dashboard**: Data visualization with charts
- [x] **Notification System**: Real-time user notifications
- [x] **Multi-resume Support**: Advanced file management
- [x] **Professional UI/UX**: Modern, polished interface
- [x] **Accessibility**: WCAG compliance features
- [x] **Security**: CSRF protection, input sanitization
- [x] **Performance**: Optimized queries and caching

### 📈 Scoring Potential
- **Technical Excellence**: Advanced PHP/MySQL implementation
- **User Experience**: Professional, intuitive interface
- **Innovation**: Real-time features and modern web technologies
- **Code Quality**: Well-structured, documented code
- **Functionality**: Comprehensive feature set beyond requirements

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4+ with MySQL support
- MySQL 5.7+ or MariaDB 10.2+
- Web server (Apache/Nginx)

### Quick Start
1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/jobportal-php-mysql.git
   cd jobportal-php-mysql
   ```

2. **Database Setup**
   ```bash
   # Import the database schema
   mysql -u username -p database_name < sql/jobportal.sql
   ```

3. **Configuration**
   ```php
   // Edit php/db.php with your database credentials
   $host = 'localhost';
   $username = 'your_db_user';
   $password = 'your_db_password';
   $database = 'jobportal';
   ```

4. **File Permissions**
   ```bash
   # Ensure uploads directory is writable
   chmod 755 uploads/
   chmod 755 uploads/profile_pictures/
   ```

5. **Access the Application**
   - Navigate to `http://localhost/jobportal-php-mysql/main/`
   - Register as both a job seeker and recruiter to test all features

## 📁 Project Structure

```
jobportal-php-mysql/
├── css/                    # Stylesheets
│   ├── style.css          # Main styles
│   ├── profile.css        # Profile page styles
│   └── dashboard.css      # Dashboard styles
├── js/                    # JavaScript files
│   ├── main.js           # Main functionality
│   └── login.js          # Login handling
├── main/                  # Main application pages
│   ├── index.php         # Homepage
│   ├── profile.php       # User profile management
│   ├── job-list.php      # Job search with filters
│   ├── recruiter.php     # Recruiter dashboard
│   └── apply.php         # Job application system
├── php/                   # Backend logic
│   ├── db.php            # Database connection
│   ├── notifications.php # Notification system
│   ├── update-profile.php # Profile updates
│   └── apply.php         # Application processing
├── uploads/              # File uploads
│   ├── profile_pictures/ # User profile images
│   └── resumes/          # Resume files
└── sql/                  # Database schema
    └── jobportal.sql     # Complete database structure
```

## 🔧 Advanced Features Documentation

### Real-time Search Implementation
The job search system uses debounced AJAX requests to provide instant results:

```javascript
// Debounced search with 500ms delay
const debouncedSearch = debounce(performSearch, 500);

function performSearch() {
    const formData = new FormData();
    formData.append('search', searchInput.value);
    // ... AJAX implementation
}
```

### Notification System Architecture
The notification system provides real-time updates using polling:

```php
// Context-aware notification retrieval
function getNotifications($conn, $userid, $recid) {
    if ($userid) {
        // Job seeker notifications
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE userid = ?");
    } else {
        // Recruiter notifications for their company's jobs
        $stmt = $conn->prepare("SELECT n.* FROM notifications n JOIN job-post j ON n.jobid = j.jobid WHERE j.recid = ?");
    }
}
```

### Analytics Dashboard
The recruiter dashboard provides comprehensive analytics using Chart.js:

```javascript
// Application status distribution chart
new Chart(applicationCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Reviewed', 'Hired', 'Rejected'],
        datasets: [{
            data: [pending, reviewed, hired, rejected],
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545']
        }]
    }
});
```

## 🎯 Competition Advantages

### Technical Sophistication
- **Modern Web Technologies**: ES6+, Fetch API, Chart.js
- **Advanced Database Design**: Proper relationships and indexing
- **Security Best Practices**: Prepared statements, input validation
- **Performance Optimization**: Efficient queries and caching

### User Experience Excellence
- **Professional Design**: Modern, intuitive interface
- **Real-time Features**: Instant search and notifications
- **Mobile Responsiveness**: Perfect mobile experience
- **Accessibility**: WCAG compliant design

### Innovation Points
- **Multi-resume Management**: Advanced file handling
- **Analytics Dashboard**: Data visualization
- **Smart Notifications**: Context-aware messaging
- **Progressive Enhancement**: Graceful degradation

## 📞 Support & Contact

For questions about this FBLA competition entry or technical implementation:

- **Developer**: Mandy Chang, Daniel Tsay, Brian Lin
- **School**: Union High School
- **Competition**: FBLA Website Coding & Development
- **Year**: 2024-2025

---

