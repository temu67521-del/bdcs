-- ============================================================
-- BDCS — PostgreSQL schema + seed data
-- For Render PostgreSQL
-- ============================================================

DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS registrations CASCADE;
DROP TABLE IF EXISTS courses CASCADE;
DROP TABLE IF EXISTS teachers CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS students CASCADE;
DROP TABLE IF EXISTS admins CASCADE;

-- ============ CATEGORIES ============
CREATE TABLE categories (
  id   SERIAL PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  icon VARCHAR(10) DEFAULT '📘'
);

INSERT INTO categories (name, slug, icon) VALUES
  ('Basic Computer',        'basic',       '💻'),
  ('Programming',           'programming', '⌨️'),
  ('Maintenance',           'maintenance', '🔧'),
  ('Software Installation', 'software',    '📀'),
  ('Networking',            'networking',  '🌐'),
  ('Design',                'design',      '🎨');

-- ============ TEACHERS ============
CREATE TABLE teachers (
  id        SERIAL PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  title     VARCHAR(120) DEFAULT '',
  photo_url TEXT,
  bio       TEXT,
  email     VARCHAR(120) DEFAULT ''
);

INSERT INTO teachers (full_name, title, photo_url, bio, email) VALUES
('Abebaw Kebede','Senior Instructor, MSc','https://i.pravatar.cc/200?img=12','12+ years teaching computer fundamentals and MS Office.','abebaw@bdcs.et'),
('Selam Tesfaye','MS Office Specialist','https://i.pravatar.cc/200?img=45','Certified Microsoft Office trainer for Excel and Word.','selam@bdcs.et'),
('Temesgen Alemu','Java & Backend Developer','https://i.pravatar.cc/200?img=33','Software engineer with 8 years of Java and Spring Boot.','temesgen@bdcs.et'),
('Hana Girma','Frontend Developer','https://i.pravatar.cc/200?img=47','Frontend engineer — HTML, CSS, JavaScript, modern web.','hana@bdcs.et'),
('Yonas Mulu','Python & Data','https://i.pravatar.cc/200?img=15','Data scientist teaching Python, pandas and MySQL.','yonas@bdcs.et'),
('Meron Bekele','Mobile Developer','https://i.pravatar.cc/200?img=32','Android and cross-platform developer with 6 years experience.','meron@bdcs.et'),
('Tsegaye Worku','Systems & Hardware','https://i.pravatar.cc/200?img=53','Hardware specialist teaching maintenance and OS setup.','tsegaye@bdcs.et');

-- ============ COURSES ============
CREATE TABLE courses (
  id           SERIAL PRIMARY KEY,
  category_id  INTEGER NOT NULL REFERENCES categories(id),
  name         VARCHAR(120) NOT NULL,
  description  TEXT,
  duration     VARCHAR(60) NOT NULL,
  start_date   DATE,
  end_date     DATE,
  certificate  VARCHAR(120) DEFAULT 'Certificate of Completion',
  fee          NUMERIC(10,2) NOT NULL DEFAULT 0,
  discount     NUMERIC(10,2) NOT NULL DEFAULT 0,
  teacher_id   INTEGER REFERENCES teachers(id) ON DELETE SET NULL,
  schedule     VARCHAR(120) DEFAULT '',
  max_students INTEGER NOT NULL DEFAULT 20,
  enrolled     INTEGER NOT NULL DEFAULT 0,
  level        VARCHAR(20) NOT NULL DEFAULT 'Beginner',
  syllabus     TEXT,
  active       BOOLEAN NOT NULL DEFAULT TRUE
);

