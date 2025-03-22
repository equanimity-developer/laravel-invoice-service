# Invoice Management System Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Domain-Driven Design Implementation](#domain-driven-design-implementation)
4. [API Endpoints](#api-endpoints)
5. [Invoice Workflow](#invoice-workflow)
6. [Internationalization](#internationalization)
7. [Testing](#testing)
8. [Error Handling](#error-handling)

## Introduction

This project implements an invoice management system following Domain-Driven Design (DDD) principles. It allows for creating, viewing, and sending invoices with associated product lines. The system integrates with a notification system to handle the delivery of invoices to clients.

```
┌────────────────────┐     ┌────────────────────┐     ┌────────────────────┐
│                    │     │                    │     │                    │
│  Invoice Creation  │───▶│   Send Invoice     │───▶│ Delivery to Client │
│                    │     │                    │     │                    │
└────────────────────┘     └────────────────────┘     └────────────────────┘
```

## Project Structure

The codebase is structured according to DDD principles, with distinct layers:

```
src/
└── Modules/
    └── Invoices/
        ├── Domain/             # Domain Layer
        │   ├── Entities/       # Core business objects
        │   ├── Repositories/   # Repository interfaces
        │   ├── Exceptions/     # Domain-specific exceptions
        │   ├── Enums/          # Value objects
        │   └── Translation/    # Translation interfaces
        │
        ├── Application/        # Application Layer
        │   ├── Services/       # Business logic coordinators
        │   ├── DTOs/           # Data Transfer Objects
        │   └── Listeners/      # Event handlers
        │
        ├── Infrastructure/     # Infrastructure Layer
        │   ├── Eloquent/       # Database implementations
        │   ├── Providers/      # Service providers
        │   └── Translation/    # Translation implementations
        │
        └── Presentation/       # Presentation Layer
            ├── Http/           # Controllers
            ├── Requests/       # Request validation
            └── Exceptions/     # Exception handlers
```

### Domain Layer
- Contains the core business logic and rules
- Located in `src/Modules/Invoices/Domain/`
- Key components:
  - **Entities**: Core business objects (`Invoice`, `ProductLine`)
  - **Repositories**: Interfaces defining persistence operations
  - **Exceptions**: Domain-specific exceptions
  - **Enums**: Value objects like `StatusEnum`
  - **Translation**: Interfaces for internationalization

### Application Layer
- Contains use cases and orchestrates domain objects
- Located in `src/Modules/Invoices/Application/`
- Key components:
  - **Services**: Business logic coordinators like `InvoiceService`
  - **DTOs**: Data Transfer Objects for presenting data
  - **Listeners**: Event handlers

### Infrastructure Layer
- Contains implementations of interfaces defined in the domain
- Located in `src/Modules/Invoices/Infrastructure/`
- Key components:
  - **Eloquent**: Database implementations using Laravel's ORM
  - **Providers**: Service providers and dependency injection setup
  - **Translation**: Framework-specific translation implementations

### Presentation Layer
- Contains user interface components
- Located in `src/Modules/Invoices/Presentation/`
- Key components:
  - **Http**: Web controllers
  - **Requests**: Request validation objects
  - **Exceptions**: Exception handlers for HTTP responses

## Domain-Driven Design Implementation

The project implements several DDD concepts:

```
┌─────────────────────┐     ┌─────────────────────┐
│     Aggregate       │     │    Repository       │
│       Root          │     │    Interface        │
│    (Invoice)        │◄────┤                     │
└─────────────────────┘     └─────────────────────┘
         ▲                            ▲
         │                            │
         │                            │
         │                   ┌─────────────────────┐
┌─────────────────────┐      │    Repository       │
│     Entity          │      │  Implementation     │
│   (ProductLine)     │      │                     │
└─────────────────────┘      └─────────────────────┘
```

### Entities
- **Invoice**: Core aggregate root representing an invoice
- **ProductLine**: Entity representing a line item in an invoice

### Value Objects
- **StatusEnum**: Represents the possible states of an invoice

### Repositories
- **InvoiceRepositoryInterface**: Defines the contract for invoice persistence
- **InvoiceRepository**: Implementation using Eloquent

### Services
- **InvoiceService**: Coordinates business logic around invoices

### Events
- The system uses events for asynchronous processes like marking invoices as delivered

## API Endpoints

### Invoice Endpoints

#### Get All Invoices
- **URL**: `/api/invoices`
- **Method**: GET
- **Response**: List of invoices with their details

#### Get Invoice by ID
- **URL**: `/api/invoices/{id}`
- **Method**: GET
- **Response**: Invoice details or 404 if not found

#### Create Invoice
- **URL**: `/api/invoices`
- **Method**: POST
- **Body**:
  ```json
  {
    "customer_name": "Customer Name",
    "customer_email": "customer@example.com"
  }
  ```
- **Response**: Created invoice details with 201 status

#### Add Product Line to Invoice
- **URL**: `/api/invoices/{id}/product-lines`
- **Method**: POST
- **Body**:
  ```json
  {
    "product_name": "Product Name",
    "quantity": 5,
    "unit_price": 1000
  }
  ```
- **Response**: Updated invoice details or 404 if not found

#### Send Invoice
- **URL**: `/api/invoices/{id}/send`
- **Method**: POST
- **Response**: Updated invoice with sending status or 404 if not found

## Invoice Workflow

```
┌─────────────┐        ┌─────────────┐        ┌─────────────┐
│             │        │             │        │             │
│    Draft    │──────▶│   Sending   │──────▶│ SentToClient│
│             │  send  │             │ webhook│             │
└─────────────┘        └─────────────┘        └─────────────┘
      ▲
      │
      │
      │
┌─────────────┐
│   Create    │
│   Invoice   │
└─────────────┘
```

1. **Invoice Creation**:
   - An invoice is created with `Draft` status
   - It can be created with or without product lines

2. **Adding Product Lines**:
   - Product lines can be added to an invoice in `Draft` status
   - Each product line must have a name, positive quantity, and unit price

3. **Sending Invoice**:
   - An invoice can only be sent if it has valid product lines
   - The invoice status changes from `Draft` to `Sending`
   - A notification is sent to the customer

4. **Delivery Confirmation**:
   - When delivery is confirmed, the invoice status changes to `SentToClient`
   - This is triggered by a notification webhook

## Internationalization

The project implements a clean separation of concerns for translation, following Domain-Driven Design principles:

```
┌─────────────────────┐      ┌─────────────────────┐
│  Domain Layer       │      │  Infrastructure     │
│                     │      │  Layer              │
│ TranslatorInterface │◄─────┤ LaravelTranslator   │
└─────────────────────┘      └─────────────────────┘
         ▲                            ▲
         │                            │
         │                            │
┌─────────────────────┐      ┌─────────────────────┐
│  Domain Entities    │      │  Language Files     │
│                     │      │                     │
│  (Error Codes)      │      │  (Translations)     │
└─────────────────────┘      └─────────────────────┘
```

### Core Components

1. **Domain Layer**:
   - Defines a `TranslatorInterface` that serves as an abstraction for translation services
   - Domain entities use simple string error codes instead of full error messages

2. **Infrastructure Layer**:
   - Provides a Laravel-specific implementation (`LaravelTranslator`) that adapts Laravel's translation function

3. **Translation Files**:
   - All translations are stored in language files (`lang/en/invoices.php`)
   - Translations are organized by category (errors, success, notifications)
   - Messages support parameter substitution for dynamic content

4. **Exception Handling**:
   - The exception handler maps domain error codes to appropriate translation keys
   - This ensures user-friendly messages while keeping the domain pure

### Benefits

- Domain entities remain free from presentation concerns
- Adding support for new languages is straightforward
- Clear separation between business logic and UI text
- Easy maintenance of all text in one place
- Improved testability of domain logic

### Translation Key Structure

```
invoices.errors.not_found              → "Invoice not found."
invoices.errors.invalid_product_lines  → "Cannot send invoice: one or more product lines are invalid."
invoices.success.created               → "Invoice created successfully."
invoices.notifications.subject         → "Invoice #:id"
```

## Testing

The project includes comprehensive tests for all layers:

```
┌─────────────────────┐      ┌─────────────────────┐      ┌─────────────────────┐
│   Unit Tests        │      │  Feature Tests      │      │  Integration Tests  │
│                     │      │                     │      │                     │
│ - Domain Entities   │      │ - Controllers       │      │ - Full Workflow     │
│ - Application Logic │      │ - Repositories      │      │ - Real Database     │
│ - Services          │      │ - Exception Handlers│      │ - API Endpoints     │
└─────────────────────┘      └─────────────────────┘      └─────────────────────┘
```

- **Unit Tests**: Test domain logic and application services in isolation
- **Feature Tests**: Test controllers, repositories, and integrations
- **Test Coverage**: Focuses on core business logic with 100% coverage of critical components

## Error Handling

The system handles several types of errors:

```
┌─────────────────────┐
│  Domain Exception   │
│                     │
│ (Error Code)        │
└─────────────────────┘
          │
          ▼
┌─────────────────────┐
│  Exception Handler  │
│                     │
│(Maps to Translation)│
└─────────────────────┘
          │
          ▼
┌─────────────────────┐
│   HTTP Response     │
│                     │
│ (JSON with message) │
└─────────────────────┘
```

- **Not Found**: When an invoice doesn't exist (404)
- **Validation Errors**: When request data is invalid (422)
- **Domain Exceptions**:
  - `InvalidInvoiceStatusTransitionException`: When an invalid status change is attempted
  - `InvalidProductLineException`: When product lines have invalid data

All errors return appropriate HTTP status codes and descriptive messages from translation files. 
