-- Use the database you created in cPanel

USE naijrloq_naijaworkmen;

-- users table (all user types)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(50),
  password_hash VARCHAR(255),
  role ENUM('worker','graduate','company','admin') NOT NULL DEFAULT 'worker',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- worker profile (skilled artisans)
CREATE TABLE workers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  gender VARCHAR(20),
  address TEXT,
  trade VARCHAR(100),
  experience_years INT DEFAULT 0,
  id_doc VARCHAR(255),
  certificate VARCHAR(255),
  photo VARCHAR(255),
  verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
  assessment_result ENUM('none','passed','referred') DEFAULT 'none',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- graduates table
CREATE TABLE graduates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  highest_qualification VARCHAR(100),
  course VARCHAR(150),
  institution VARCHAR(255),
  year_graduated YEAR,
  cv VARCHAR(255),
  verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- companies / clients
CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  company_name VARCHAR(255),
  rc_number VARCHAR(100),
  contact_person VARCHAR(255),
  contact_phone VARCHAR(100),
  address TEXT,
  docs VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- jobs / requests
CREATE TABLE jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT,
  title VARCHAR(255),
  description TEXT,
  required_trade VARCHAR(100),
  location VARCHAR(255),
  budget DECIMAL(12,2),
  status ENUM('pending','assigned','ongoing','completed','cancelled') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- job assignments
CREATE TABLE job_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT,
  worker_id INT,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('assigned','accepted','rejected','completed') DEFAULT 'assigned',
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
);

-- payments
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT,
  amount DECIMAL(12,2),
  commission DECIMAL(12,2),
  paid_to_worker DECIMAL(12,2),
  status ENUM('pending','paid','refunded') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- verification documents
CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  doc_type VARCHAR(100),
  file_name VARCHAR(255),
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- guarantors
CREATE TABLE guarantors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  name VARCHAR(255),
  phone VARCHAR(100),
  relationship VARCHAR(100),
  id_doc VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- references
CREATE TABLE user_references (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  referee_name VARCHAR(255),
  relationship VARCHAR(100),
  phone VARCHAR(100),
  comments TEXT,
  rating TINYINT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
