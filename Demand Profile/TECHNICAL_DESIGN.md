# Demand Profile - Technical Design Document

## 1. System Architecture

### 1.1 Overview
```
User Browser
    ↓
HTML Form (index.php)
    ↓
PHP Processing (process.php)
    ↓
├── Input Validation
├── Data Fetching (External APIs)
├── Demand Calculation Engine
├── CSV Generation
└── Graph Data Preparation
    ↓
Results Page (results.php)
    ├── Display 4 Seasonal Graphs (JavaScript/Chart.js)
    └── CSV Download Link
```

### 1.2 File Structure
```
/demand-profile/
├── index.php              # Main input form
├── process.php            # Processing logic
├── results.php            # Results display page
├── includes/
│   ├── config.php         # Configuration (API keys, etc.)
│   ├── validator.php      # Input validation functions
│   ├── calculator.php     # Demand calculation engine
│   ├── csv_generator.php  # CSV file generation
│   └── data_fetcher.php   # External API integration
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── charts.js      # Chart rendering logic
└── temp/                  # Temporary CSV storage
```

## 2. Demand Calculation Algorithm

### 2.1 Overview
The algorithm generates 15-minute interval demand data by:
1. Starting with a base residential load profile
2. Applying seasonal adjustments based on climate data
3. Adding AC load (if applicable) during summer months
4. Adding electric heating load (if applicable) during winter months
5. Applying work-from-home adjustments (if applicable) during daytime hours
6. Adding EV charging load (if applicable) during charging periods
7. Normalizing to match the user's annual kWh usage

### 2.2 Base Load Profile
- Use typical residential hourly load patterns
- Base load varies by hour of day (higher during evening)
- Base load varies by day of week (weekends different from weekdays)
- Typical pattern: Low overnight, rising in morning, moderate during day, peak in evening
- **If Work-From-Home = Yes:** Daytime load (9 AM - 5 PM) is higher and more consistent

### 2.3 Seasonal Adjustments

#### Spring (March, April, May)
- Moderate temperatures
- Base load profile with minimal heating/cooling
- Slight increase in evening usage

#### Summer (June, July, August)
- High temperatures
- Base load profile
- **If AC = Yes:** Add significant cooling load
  - Peak during hottest hours (typically 2 PM - 6 PM)
  - Higher on weekdays when home is occupied
  - Cooling degree days (CDD) based on temperature

#### Fall (September, October, November)
- Moderate to cool temperatures
- Base load profile
- Minimal heating/cooling needs

#### Winter (December, January, February)
- Low temperatures
- Base load profile
- **If Electric Heating = Yes:** Add significant heating load
  - Peak during coldest hours (typically early morning and evening)
  - Higher on weekdays when home is occupied
  - Heating degree days (HDD) based on temperature

### 2.4 Load Factor Application

#### AC Load (Summer Only, if AC = Yes)
```
AC_kW = base_kW * AC_multiplier * temperature_factor
```
- AC_multiplier: Factor based on home size (estimated from annual kWh)
- temperature_factor: Based on cooling degree days
- Applied during peak cooling hours

#### Electric Heating Load (Winter Only, if Electric Heating = Yes)
```
Heating_kW = base_kW * heating_multiplier * temperature_factor
```
- heating_multiplier: Factor based on home size (estimated from annual kWh)
- temperature_factor: Based on heating degree days
- Applied during peak heating hours

#### Work-From-Home Load Adjustment (All Seasons, if Work-From-Home = Yes)
```
WFH_kW = base_kW * WFH_multiplier (during work hours 9 AM - 5 PM)
```
- WFH_multiplier: Additional load factor for work-from-home (typically 1.2 - 1.5x base)
- Applied during work hours (9 AM - 5 PM, Monday-Friday)
- Reduces weekday/weekend variation
- May increase AC/heating usage during work hours if applicable
- Typical additions: Computer equipment, lighting, additional HVAC usage

#### EV Charging Load (All Seasons, if EV Charging = Yes)
```
EV_kW = charging_rate (during charging periods)
```
- Charging rate: Typically 3-11 kW depending on charger type (Level 1: 1-2 kW, Level 2: 7-11 kW)
- Charging periods: Typically overnight (10 PM - 6 AM) or evening (6 PM - 10 PM)
- Charging frequency: Daily or as needed based on usage patterns
- Charging duration: Typically 4-8 hours for full charge, varies by battery capacity and usage
- Applied during typical charging windows (evening return home or overnight)
- May vary by day of week (more consistent on weekdays)
- Typical pattern: Higher load in evening when returning home, or overnight charging

### 2.5 Normalization
After generating all 15-minute intervals:
1. Sum total kWh for the year
2. Calculate normalization factor: `factor = user_annual_kWh / calculated_annual_kWh`
3. Multiply all kW values by normalization factor
4. This ensures the generated profile matches the user's annual usage

## 3. Data Sources

### 3.1 Climate Data
**Option 1: NOAA Climate Data API**
- Free, publicly available
- Historical temperature data by location
- Can map zip code to weather station

**Option 2: OpenWeatherMap API**
- Requires API key (free tier available)
- Current and historical weather data
- Zip code lookup supported

**Option 3: Static Climate Zone Data**
- Pre-loaded climate zone data
- Zip code to climate zone mapping
- Typical temperature profiles by climate zone

### 3.2 Load Profile Data
**Option 1: Use Standard Load Profiles**
- Typical residential hourly load patterns
- Can be hardcoded or loaded from file
- Adjust based on annual kWh usage

**Option 2: Generate from Typical Patterns**
- Base hourly pattern (percentage of daily total)
- Apply to daily totals
- Adjust for day of week

### 3.3 Implementation Approach
1. Start with static/climate zone data (no API dependency)
2. Later enhance with real-time API data if needed
3. Cache climate data to reduce API calls

