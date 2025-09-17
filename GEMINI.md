# Gemini Project Configuration

This file provides specific instructions and context for working on this project with Gemini.

## Project Overview

*   **Description:** MedInfo is a web-based application designed to streamline patient and medical information management for healthcare professionals. It provides features for managing patient records, consultations, prescriptions, and lab results in a secure and centralized platform.
*   **Technology Stack:** This project is built on the TALL stack:
    *   **Laravel:** A PHP web application framework.
    *   **Tailwind CSS:** A utility-first CSS framework.
    *   **Alpine.js:** A minimal JavaScript framework.
    *   **Livewire:** A full-stack framework for Laravel that makes building dynamic interfaces simple.
*   **Key Directories:**
    *   `app/`: Contains the core application logic, including Models, Controllers, and business logic.
    *   `config/`: Stores all of the application's configuration files.
    *   `database/`: Houses database migrations, factories, and seeders.
    *   `resources/`: Contains all of the views, raw assets (CSS, JS), and language files.
    *   `routes/`: Includes all of the route definitions for the application.
    *   `tests/`: Holds the automated tests for the application.

## Conventions & Style

*   **Coding Style:** The project adheres to the PSR-12 coding standard for PHP.
*   **Commit Messages:** Follow the Conventional Commits specification for clear and descriptive commit history.
*   **Branching Strategy:** Use a feature-based branching strategy. Create a new branch for each new feature or bug fix.

## Common Commands

*   **Installation:** `composer install && npm install`
*   **Running the dev server:** `php artisan serve`
*   **Running tests:** `php artisan test`
*   **Building assets:** `npm run build`
