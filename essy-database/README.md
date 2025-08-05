# ESSY Project & Database

## Setup Instructions

### Prerequisites
- PHP 8.1+
- Composer
- SQLite (or configure another database)

### Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd essy-database
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Set up environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Run migrations and seed the database:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

   **Important:** The seeder will automatically import decision rules from the included CSV file. This is required for the application to work properly.

### How to Use:

1. To get started, upload an `.xlsx` file with the contents of a report for 1 or more student.

    1. Note that the title of the tab with the content must be: _Qualtrics Output_

2. After uploading an `.xlsx` file a batch of PDF files will be generated.

    1. Each batch will have the PDF files for the student information that was included in the file.
    2. You can download the entire batch as a compressed ZIP file, or a single report PDF file.

3. You have the option to delete individual reports, as well as entire batches of reports.