## 4. CSV Generation

### 4.1 Format - Green Button Standard
The CSV file follows the Green Button format standard:
```csv
Date,Time,Usage
2024-01-01,00:00,0.6125
2024-01-01,00:15,0.5950
2024-01-01,00:30,0.5800
...
```

**Column Specifications:**
- **Date:** YYYY-MM-DD format (e.g., 2024-01-01)
- **Time:** HH:MM format, 24-hour time (e.g., 00:00, 00:15, 23:45)
- **Usage:** Energy consumed in kWh (kilowatt-hours) during the 15-minute interval

**Important:** Green Button format uses **kWh (energy)**, not kW (power). 
- For 15-minute intervals: `kWh = kW × 0.25 hours`
- Example: If demand is 2.45 kW for a 15-minute interval, Usage = 2.45 × 0.25 = 0.6125 kWh

### 4.2 Implementation
- Generate array of 15-minute intervals for full year
- Calculate kW demand for each interval
- Convert kW to kWh: `kWh = kW × 0.25` (for 15-minute intervals)
- Format each row with Date, Time, and Usage (kWh) columns
- Write to CSV file following Green Button format
- Store temporarily or generate on-the-fly
- Provide download link
- Ensure proper CSV formatting (comma-separated, proper escaping if needed)

## 5. Graph Generation

### 5.1 Chart Library Selection
**Recommended: Chart.js**
- Easy to use
- Good documentation
- Supports heatmaps and line charts
- Can customize axes

**Alternative: D3.js**
- More flexible
- Steeper learning curve
- Better for complex visualizations

### 5.2 Data Preparation
For each season:
1. Aggregate 15-minute data by hour
2. Calculate average kW for each hour (0-23)
3. Prepare data array for chart library
4. Format: `[{hour: 0, kW: 2.5}, {hour: 1, kW: 2.3}, ...]`

### 5.3 Graph Configuration
- **X-axis:** kW (horizontal)
- **Y-axis:** Hours (0-23, vertical)
- **Type:** Line chart or heatmap
- **Orientation:** Hours start at midnight (0) on left
- **Styling:** Clear labels, readable font, distinct colors per season

### 5.4 Implementation
```javascript
// Example Chart.js configuration
const chartConfig = {
  type: 'line',
  data: {
    labels: [0, 1, 2, ..., 23], // Hours
    datasets: [{
      label: 'Average kW',
      data: [2.5, 2.3, ..., 3.2], // kW values
    }]
  },
  options: {
    scales: {
      x: { title: { display: true, text: 'kW' } },
      y: { title: { display: true, text: 'Hour of Day' } }
    }
  }
};
```

## 6. Input Validation

### 6.1 Zip Code
```php
function validateZipCode($zip) {
    // Check format: 5 digits
    if (!preg_match('/^\d{5}$/', $zip)) {
        return false;
    }
    // Optional: Validate against known zip codes
    return true;
}
```

### 6.2 Annual kWh
```php
function validateAnnualKWh($kwh) {
    // Must be numeric
    if (!is_numeric($kwh)) {
        return false;
    }
    // Must be positive
    if ($kwh <= 0) {
        return false;
    }
    // Reasonable range check (e.g., 1,000 - 50,000 kWh)
    if ($kwh < 1000 || $kwh > 50000) {
        return false; // or show warning
    }
    return true;
}
```

### 6.3 Checkboxes
- Simple boolean check (checked = true, unchecked = false)
- Default to false if not set
- Checkboxes include: AC, Electric Heating, Work From Home, EV Charging

## 7. Security Considerations

### 7.1 Input Sanitization
- Sanitize all user inputs
- Use `filter_var()` and `htmlspecialchars()`
- Validate before processing

### 7.2 File Downloads
- Generate CSV files securely
- Use proper headers for download
- Clean up temporary files
- Prevent path traversal attacks

### 7.3 API Keys
- Store API keys in config file (not in version control)
- Use environment variables if possible
- Rate limit API calls

## 8. Performance Optimization

### 8.1 Caching
- Cache climate data by zip code
- Cache load profiles
- Use file-based or in-memory cache

### 8.2 Processing
- Generate CSV efficiently (stream if possible)
- Pre-calculate seasonal averages for graphs
- Use efficient data structures

### 8.3 Frontend
- Lazy load charts
- Use async loading for JavaScript libraries
- Optimize chart rendering

## 9. Error Handling

### 9.1 Input Errors
- Display clear error messages
- Highlight invalid fields
- Prevent form submission until valid

### 9.2 Processing Errors
- Handle API failures gracefully
- Provide fallback data if API unavailable
- Log errors for debugging

### 9.3 Output Errors
- Validate CSV generation
- Handle chart rendering failures
- Provide alternative output if needed

## 10. Testing Strategy

### 10.1 Unit Tests
- Input validation functions
- Demand calculation functions
- CSV generation functions

### 10.2 Integration Tests
- End-to-end form submission
- API integration
- File download functionality

### 10.3 Test Cases
- Valid inputs → correct outputs
- Invalid zip code → error message
- Invalid kWh → error message
- AC only → summer peak visible
- Heating only → winter peak visible
- Both AC and heating → both peaks visible
- Neither AC nor heating → base load only
- Work-from-home only → higher daytime load visible
- Work-from-home + AC → higher daytime summer load
- Work-from-home + heating → higher daytime winter load
- EV charging only → overnight/evening charging load visible
- EV charging + AC → summer load with evening charging
- EV charging + heating → winter load with evening charging
- All inputs → combined effects visible

## 11. Future Enhancements
- Database storage for generated profiles
- User accounts to save profiles
- Export to Excel format
- Custom date range selection
- Multiple home comparison
- Historical data comparison
- More granular climate data integration
