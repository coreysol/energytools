# Demand Profile - Project Plan

## Project Overview
**Project Name:** Demand Profile  
**Status:** Planning Phase  
**Last Updated:** January 24, 2026

**Description:** A PHP-based web application that generates residential electricity demand profiles based on user inputs and publicly available data.

## Goals & Objectives
- [x] Define core functionality and features
- [ ] Establish technical architecture
- [ ] Set up development environment
- [ ] Implement MVP (Minimum Viable Product)
- [ ] Deploy and iterate

## Phase 1: Discovery & Planning
### Tasks
- [x] Define project requirements
- [ ] Identify target users/stakeholders
- [ ] Research similar solutions
- [ ] Define success metrics
- [ ] Create user stories/use cases
- [ ] Technical feasibility assessment
- [ ] Research publicly available data sources (weather APIs, load profile data)

### Deliverables
- Requirements document (see Requirements section below)
- User personas
- Technical architecture diagram
- Project timeline
- Data source documentation

## Phase 2: Design & Architecture
### Tasks
- [ ] System architecture design
- [ ] Database schema design (if applicable)
- [ ] API design (if applicable)
- [ ] UI/UX wireframes (if applicable)
- [ ] Technology stack selection
- [ ] Development environment setup

### Deliverables
- Architecture documentation
- Database schema
- API specifications
- Design mockups

## Phase 3: Development Setup
### Tasks
- [ ] Initialize project repository
- [ ] Set up development environment
- [ ] Configure build tools
- [ ] Set up testing framework
- [ ] Configure CI/CD pipeline
- [ ] Set up code quality tools (linters, formatters)

### Deliverables
- Working development environment
- Project structure
- Build configuration
- Test framework

## Phase 4: Core Development
### Tasks
- [ ] Create user input form (HTML/PHP)
- [ ] Implement form validation (zip code, kWh, checkboxes)
- [ ] Develop demand calculation algorithm
- [ ] Integrate weather/climate data API
- [ ] Implement seasonal profile generation logic
- [ ] Create CSV generation function (15-minute intervals)
- [ ] Implement chart rendering (4 seasonal graphs)
- [ ] Build download functionality for CSV
- [ ] Apply AC load factors (summer months)
- [ ] Apply electric heating load factors (winter months)
- [ ] Apply work-from-home load adjustments (daytime hours)
- [ ] Apply EV charging load adjustments (overnight/evening periods)

### Deliverables
- Working input form
- Demand calculation engine
- CSV generation module
- Chart rendering module
- Working prototype
- Unit tests
- Integration tests

## Phase 5: Testing & Quality Assurance
### Tasks
- [ ] Unit testing
- [ ] Integration testing
- [ ] End-to-end testing
- [ ] Performance testing
- [ ] Security testing
- [ ] User acceptance testing

### Deliverables
- Test coverage report
- Bug fixes
- Performance benchmarks
- Security audit report

## Phase 6: Deployment & Launch
### Tasks
- [ ] Production environment setup
- [ ] Deployment configuration
- [ ] Monitoring and logging setup
- [ ] Documentation
- [ ] User training (if applicable)
- [ ] Launch

### Deliverables
- Deployed application
- Documentation
- Monitoring dashboard
- Launch checklist

## Phase 7: Post-Launch & Iteration
### Tasks
- [ ] Monitor performance
- [ ] Gather user feedback
- [ ] Bug fixes and patches
- [ ] Feature enhancements
- [ ] Performance optimization

### Deliverables
- Analytics reports
- Feature updates
- Performance improvements

## Technical Considerations

### Technology Stack
- **Backend:** PHP (server-side processing)
- **Frontend:** HTML, CSS, JavaScript (form handling, chart rendering)
- **Charting Library:** Chart.js, D3.js, or similar (for seasonal demand profile graphs)
- **Data Processing:** PHP (CSV generation, demand calculations)
- **External Data Sources:** Weather APIs, typical load profile databases
- **Hosting/Deployment:** PHP-compatible web server (Apache/Nginx)

### Key Features
1. **User Input Form**
   - Zip code input (validation required)
   - Annual kWh usage input (numeric validation)
   - Air conditioning checkbox (yes/no)
   - Electric heating checkbox (yes/no)
   - Work from home checkbox (yes/no)
   - Electric vehicle charging checkbox (yes/no)

2. **Demand Profile Generation**
   - Calculate 15-minute interval demand data for full year
   - Generate seasonal profiles (Spring, Summer, Fall, Winter)
   - Apply AC and electric heating load factors based on user inputs
   - Apply work-from-home adjustments (increased daytime load, reduced weekday/weekend variation)
   - Apply EV charging load adjustments (typically overnight or evening charging periods)

3. **Output Generation**
   - CSV file download with 15-minute interval data
   - Four seasonal demand profile graphs:
     - X-axis: kW usage
     - Y-axis: Hours of day (0-23, starting at midnight on left)
     - One graph each for Spring, Summer, Fall, Winter

