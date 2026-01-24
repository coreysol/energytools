# Demand Profile - Requirements Document

## 1. Project Overview
A PHP-based web application that generates residential electricity demand profiles based on user inputs and publicly available data sources.

## 2. User Inputs

### 2.1 Zip Code
- **Type:** Text input (5-digit US zip code)
- **Validation:** 
  - Must be 5 digits
  - Must be valid US zip code format
  - Required field
- **Purpose:** Used to fetch location-specific climate/weather data

### 2.2 Annual kWh Usage
- **Type:** Numeric input
- **Validation:**
  - Must be a positive number
  - Must be greater than 0
  - Required field
- **Purpose:** Total annual electricity consumption to normalize the demand profile

### 2.3 Air Conditioning
- **Type:** Checkbox (Boolean: Yes/No)
- **Validation:** Optional (defaults to No)
- **Purpose:** Adjusts summer demand profile to account for AC load

### 2.4 Electric Heating
- **Type:** Checkbox (Boolean: Yes/No)
- **Validation:** Optional (defaults to No)
- **Purpose:** Adjusts winter demand profile to account for electric heating load

### 2.5 Work From Home
- **Type:** Checkbox (Boolean: Yes/No)
- **Validation:** Optional (defaults to No)
- **Purpose:** Adjusts daytime demand profile to account for work-from-home load patterns
  - Increases daytime usage (typically 9 AM - 5 PM)
  - Reduces difference between weekday and weekend patterns
  - Adds base load during work hours (computers, lighting, etc.)
  - May increase AC/heating usage during work hours

### 2.6 Electric Vehicle Charging
- **Type:** Checkbox (Boolean: Yes/No)
- **Validation:** Optional (defaults to No)
- **Purpose:** Adjusts demand profile to account for EV charging load
  - Adds significant load during charging periods (typically 3-11 kW)
  - Typically occurs overnight (late evening/early morning) or during off-peak hours
  - May occur when returning home from work (evening hours)
  - Charging duration and frequency varies by usage patterns

## 3. Outputs

### 3.1 CSV File (15-Minute Interval Demand Data)
- **Format:** Green Button CSV format (comma-separated values)
- **Content:**
  - Date (YYYY-MM-DD format)
  - Time (HH:MM format, 24-hour)
  - Usage (kWh - energy consumed during the 15-minute interval)
- **Frequency:** 15-minute intervals for full year (35,040 data points)
- **Delivery:** Downloadable file
- **File Naming:** `demand_profile_[timestamp].csv` or similar
- **Note:** Green Button format uses kWh (energy) not kW (power). For 15-minute intervals, kWh = kW × 0.25 hours

### 3.2 Seasonal Demand Profile Graphs
Four separate graphs, one for each season:

#### 3.2.1 Graph Specifications
- **Type:** Heatmap or line chart (to be determined)
- **X-Axis:** kW usage (demand)
- **Y-Axis:** Hours of day (0-23)
- **Orientation:** Hours start at midnight (00:00) on the far left
- **Display:** Show typical/average demand pattern for the season

#### 3.2.2 Seasonal Definitions
- **Spring:** March, April, May
- **Summer:** June, July, August
- **Fall:** September, October, November
- **Winter:** December, January, February

#### 3.2.3 Graph Requirements
- Each graph should clearly show the demand pattern throughout the day
- Graphs should be visually distinct and labeled
- Should display average or typical hourly demand for the season
- Should account for AC usage (summer) and electric heating (winter)

## 4. Functional Requirements

### 4.1 Input Processing
- System must validate all inputs before processing
- System must sanitize inputs to prevent security issues
- System must provide clear error messages for invalid inputs

### 4.2 Demand Calculation
- System must use publicly available data sources:
  - Weather/climate data based on zip code
  - Typical residential load profiles
- System must generate 15-minute interval data for entire year
- System must normalize total annual usage to match user-provided annual kWh
- System must apply seasonal adjustments:
  - AC load factors for summer months (if AC = Yes)
  - Electric heating load factors for winter months (if Electric Heating = Yes)
- System must apply work-from-home adjustments:
  - Increased daytime load (9 AM - 5 PM) if work-from-home = Yes
  - Reduced weekday/weekend variation if work-from-home = Yes
  - Additional base load during work hours if work-from-home = Yes
- System must apply EV charging adjustments:
  - Add charging load during typical charging periods if EV charging = Yes
  - Account for charging duration and frequency patterns
  - Typically overnight or evening charging periods

### 4.3 Data Sources
- System must fetch location-specific climate data based on zip code
- System must use typical residential load profile patterns
- System should cache data where possible to improve performance

### 4.4 Output Generation
- System must generate CSV file in Green Button format with required columns (Date, Time, Usage in kWh)
- System must convert kW demand to kWh for each 15-minute interval (kWh = kW × 0.25 hours)
- System must generate four seasonal graphs with correct axis orientation
- System must provide download functionality for CSV
- System must display graphs on the web page

## 5. Technical Requirements

### 5.1 Technology Stack
- **Backend:** PHP
- **Frontend:** HTML, CSS, JavaScript
- **Charting:** JavaScript charting library (Chart.js, D3.js, or similar)
- **Server:** PHP-compatible web server

### 5.2 Performance
- CSV generation should complete within reasonable time (< 30 seconds)
- Page load should be responsive
- Graphs should render quickly

### 5.3 Security
- Input validation and sanitization
- Protection against SQL injection (if database used)
- Protection against XSS attacks
- Secure file downloads

## 6. Data Requirements

### 6.1 External Data Sources Needed
1. **Climate/Weather Data**
   - Temperature data by zip code
   - Seasonal temperature patterns
   - Cooling degree days (for AC)
   - Heating degree days (for electric heating)

2. **Load Profile Data**
   - Typical residential hourly load patterns
   - Base load profiles
   - Peak demand patterns

### 6.2 Data Processing
- Map zip code to climate zone
- Retrieve seasonal temperature data
- Apply load profile templates
- Adjust for AC and heating based on user inputs
- Adjust for work-from-home patterns if applicable
- Adjust for EV charging patterns if applicable
- Normalize to match annual kWh usage

## 7. User Interface Requirements

### 7.1 Input Form
- Clean, intuitive form layout
- Clear labels for all inputs
- Input validation feedback
- Submit button

### 7.2 Results Display
- Display four seasonal graphs clearly
- Provide download button/link for CSV
- Show processing status/loading indicator
- Display any errors or warnings

## 8. Assumptions
- Users are located in the United States (US zip codes)
- Annual kWh usage is accurate
- AC and heating are binary (yes/no) - no partial usage
- Work-from-home is binary (yes/no) - at least one household member
- EV charging is binary (yes/no) - at least one EV charged at home
- Demand profiles follow typical residential patterns
- 15-minute intervals are sufficient granularity

## 9. Out of Scope (For Now)
- Multi-year analysis
- Custom time period selection
- Export to other formats (Excel, JSON, etc.)
- Historical data comparison
- Multiple home profiles
- User accounts/saving profiles

## 10. Success Criteria
- System generates accurate 15-minute interval demand data
- Total annual kWh in generated data matches user input (within acceptable tolerance)
- Seasonal graphs correctly display demand patterns
- CSV file is properly formatted and downloadable
- Application handles edge cases gracefully
- Performance is acceptable for typical use
