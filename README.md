
# Capstone Project Deployment Report & Documentation

**Project Title:** TaskMaster – Multi-User Server-Side Rendered (SSR) Task Management Application

**Live URL:** [https://taskmaster.bond](https://taskmaster.bond)

**GitHub Repository:** [https://github.com/lansipanjenalyn20-glitch/TaskMaster](https://github.com/lansipanjenalyn20-glitch/TaskMaster)

**Author/Student:** Jenalyn Lansipan

**Course:** Web Development 2 – Capstone Project

**Hosting Provider:** Hostinger

## 1. Executive Summary & Project Overview

**TaskMaster** is a secure, multi-user, server-side rendered task management application built with PHP (PDO), MySQL, and Tailwind CSS. It allows users to register, securely log in, manage tasks (CRUD operations, priority tagging, completion toggling, due dates, search/filtering), import tasks via JSON, and maintain strict data isolation between user accounts. The project is fully deployed, secured with HTTPS, and hosted live on a custom domain via Hostinger.

## 2. Domain Name Registration & Configuration

-   **Domain Registrar & Provider:** Hostinger
    
-   **Custom Domain:** `taskmaster.bond` (Valid for 1 year).
    
-   **DNS Settings Configuration:**
    
    -   **A Record:** `@` pointed to Hostinger server IP address (`taskmaster.bond.cdn.hstgr.net`).
        
    -   **A Record:** `www` pointed to Hostinger server IP address (`www.taskmaster.bond.cdn.hstgr.net`).
        
    -   **Nameservers:** Configured to point to Hostinger DNS (`byte.dns-parking.com`, `pixel.dns-parking.co`).
        
-   **SSL/HTTPS Implementation:**
    
    -   Leveraged Hostinger's built-in **Free SSL Certificate** included with the hosting plan, automatically installed and activated during Initialization of the new added website.
        
    -  **HTTPS Enforcement:** Verified that the SSL certificate is fully valid, with secure HTTPS active across all pages, and HTTP traffic automatically forced to secure HTTPS via `.htaccess` redirection rules to prevent mixed-content warnings.
        

## 3. Hosting Setup & Server Configuration

-   **Hosting Platform:** Hostinger Web Hosting (Premium plan).
    
-   **Server Environment:**
    
    -   Web Server: Apache (managed via Hostinger hPanel / `mod_rewrite` enabled).
        
    -   PHP Version: PHP 8.2 (configured via hPanel PHP Configuration).
        
    -   Database: MySQL via phpMyAdmin.
        
-   **Database Deployment Steps:**
    
    1.  Created a production database (`u131435850_taskmaster`) via Hostinger MySQL Database manager.
        
    2.  Exported local schema and imported via phpMyAdmin.
        
    3.  Tables deployed: `users` (with unique usernames and bcrypt password hashes) and `tasks` (relational foreign key `user_id` mapped with `ON DELETE CASCADE`).
        
-   **Environment & Security Configuration:**
    
    -   Database connection parameters (`includes/mysql/conn.php`) secured on the server with strict file permissions.
        
    -   Production error reporting turned off in production (`display_errors = Off`) to prevent sensitive path disclosures.
        
    -   Debug modes disabled.
        

## 4. Evaluator Access & Credentials (Demo Account)

To test and evaluate the live application, a dedicated demo account has been pre-configured:

-   **Live URL:** [https://taskmaster.bond](https://www.google.com/url?sa=E&source=gmail&q=https://taskmaster.bond&authuser=1)
    
-   **Demo Username:** `test_user`
    
-   **Demo Password:** `1234` _(Note: Evaluators can also freely use the registration page to test fresh user account creation and complete data sandboxing)._
    

## 5. Security & Best Practices Implemented

-   **Authentication & Session Security:** Passwords are securely hashed using PHP’s native `password_hash()` (Bcrypt algorithm) and verified using `password_verify()`. Sessions use strict server-side `$_SESSION['user_id']` checks.
    
-   **SQL Injection Prevention:** All database operations utilize PDO (PHP Data Objects) with **prepared statements** and parameter binding across every query (`getTasks`, `insertTask`, `updateTask`, `deleteTask`).
    
-   **Data Isolation (Authorization):** Every query strictly cross-checks the session's `user_id` against the `tasks.user_id` column to ensure users cannot view or mutate other users' tasks.
    
-   **Input Validation & Sanitization:** All incoming POST parameters are sanitized, checked for expected data types, and escaped before rendering using `htmlspecialchars()`.
    

## 6. Step-by-Step Deployment Guide

1.  **Domain Purchase & Setup:** Purchased `taskmaster.bond` domain from Hostinger.
    
2.  **File Transfer:** Packaged project files (`dashboard.php`, `index.php`, `register.php`, `logout.php`, and `includes/`) and uploaded them to the `public_html/` directory via Hostinger File Manager / FTP.
    
3.  **Database Setup:** Exported localhost data and import it to a MySQL database in Hostinger hPanel, and also updating `conn.php` with live production credentials.
    
4.  **SSL Activation:** Enabled Lifetime/Let's Encrypt SSL inside hPanel and verified automatic HTTP-to-HTTPS redirection.
    
5.  **Live Testing:** Tested authentication flow, multi-user task isolation, CRUD actions, and responsive layout across mobile and desktop viewports.
    

## 7. Reflection & Challenges Learned

-   **Challenges Encountered:**
    
    -   _Session Routing Issues:_ Initially, session handling threw headers redirection warnings and session notice errors because `session_start()` was redundantly called across included modular handler files.
        
    -   _Solution:_ Implemented conditional session checking using `session_status() === PHP_SESSION_NONE` and centralized clean redirection paths to `dashboard.php`.
        
-   **Lessons Learned:** Gained deep practical experience handling production DNS propagation, enforcing database foreign key cascades on shared hosting environments, and ensuring secure password hashing workflows for multi-user architectures.
