# Demand Profile - Implementation Checklist

## Phase 1: Project Setup
- [ ] Set up PHP development environment
- [ ] Create project directory structure
- [ ] Initialize Git repository (if using version control)
- [ ] Set up local web server (Apache/Nginx with PHP)
- [ ] Create basic file structure (index.php, includes/, assets/)

## Phase 2: Input Form Development
- [ ] Create HTML form in index.php
  - [ ] Zip code input field
  - [ ] Annual kWh input field
  - [ ] Air conditioning checkbox
  - [ ] Electric heating checkbox
  - [ ] Work from home checkbox
  - [ ] Electric vehicle charging checkbox
  - [ ] Submit button
- [ ] Add CSS styling for form
- [ ] Implement client-side validation (JavaScript)
- [ ] Create form handler (process.php)

## Phase 3: Input Validation (PHP)
- [ ] Create validator.php
- [ ] Implement zip code validation
- [ ] Implement annual kWh validation
- [ ] Implement checkbox handling
- [ ] Add error handling and messages
- [ ] Sanitize all inputs

## Phase 4: Data Source Integration
- [ ] Research and select climate data source
- [ ] Create data_fetcher.php
- [ ] Implement zip code to climate zone mapping
- [ ] Implement temperature data retrieval
- [ ] Add caching mechanism for climate data
- [ ] Create fallback for API failures

## Phase 5: Load Profile Data
- [ ] Define base residential load profile
- [ ] Create hourly load pattern template
- [ ] Implement day-of-week variations
- [ ] Store load profile data (file or code)

## Phase 6: Demand Calculation Engine
- [ ] Create calculator.php
- [ ] Implement base load profile application
- [ ] Implement seasonal adjustments
- [ ] Implement AC load calculation (summer)
- [ ] Implement electric heating load calculation (winter)
- [ ] Implement work-from-home load adjustment (daytime hours, weekdays)
- [ ] Implement EV charging load calculation (overnight/evening periods)
- [ ] Implement normalization algorithm
- [ ] Generate 15-minute interval data for full year
- [ ] Test calculation accuracy

## Phase 7: CSV Generation
- [ ] Create csv_generator.php
- [ ] Format 15-minute interval data in Green Button format
- [ ] Convert kW to kWh for each interval (kWh = kW × 0.25 hours)
- [ ] Generate CSV file structure with Green Button columns (Date, Time, Usage)
- [ ] Implement file download functionality
- [ ] Add proper headers for CSV download
- [ ] Test CSV format and content (verify Green Button compliance)

## Phase 8: Graph Data Preparation
- [ ] Aggregate 15-minute data by hour for each season
- [ ] Calculate average kW per hour for each season
- [ ] Format data for chart library
- [ ] Prepare data arrays (Spring, Summer, Fall, Winter)

## Phase 9: Chart Implementation
- [ ] Select and include chart library (Chart.js recommended)
- [ ] Create charts.js file
- [ ] Implement Spring demand profile graph
- [ ] Implement Summer demand profile graph
- [ ] Implement Fall demand profile graph
- [ ] Implement Winter demand profile graph
- [ ] Configure axes correctly (X = kW, Y = Hours)
- [ ] Style charts appropriately
- [ ] Ensure hours start at midnight on left

## Phase 10: Results Page
- [ ] Create results.php
- [ ] Display four seasonal graphs
- [ ] Add CSV download link/button
- [ ] Style results page
- [ ] Add loading indicators
- [ ] Handle errors gracefully

## Phase 11: Integration & Testing
- [ ] Test complete flow (form → processing → results)
- [ ] Test with various inputs
- [ ] Verify CSV download works
- [ ] Verify graphs display correctly
- [ ] Test with AC only
- [ ] Test with heating only
- [ ] Test with both AC and heating
- [ ] Test with neither AC nor heating
- [ ] Test with work-from-home only
- [ ] Test with work-from-home + AC
- [ ] Test with work-from-home + heating
- [ ] Test with EV charging only
- [ ] Test with EV charging + AC
- [ ] Test with EV charging + heating
- [ ] Test with EV charging + work-from-home
- [ ] Test with all inputs combined
- [ ] Verify annual kWh normalization
- [ ] Test edge cases (very high/low kWh, invalid zip codes)

## Phase 12: Security & Performance
- [ ] Review and harden input validation
- [ ] Implement proper sanitization
- [ ] Secure file downloads
- [ ] Optimize calculation performance
- [ ] Implement caching where appropriate
- [ ] Test with large datasets

## Phase 13: Documentation
- [ ] Document code with comments
- [ ] Create user documentation
- [ ] Document API/data source usage
- [ ] Create README.md
- [ ] Document installation/setup instructions

## Phase 14: Deployment Preparation
- [ ] Test on production-like environment
- [ ] Configure production settings
- [ ] Set up error logging
- [ ] Prepare deployment checklist
- [ ] Document deployment process

## Quick Reference: Key Files to Create

### Core Files
1. `index.php` - Main input form
2. `process.php` - Form processing and calculation
3. `results.php` - Results display page

### Include Files
4. `includes/config.php` - Configuration settings
5. `includes/validator.php` - Input validation
6. `includes/calculator.php` - Demand calculation engine
7. `includes/csv_generator.php` - CSV file generation
8. `includes/data_fetcher.php` - External data fetching

### Asset Files
9. `assets/css/style.css` - Styling
10. `assets/js/charts.js` - Chart rendering

### Documentation
11. `README.md` - Project documentation
12. `PLAN.md` - Project plan (already created)
13. `REQUIREMENTS.md` - Requirements (already created)
14. `TECHNICAL_DESIGN.md` - Technical design (already created)

## Notes
- Start with MVP: basic functionality working end-to-end
- Iterate and refine based on testing
- Consider starting with static climate data before integrating APIs
- Test calculation accuracy with known inputs/outputs
