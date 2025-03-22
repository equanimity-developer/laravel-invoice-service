# Invoice Management System Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Domain-Driven Design Implementation](#domain-driven-design-implementation)
4. [API Endpoints](#api-endpoints)
5. [Invoice Workflow](#invoice-workflow)
6. [Testing](#testing)
7. [Error Handling](#error-handling)

## Introduction

This project implements an invoice management system following Domain-Driven Design (DDD) principles. It allows for creating, viewing, and sending invoices with associated product lines. The system integrates with a notification system to handle the delivery of invoices to clients.

## Project Structure

The codebase is structured according to DDD principles, with distinct layers:

### Domain Layer
- Contains the core business logic and rules
- Located in `src/Modules/Invoices/Domain/`
- Key components:
  - **Entities**: Core business objects (`Invoice`, `ProductLine`)
  - **Repositories**: Interfaces defining persistence operations
  - **Exceptions**: Domain-specific exceptions
  - **Enums**: Value objects like `StatusEnum`

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
  - **Http/Controllers/Api**: API controllers
  - **Providers**: Service providers and dependency injection setup

### Presentation Layer
- Contains user interface components
- Located in `src/Modules/Invoices/Presentation/`
- Key components:
  - **Http**: Web controllers
  - **Requests**: Request validation objects
  - **routes.php**: Route definitions

## Domain-Driven Design Implementation

The project implements several DDD concepts:

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
    "name": "Product Name",
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

## Testing

The project includes comprehensive tests for all layers:

- **Unit Tests**: Test domain logic and application services in isolation
- **Feature Tests**: Test controllers, repositories, and integrations
- **Test Coverage**: Focuses on core business logic with 100% coverage of critical components

## Error Handling

The system handles several types of errors:

- **Not Found**: When an invoice doesn't exist (404)
- **Validation Errors**: When request data is invalid (422)
- **Domain Exceptions**:
  - `InvalidInvoiceStatusTransitionException`: When an invalid status change is attempted
  - `InvalidProductLineException`: When product lines have invalid data

All errors return appropriate HTTP status codes and descriptive messages. 