4. **Data Integration**
   - Fetch weather/climate data based on zip code
   - Apply typical residential load profiles
   - Adjust for AC usage (summer peak demand)
   - Adjust for electric heating (winter peak demand)
   - Adjust for work-from-home patterns (daytime load increase)
   - Adjust for EV charging patterns (overnight/evening charging load)

## Risks & Mitigation
- **Risk 1:** _Description_ - Mitigation: _Strategy_
- **Risk 2:** _Description_ - Mitigation: _Strategy_

## Timeline
- **Phase 1:** _Start Date_ - _End Date_
- **Phase 2:** _Start Date_ - _End Date_
- **Phase 3:** _Start Date_ - _End Date_
- **Phase 4:** _Start Date_ - _End Date_
- **Phase 5:** _Start Date_ - _End Date_
- **Phase 6:** _Start Date_ - _End Date_
- **Phase 7:** _Ongoing_

## Resources Needed
- Development team
- Design resources (if applicable)
- Infrastructure/hosting
- Third-party services/APIs (if applicable)

## Requirements Specification

### Functional Requirements

#### FR1: User Input Collection
- **FR1.1:** System shall accept zip code input (5-digit US zip code format)
- **FR1.2:** System shall accept annual kWh usage (numeric, positive value)
- **FR1.3:** System shall accept air conditioning status (boolean: yes/no)
- **FR1.4:** System shall accept electric heating status (boolean: yes/no)
- **FR1.5:** System shall accept work-from-home status (boolean: yes/no)
- **FR1.6:** System shall accept electric vehicle charging status (boolean: yes/no)
- **FR1.7:** System shall validate all inputs before processing

#### FR2: Demand Profile Calculation
- **FR2.1:** System shall generate 15-minute interval demand data for a full year (35,040 data points)
- **FR2.2:** System shall use publicly available data (weather/climate) based on zip code
- **FR2.3:** System shall apply typical residential load profiles
- **FR2.4:** System shall adjust demand for AC usage (primarily summer months)
- **FR2.5:** System shall adjust demand for electric heating (primarily winter months)
- **FR2.6:** System shall adjust demand for work-from-home patterns (increased daytime load, reduced weekday/weekend variation)
- **FR2.7:** System shall adjust demand for EV charging patterns (typically overnight or evening charging periods)
- **FR2.8:** System shall normalize total annual usage to match user-provided annual kWh

#### FR3: Output Generation
- **FR3.1:** System shall generate CSV file with 15-minute interval data in Green Button format
  - Columns: Date, Time, Usage (kWh)
  - Format: Green Button CSV standard (comma-separated)
  - Usage values in kWh (energy consumed during interval)
  - Convert kW to kWh: kWh = kW × 0.25 hours (for 15-minute intervals)
  - Downloadable file
- **FR3.2:** System shall generate four seasonal demand profile graphs:
  - Spring profile (March, April, May)
  - Summer profile (June, July, August)
  - Fall profile (September, October, November)
  - Winter profile (December, January, February)
- **FR3.3:** Graph specifications:
  - X-axis: kW usage (demand)
  - Y-axis: Hours of day (0-23)
  - Display: Hours start at midnight (00:00) on the far left
  - Type: Heatmap or line chart showing average/typical demand pattern

### Non-Functional Requirements
- **NFR1:** Application shall be built using PHP
- **NFR2:** Application shall be accessible via web browser
- **NFR3:** CSV generation shall complete within reasonable time (< 30 seconds)
- **NFR4:** Graphs shall render clearly and be readable
- **NFR5:** Application shall handle invalid inputs gracefully

### Data Sources (To Be Researched)
- Weather/climate APIs (e.g., NOAA, OpenWeatherMap)
- Typical residential load profile databases
- Zip code to climate zone mapping
- Seasonal temperature data

## Technical Architecture

### Components
1. **Input Form Handler** (PHP)
   - Collect and validate user inputs
   - Sanitize inputs for security

2. **Demand Calculator** (PHP)
   - Core algorithm for demand profile generation
   - Seasonal adjustments
   - AC and heating load factors

3. **Data Fetcher** (PHP)
   - Interface with external APIs
   - Cache climate/weather data
   - Load profile data retrieval

4. **CSV Generator** (PHP)
   - Format 15-minute interval data
   - Generate downloadable file

5. **Chart Renderer** (JavaScript + Chart Library)
   - Generate seasonal demand profile graphs
   - Format axes correctly (kW on X, hours on Y)

6. **Output Handler** (PHP)
   - Serve CSV download
   - Display graphs on page

## Notes
- Need to research publicly available data sources for:
  - Weather/climate data by zip code
  - Typical residential load profiles
  - Seasonal demand patterns
- Algorithm must normalize annual usage to match user input
- Consider caching weather data to reduce API calls
- Graph orientation: X-axis = kW, Y-axis = Hours (0-23, midnight on left)

---

## Next Steps
1. Research and select data sources/APIs
2. Design demand calculation algorithm
3. Create project structure
4. Set up PHP development environment
5. Begin Phase 3: Development Setup