-- Basic Computer courses
INSERT INTO courses (category_id,name,description,duration,start_date,end_date,certificate,fee,discount,teacher_id,schedule,max_students,level,syllabus) VALUES
(1,'Computer Fundamentals','Learn parts of a computer, Windows, files and safe use.','1 Month','2026-01-05','2026-02-05','Certificate of Completion',1200,200,1,'Mon/Wed/Fri 8:00-10:00',25,'Beginner','Parts of a computer
Windows desktop
Files and folders
USB and printing
Safe use'),
(1,'MS Word','Create professional documents with Word.','1 Month','2026-01-05','2026-02-05','Certificate of Completion',900,0,2,'Tue/Thu 8:00-10:00',25,'Beginner','Word window
Formatting text
Paragraphs and styles
Tables
Page layout'),
(1,'MS Excel','Master spreadsheets, formulas and charts.','1 Month','2026-01-06','2026-02-06','Certificate of Completion',1200,300,2,'Mon/Wed/Fri 10:00-12:00',25,'Beginner','Cells
Formulas
Formatting
Charts
Filters'),
(1,'MS PowerPoint','Build attractive slide presentations.','1 Month','2026-01-06','2026-02-06','Certificate of Completion',900,100,1,'Tue/Thu 10:00-12:00',25,'Beginner','Slides
Text and shapes
Transitions
Charts
Presenting'),
(1,'Internet & Email','Browse safely and use email.','2 Weeks','2026-01-05','2026-01-19','Certificate of Completion',700,0,1,'Mon-Fri 14:00-16:00',30,'Beginner','Browsers
Search
Email
Attachments
Safety'),
(1,'Typing Skills','Type fast and accurately.','3 Weeks','2026-01-05','2026-01-26','Certificate of Completion',600,100,2,'Mon/Wed/Fri 16:00-17:30',30,'Beginner','Home row
Touch typing
Speed
Keypad'),
(1,'Full Basic Package','Complete beginner package.','3 Months','2026-01-05','2026-04-05','Certificate of Completion',3500,500,1,'Mon-Fri 8:00-12:00',20,'Beginner','All basic topics');

-- Programming courses
INSERT INTO courses (category_id,name,description,duration,start_date,end_date,certificate,fee,discount,teacher_id,schedule,max_students,level,syllabus) VALUES
(2,'Java','OOP programming with Java from scratch.','3 Months','2026-01-05','2026-04-05','Certificate of Completion',4500,500,3,'Mon/Wed/Fri 14:00-16:00',20,'Intermediate','Java basics
Variables
Control flow
OOP
Collections'),
(2,'HTML & CSS','Build and style your first web pages.','2 Months','2026-01-05','2026-03-05','Certificate of Completion',2500,300,4,'Tue/Thu 14:00-16:00',25,'Beginner','HTML
Tags
CSS
Box model
Flexbox/Grid'),
(2,'JavaScript','Add interactivity to web pages.','2 Months','2026-01-06','2026-03-06','Certificate of Completion',3000,400,4,'Mon/Wed 16:00-18:00',20,'Intermediate','Variables
DOM
Events
Arrays
Fetch API'),
(2,'Website Development','Full-stack web development.','4 Months','2026-01-05','2026-05-05','Certificate of Completion',6000,1000,4,'Mon-Fri 16:00-18:00',15,'Advanced','Frontend
Backend
DB
Auth
Deploy'),
(2,'App Development','Build Android applications.','4 Months','2026-01-06','2026-05-06','Certificate of Completion',7000,1000,6,'Tue/Thu/Sat 10:00-12:00',15,'Advanced','UI
Activities
Storage
Networking
Publish'),
(2,'Python','Learn Python for real-world problems.','3 Months','2026-01-05','2026-04-05','Certificate of Completion',4500,500,5,'Mon/Wed/Fri 8:00-10:00',20,'Beginner','Syntax
Data types
Loops
Files
OOP'),
(2,'Database (PostgreSQL)','Design and query relational databases.','2 Months','2026-01-06','2026-03-06','Certificate of Completion',3000,300,5,'Tue/Thu 8:00-10:00',20,'Intermediate','Design
SQL
Joins
Indexes
Backups'),
(2,'Go','Fast concurrent backends with Go.','3 Months','2026-01-05','2026-04-05','Certificate of Completion',5000,500,7,'Mon/Wed/Fri 18:00-20:00',15,'Intermediate','Go syntax
Structs
Goroutines
HTTP
DB'),
(2,'C++','Systems and competitive programming.','3 Months','2026-01-06','2026-04-06','Certificate of Completion',4500,500,7,'Tue/Thu 18:00-20:00',20,'Intermediate','Basics
Pointers
Classes
STL'),
(2,'Full Programming Bundle','Web, Python, Java, DB, mobile.','6 Months','2026-01-05','2026-07-05','Certificate of Completion',12000,2000,3,'Mon-Fri 14:00-18:00',10,'Advanced','Web
Python
Java
MySQL
Mobile');

-- Maintenance courses
INSERT INTO courses (category_id,name,description,duration,start_date,end_date,certificate,fee,discount,teacher_id,schedule,max_students,level,syllabus) VALUES
(3,'Computer Repair & Maintenance','Diagnose and fix hardware problems.','2 Months','2026-01-05','2026-03-05','Certificate of Completion',3500,500,7,'Mon/Wed 10:00-12:00',15,'Intermediate','Hardware parts
Troubleshooting
Cleaning
Replacing parts
Preventive care'),
(3,'Laptop Repair','Open, clean and repair laptops safely.','1 Month','2026-01-06','2026-02-06','Certificate of Completion',2500,300,7,'Tue/Thu 10:00-12:00',15,'Beginner','Laptop anatomy
Keyboard/battery
Screen replacement
Diagnostics');

-- Software Installation courses
INSERT INTO courses (category_id,name,description,duration,start_date,end_date,certificate,fee,discount,teacher_id,schedule,max_students,level,syllabus) VALUES
(4,'Windows Installation & Setup','Install Windows and configure a new PC.','2 Weeks','2026-01-05','2026-01-19','Certificate of Completion',1500,200,7,'Mon-Fri 16:00-17:30',20,'Beginner','BIOS
USB install
Drivers
Activation
Backup'),
(4,'Software Installation Package','Install Office, browsers, antivirus and more.','3 Weeks','2026-01-06','2026-01-27','Certificate of Completion',1800,300,2,'Tue/Thu/Sat 14:00-16:00',20,'Beginner','Windows setup
Office
Browsers
Antivirus
Utilities');

-- Networking courses
INSERT INTO courses (category_id,name,description,duration,start_date,end_date,certificate,fee,discount,teacher_id,schedule,max_students,level,syllabus) VALUES
(5,'Computer Networking Basics','Set up small home and office networks.','2 Months','2026-01-05','2026-03-05','Certificate of Completion',3000,400,3,'Mon/Wed 18:00-20:00',15,'Intermediate','Network types
IP addresses
Router setup
Wi-Fi
Troubleshooting');

-- Design courses
INSERT INTO courses (category_id,name,description,duration,start_date,end_date,certificate,fee,discount,teacher_id,schedule,max_students,level,syllabus) VALUES
(6,'Graphic Design (Photoshop)','Design posters, logos and banners.','2 Months','2026-01-06','2026-03-06','Certificate of Completion',3500,500,4,'Tue/Thu 16:00-18:00',15,'Beginner','Photoshop UI
Layers
Selection
Text
Exporting');

-- ============ STUDENTS ============
CREATE TABLE students (
  id         SERIAL PRIMARY KEY,
  full_name  VARCHAR(120) NOT NULL,
  email      VARCHAR(120) NOT NULL,
  phone      VARCHAR(20)  NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ REGISTRATIONS ============
CREATE TABLE registrations (
  id             SERIAL PRIMARY KEY,
  student_id     INTEGER NOT NULL REFERENCES students(id),
  course_id      INTEGER NOT NULL REFERENCES courses(id),
  amount         NUMERIC(10,2) NOT NULL,
  payment_method VARCHAR(20) NOT NULL,
  status         VARCHAR(20) NOT NULL DEFAULT 'Pending',
  tx_ref         VARCHAR(40) NOT NULL UNIQUE,
  payer_ref      VARCHAR(80) DEFAULT '',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ PAYMENTS ============
CREATE TABLE payments (
  id              SERIAL PRIMARY KEY,
  registration_id INTEGER NOT NULL REFERENCES registrations(id),
  method          VARCHAR(20) NOT NULL,
  transaction_id  VARCHAR(80),
  amount          NUMERIC(10,2) NOT NULL,
  status          VARCHAR(20) NOT NULL DEFAULT 'Paid',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ ADMINS ============
CREATE TABLE admins (
  id            SERIAL PRIMARY KEY,
  username      VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- DONE
-- The admin user (admin / 1234) is created automatically by api.php on first run.
-- ============================================================