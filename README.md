# laravel-reverb-chat

# Database Schema for Buyer-Seller Service Chat Application

This schema outlines the structure for a buyer-seller platform where buyers can list services (like haircuts, hair coloring, etc.), and sellers can initiate chat conversations related to specific services. Each chat is tied to a particular service, ensuring clarity and focus within each conversation.

## Database Tables

### 1. Users Table
Stores general user information, covering both buyers and sellers.

| Column     | Type        | Description                                 |
|------------|-------------|---------------------------------------------|
| id         | BIGINT      | Primary key, unique identifier for each user. |
| name       | VARCHAR     | Name of the user.                          |
| email      | VARCHAR     | Email of the user.                         |
| role       | ENUM        | Role of the user (`buyer` or `seller`).    |
| created_at | TIMESTAMP   | Timestamp for when the user was created.   |
| updated_at | TIMESTAMP   | Timestamp for the last update.             |

### 2. Services Table
Lists the details of each service that a buyer posts.

| Column      | Type       | Description                                 |
|-------------|------------|---------------------------------------------|
| id          | BIGINT     | Primary key, unique identifier for each service. |
| user_id     | BIGINT     | Foreign key referencing `users(id)` for the buyer who listed the service. |
| name        | VARCHAR    | Name of the service (e.g., hair cut).       |
| description | TEXT       | Description of the service.                |
| price       | DECIMAL    | Price of the service.                      |
| created_at  | TIMESTAMP  | Timestamp for when the service was created.|
| updated_at  | TIMESTAMP  | Timestamp for the last update.             |

### 3. Chats Table
Tracks each chat session related to a specific service between a buyer and a seller.

| Column      | Type       | Description                                 |
|-------------|------------|---------------------------------------------|
| id          | BIGINT     | Primary key, unique identifier for each chat session. |
| service_id  | BIGINT     | Foreign key referencing `services(id)`, indicating which service the chat is about. |
| buyer_id    | BIGINT     | Foreign key referencing `users(id)` for the buyer involved in the chat. |
| seller_id   | BIGINT     | Foreign key referencing `users(id)` for the seller involved in the chat. |
| created_at  | TIMESTAMP  | Timestamp for when the chat session was initiated. |
| updated_at  | TIMESTAMP  | Timestamp for the last update.             |

### 4. Messages Table
Contains the individual messages sent within each chat session.

| Column      | Type       | Description                                 |
|-------------|------------|---------------------------------------------|
| id          | BIGINT     | Primary key, unique identifier for each message. |
| chat_id     | BIGINT     | Foreign key referencing `chats(id)` to link the message to a chat session. |
| sender_id   | BIGINT     | Foreign key referencing `users(id)` for the user who sent the message. |
| message     | TEXT       | Content of the message.                    |
| created_at  | TIMESTAMP  | Timestamp for when the message was sent.   |

## Summary of Relationships
1. **Users - Services**: One-to-Many relationship (a buyer can list multiple services).
2. **Users - Chats**: Many-to-Many relationship managed through the `Chats` table (a buyer and seller can have multiple chat sessions, each tied to a specific service).
3. **Chats - Messages**: One-to-Many relationship (each chat session can contain multiple messages).