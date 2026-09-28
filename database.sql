CREATE DATABASE IF NOT EXISTS future_skills
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE future_skills;

CREATE TABLE IF NOT EXISTS site_content (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  page VARCHAR(30) NOT NULL,
  content_key VARCHAR(100) NOT NULL,
  title VARCHAR(255) NULL,
  content_json LONGTEXT NOT NULL,
  updated_by VARCHAR(190) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_page_content_key (page, content_key),
  KEY idx_page (page)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  school_name VARCHAR(150) NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('Pending','Replied') NOT NULL DEFAULT 'Pending',
  admin_reply TEXT NULL,
  replied_by VARCHAR(190) NULL,
  replied_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_status_created (status, created_at),
  KEY idx_email (email)
) ENGINE=InnoDB;

-- Seed content based on the supplied Future Skills – Robotics & AI brochure.
INSERT INTO site_content (page, content_key, title, content_json) VALUES
('home','hero_eyebrow','Hero Eyebrow',JSON_QUOTE('Future-Ready Robotics, Artificial Intelligence & Hands-On Technology Education')),
('home','hero_title','Hero Title',JSON_QUOTE('Empowering Young Minds for Tomorrow''s World')),
('home','hero_description','Hero Description',JSON_QUOTE('Education for Schools — Grades 1–12. Learn • Build • Innovate • Lead.')),
('home','stats_months','Months',JSON_QUOTE('10')),
('home','stats_sessions','Sessions',JSON_QUOTE('8')),
('home','stats_projects','Projects',JSON_QUOTE('40+')),
('home','value_title','Value Proposition Title',JSON_QUOTE('The future belongs to students who can think, create, solve and innovate')),
('home','value_description','Value Proposition Description',JSON_QUOTE('Moving beyond textbooks into practical technology through cognitive foundations, creativity and innovation, problem-solving through design, and hands-on learning confidence.')),
('home','value_props','Core Value Propositions',JSON_ARRAY(
  JSON_OBJECT('title','Zero Cost Lab Setup for Schools','description','₹0 setup cost, no hardware purchase and zero financial risk. Future Skills sets up the Robotics Lab free of cost.'),
  JSON_OBJECT('title','Grades 1–12 Program Structure','description','Age-appropriate, progressive learning across four levels, from beginner electronics through advanced Arduino and AI applications.'),
  JSON_OBJECT('title','40+ Innovative Projects','description','Hands-on project possibilities spanning Arduino Nano & Uno, robotics, electronics, sensors, automation, IoT and AI.')
)),
('home','zero_investment_title','Zero Investment Model',JSON_QUOTE('We set up the Robotics Lab — free of cost.')),
('home','zero_investment_description','Zero Investment Description',JSON_QUOTE('Schools can introduce structured Robotics & AI education without major upfront investment.')),
('scope','eyebrow','Scope Eyebrow',JSON_QUOTE('10-month program • 8 sessions per month')),
('scope','title','Scope Title',JSON_QUOTE('Structured 8-Session Learning Journey')),
('scope','description','Scope Description',JSON_QUOTE('Every month follows a structured journey from component introduction and real-world application to DIY project building, AI integration and evaluation.')),
('scope','sessions','Sessions',JSON_ARRAY(
  JSON_OBJECT('number',1,'title','Component Introduction','description','Introduce the component and its purpose.'),
  JSON_OBJECT('number',2,'title','Real-World Application','description','Connect the component to a real-world use case.'),
  JSON_OBJECT('number',3,'title','Hands-On Connection','description','Work hands-on with the component and connections.'),
  JSON_OBJECT('number',4,'title','Circuit Completion','description','Complete and understand the working circuit.'),
  JSON_OBJECT('number',5,'title','Individual Discussion','description','Discuss understanding, observations and ideas individually.'),
  JSON_OBJECT('number',6,'title','DIY Project Build','description','Turn the learning into a practical DIY project.'),
  JSON_OBJECT('number',7,'title','AI Integration','description','Introduce AI integration where relevant to the project.'),
  JSON_OBJECT('number',8,'title','Quiz & Evaluation','description','Assess understanding through quiz and evaluation.')
)),
('scope','levels','Learning Levels',JSON_ARRAY(
  JSON_OBJECT('grade','Grades 1–3','focus','Simple Robotics & Beginner Electronics'),
  JSON_OBJECT('grade','Grades 4–6','focus','Arduino Basics & Sensor Projects'),
  JSON_OBJECT('grade','Grades 7–9','focus','Robotics, Automation & IoT Concepts'),
  JSON_OBJECT('grade','Grades 10–12','focus','Advanced Arduino & AI Applications')
)),
('scope','technology_tags','Technology Tags',JSON_ARRAY('Arduino Nano & Uno','Robotics & Electronics','Sensors & Automation','IoT & AI')),
('skills','eyebrow','Skills Eyebrow',JSON_QUOTE('From Technology Users → Technology Creators')),
('skills','title','Skills Title',JSON_QUOTE('Future Ready Skills')),
('skills','description','Skills Description',JSON_QUOTE('Build cognitive foundations, critical thinking, creativity and problem-solving through design — moving students beyond technology use toward technology creation.')),
('skills','foundations','Cognitive Foundations',JSON_ARRAY(
  JSON_OBJECT('title','Logical & Critical Thinking','description','Develop logical and critical thinking skills through practical technology learning.'),
  JSON_OBJECT('title','Creativity & Innovation','description','Use hands-on projects to encourage creativity, experimentation and innovation.'),
  JSON_OBJECT('title','Problem-Solving Through Design','description','Learn to solve problems by designing, building, testing and improving solutions.')
)),
('skills','creator_title','Creator Title',JSON_QUOTE('Students don''t just learn robotics. They build it.')),
('skills','creator_description','Creator Description',JSON_QUOTE('Electronic components and circuits, sensors and Arduino platforms, robot models and smart controls, plus IoT and AI applications make learning visible, practical and memorable.')),
('skills','stack','Technology Stack',JSON_ARRAY('Robotics','Electronics','Arduino','Sensors','Automation','IoT','Artificial Intelligence')),
('skills','project_groups','Project Groups',JSON_ARRAY(
  JSON_OBJECT('title','Foundation Projects','description','Students progress from basic electronics to practical systems.','projects',JSON_ARRAY('LED Blinking System','Traffic Light Controller','Smart Street Light','Water Level Indicator','Motion Sensor Light','Simple Robot Car','Smart Dustbin','Mini Piano & Buzzer Alarm')),
  JSON_OBJECT('title','Intermediate Projects','description','Connect technology with everyday problems.','projects',JSON_ARRAY('Obstacle Avoiding Robot','Line Following Robot','Fire Alarm System','Smart Irrigation','Bluetooth LED Control','Digital Thermometer','RFID Attendance','Smart Home Model')),
  JSON_OBJECT('title','Advanced Robotics, IoT & Automation','description','Move from projects to smart systems.','projects',JSON_ARRAY('Smart Home Automation','IoT Weather Monitoring','Gesture Controlled Robot','Voice Controlled System','GPS Tracking System','Smart City Integrated Model')),
  JSON_OBJECT('title','AI & Capstone Innovations','description','Prepare students for the AI era.','projects',JSON_ARRAY('AI Face Detection','AI Smart Bot','IoT Health Monitor','AI-Based Attendance','AI Traffic Management','AI Chatbot + Hardware','Final Capstone Innovation Project'))
))
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  content_json = VALUES(content_json);
