# Demand Profile Generator

A PHP-based web application that generates residential electricity demand profiles based on user inputs and publicly available data.

## Features

- Generate 15-minute interval demand data for a full year
- Create seasonal demand profile graphs (Spring, Summer, Fall, Winter)
- Export data in Green Button CSV format
- Account for various load factors:
  - Air conditioning usage
  - Electric heating
  - Work-from-home patterns
  - Electric vehicle charging

## Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx) with PHP support
- OR use PHP's built-in development server

## Installation

1. Clone or download this repository
2. Ensure PHP is installed on your system
3. Configure your web server to point to this directory, OR use PHP's built-in server

## Running the Application

### Using PHP Built-in Server (Development)

```bash
php -S localhost:8000
```

Then open your browser and navigate to:
```
http://localhost:8000
```

### Using Apache/Nginx

1. Configure your web server to serve files from this directory
2. Ensure PHP is enabled
3. Access via your configured domain/port

## Project Structure

```
/demand-profile/
├── index.php              # Main input form
├── process.php            # Processing logic
├── results.php            # Results display page
├── includes/
│   ├── config.php         # Configuration settings
│   ├── validator.php      # Input validation functions
│   ├── calculator.php     # Demand calculation engine
│   ├── csv_generator.php  # CSV file generation
│   └── data_fetcher.php   # External API integration
├── assets/
│   ├── css/
│   │   └── style.css      # Styling
│   └── js/
│       └── charts.js       # Chart rendering logic
└── temp/                  # Temporary CSV storage
```

## Usage

1. Enter your zip code (5-digit US zip code)
2. Enter your annual kWh usage
3. Select applicable options:
   - Air conditioning
   - Electric heating
   - Work from home
   - Electric vehicle charging
4. Submit the form
5. View seasonal demand profile graphs
6. Download the CSV file with 15-minute interval data

## Output Format

The CSV file follows the Green Button format standard:
- **Date:** YYYY-MM-DD
- **Time:** HH:MM (24-hour format)
- **Usage:** kWh (energy consumed during the 15-minute interval)

## Development

See the planning documents for detailed specifications:
- `PLAN.md` - Project plan and phases
- `REQUIREMENTS.md` - Detailed requirements
- `TECHNICAL_DESIGN.md` - Technical architecture and design
- `IMPLEMENTATION_CHECKLIST.md` - Implementation tasks

## License

[Add your license here]

## Author

[Add your name/info here]
