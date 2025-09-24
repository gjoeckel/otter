# Reports Architecture and Logic Guide

**Document Status**: Active and up-to-date.

This document provides a comprehensive overview of the architecture, data flow, and business logic for the reporting system.

## 1. Core Business Logic

The reporting system is designed to display registration and enrollment data, which can be viewed in two primary modes: "by date" and "by cohort". The logic is governed by the following rules:

-   **Registrations Radio is Authoritative**: The selection on the Registrations widget (`by-date` vs. `by-cohort`) determines the primary dataset for both the Registrations and Enrollments tables.
-   **Enrollments Radio is Secondary**: The selection on the Enrollments widget (`by-tou` vs. `by-registration`) only affects the Enrollments data and does not change the primary mode (date/cohort).
-   **"ALL" Date Range Safeguard**: When the "ALL" date range is selected, the system automatically forces the "by-date" mode and disables the "by-cohort" option to ensure performance and clarity.

### Core Datasets

The backend (`reports_api.php`) generates six distinct datasets to support the various display modes on the frontend:

-   `registrations_submissions`: Registrations filtered by submission date.
-   `registrations_cohort`: Registrations filtered by cohort/year.
-   `submissions_enrollments_tou`: Enrollments based on "by-date" registrations, counted by Term of Use completion date.
-   `submissions_enrollments_registrations`: Enrollments based on "by-date" registrations, counted by registration date.
-   `cohort_enrollments_tou`: Enrollments based on "by-cohort" registrations, counted by Term of Use completion date.
-   `cohort_enrollments_registrations`: Enrollments based on "by-cohort" registrations, counted by registration date.

## 2. System Architecture

The reporting system is composed of four key components that work together to fetch, manage, and display the data.

-   **`reports_api.php` (Backend)**: A PHP script that serves as the single API endpoint for all reporting data. It fetches raw data from Google Sheets, processes it, and returns a unified JSON object containing all six core datasets.
-   **`unified-data-service.js` (Frontend Service)**: A JavaScript class (`ReportsDataService`) that acts as the central point for all data fetching on the frontend. It makes a single call to `reports_api.php` and manages the application state (e.g., current date range, selected modes).
-   **`unified-table-updater.js` (Frontend UI)**: A JavaScript class (`UnifiedTableUpdater`) that takes the data from the `ReportsDataService` and is responsible for rendering it into the various HTML tables on the reports page.
-   **`reports-data.js` (Frontend Orchestrator)**: The main JavaScript file that orchestrates the entire process. It handles user interactions (like changing the date range or display mode), initializes the data service and table updater, and triggers data refreshes.

## 3. Data Flow

The data flows through the system in the following sequence:

1.  **User Interaction**: The process begins when a user changes the date range or selects a different display mode on the reports page.
2.  **Orchestration (`reports-data.js`)**: The event listeners in `reports-data.js` capture the user's action. The `getCurrentModes()` function determines the currently selected display modes.
3.  **Data Fetching (`unified-data-service.js`)**: The `fetchAndUpdateAllTables()` function calls the `ReportsDataService`, which constructs the appropriate URL and makes a single `fetch` request to `reports_api.php`. The request includes the date range and selected modes as URL parameters.
4.  **Backend Processing (`reports_api.php`)**: The PHP script receives the request, fetches the raw data from Google Sheets (utilizing a cache for performance), and processes it to generate all six core datasets. It then returns a single JSON object containing these datasets.
5.  **UI Update (`unified-table-updater.js`)**: Once the data is returned to the frontend, the `ReportsDataService` passes it to the `UnifiedTableUpdater`. This class then updates the HTML for the system-wide, organizations, and groups tables with the appropriate data based on the user's selections.

## 4. Race Condition Handling

To prevent race conditions caused by rapid user input (e.g., a user typing quickly in the date range fields), the system employs a **debouncing** mechanism.

-   **How it Works**: When a user triggers a data refresh, the application does not send the API request immediately. Instead, it waits for a brief period (e.g., 200ms). If the user triggers another refresh within that window, the timer is reset. The API request is only sent after the user has paused their input for the specified duration.
-   **Implementation**: This is handled in `reports-data.js` and `unified-data-service.js` using `setTimeout` and `clearTimeout`.
-   **Benefit**: This is a simple and reliable solution that ensures only a single, final API request is sent, preventing unnecessary server load and avoiding potential conflicts from multiple, simultaneous data requests.

This architecture was implemented to refactor a previous version that made multiple, parallel API calls, leading to inefficiencies and code complexity. The current unified system is more performant, reliable, and easier to maintain.
