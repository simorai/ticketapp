This is a modern, full-featured ticket management system for centralized handling of internal and external requests and communications, segmented by different areas (Inboxes/Departments) of an organization.

## Tech Stack

- **Backend:** Laravel 12 (PHP)
- **Frontend:** Vue 3, Inertia.js, Pinia, ShadcnVue
- **Database:** MySQL

## Key Features

- **Multi-Inbox Support:** Manage tickets independently for each department (e.g., Commercial, Technical Support, HR), each with its own operators, permissions, and notifications.
- **User Roles:**
    - **Operators:** Create, manage, and respond to tickets in their inboxes; assign operators; change statuses; receive notifications.
    - **Clients:** Create and respond to tickets for their entity; view only their own tickets.
- **Ticket Lifecycle:**
    - Automatic ticket numbering (e.g., TC-193)
    - Subject, type, CC (knowledge), rich text messages with attachments
    - Status tracking (Open, In Progress, Closed, etc.)
    - Operator and entity associations
- **Notifications:**
    - Email notifications for ticket creation, assignment, and replies
    - Configurable email templates
- **Filtering & Search:**
    - Filter by inbox, status, operator, type, and entity
    - Quick search by ticket number, subject, email, or entity
- **Activity Log:**
    - Complete history of messages and changes for each ticket
- **Data Structure:**
    - Entities (with NIF, name, contacts, etc.)
    - Contacts (linked to entities, with roles and internal notes)
    - Tickets (with all relevant fields and relationships)
- **Modern UI:**
    - Inspired by [Kirridesk - Customer Service Management System](https://www.behance.net/gallery/188671509/Kirridesk-Customer-Service-Management-System?tracking_source=search_projects%7Ccustomer+tickets&l=0)
    - Responsive and user-friendly design

## Purpose

This project aims to provide a robust, extensible platform for centralized ticket management, supporting both internal teams and external clients, with a focus on flexibility, security, and clear communication. The architecture follows best practices (SOLID, service layer, DTOs, resources, etc.) for maintainability and scalability.
