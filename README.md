# AI CMS

AI CMS is a Laravel-based, API-only Content Management System that leverages AI for content generation and management. All interactions are performed via RESTful API endpoints—there is no web frontend. This project is ideal for headless CMS use cases, mobile apps, or integration with custom frontends.

## Features
- JWT-based authentication
- Role-based access (admin, author, etc.)
- Article and category management
- AI-powered content generation (OpenAI integration)
- API-first design (no Blade views or session-based auth)
- Ready-to-use Postman collection for API testing

## Getting Started

### Prerequisites
- PHP >= 8.1
- Composer
- MySQL or compatible database
- [OpenAI API key](https://platform.openai.com/)

### Installation
1. **Clone the repository:**
   ```bash
   git clone https://github.com/vikrant-mayekar/ai-cms.git
   cd ai-cms
   ```
2. **Install dependencies:**
   ```bash
   composer install
   ```
3. **Set up environment variables:**
   - Copy `.env.example` to `.env`:
     ```bash
     cp .env.example .env
     ```
   - Configure your database and add your OpenAI API key in `.env`:
     ```env
     OPENAI_API_KEY=your_openai_api_key_here
     ```
   - Generate JWT secret:
     ```bash
     php artisan jwt:secret
     ```
4. **Run migrations and seeders:**
   ```bash
   php artisan migrate --seed
   ```
5. **Start the development server:**
   ```bash
   php artisan serve
   ```

## Authentication
- Authenticate by POSTing to `/api/login` with email and password (see seeders for demo users).
- Use the returned JWT token in the `Authorization: Bearer <token>` header for all protected endpoints.

### Example Users
- `admin@cms.com` / `password123`
- `author@cms.com` / `password123`

## API Usage
- All endpoints are under `/api/*`.
- Use the provided Postman collection (`CMS_API_Postman_Collection.json`) for ready-to-use API requests.

### Example: Authenticate and Use API
1. **Login:**
   - POST `/api/login` with JSON body:
     ```json
     { "email": "admin@cms.com", "password": "password123" }
     ```
   - Copy the `access_token` from the response.
2. **Authenticated Request:**
   - For any protected endpoint, add a header:
     ```
     Authorization: Bearer <access_token>
     ```

## Environment Variables
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Database configuration
- `OPENAI_API_KEY`: Your OpenAI API key
- `JWT_SECRET`: Generated via `php artisan jwt:secret`

## Contribution
Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License
This project is licensed under the MIT License.
