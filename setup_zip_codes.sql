-- ZIP Codes Database Schema for Enhanced HVAC Keyword Research
-- This table will store comprehensive location data for better keyword targeting

USE hvac_keywords;

-- ZIP codes table with comprehensive location data
CREATE TABLE IF NOT EXISTS zip_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zip_code VARCHAR(10) NOT NULL UNIQUE,
    city VARCHAR(100) NOT NULL,
    county VARCHAR(100) NOT NULL,
    state VARCHAR(50) NOT NULL,
    state_code VARCHAR(2) NOT NULL,
    area_description VARCHAR(200),
    
    -- Geographic data
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    timezone VARCHAR(50),
    
    -- Demographics (useful for HVAC targeting)
    population INT DEFAULT 0,
    median_income INT DEFAULT 0,
    avg_home_age INT DEFAULT 0,
    
    -- HVAC market data
    climate_zone VARCHAR(20),
    primary_heating VARCHAR(30),
    primary_cooling VARCHAR(30),
    
    -- Network/IP data for Google autocomplete
    suggested_ip VARCHAR(15),
    metro_area VARCHAR(100),
    
    -- Indexing
    INDEX idx_zip_code (zip_code),
    INDEX idx_state (state_code),
    INDEX idx_city (city),
    INDEX idx_metro (metro_area),
    INDEX idx_climate (climate_zone)
);

-- Add some sample data to get started
INSERT INTO zip_codes (zip_code, city, county, state, state_code, area_description, latitude, longitude, suggested_ip, climate_zone, primary_heating, primary_cooling, metro_area, population) VALUES

-- Major metropolitan areas with high HVAC demand
-- Los Angeles Area
('90001', 'Los Angeles', 'Los Angeles County', 'California', 'CA', 'South Los Angeles', 33.9731, -118.2479, '172.58.0.22', 'Hot-Dry', 'Natural Gas', 'Central AC', 'Los Angeles-Long Beach-Anaheim', 51223),
('90210', 'Beverly Hills', 'Los Angeles County', 'California', 'CA', 'Beverly Hills', 34.0901, -118.4065, '172.58.0.22', 'Hot-Dry', 'Natural Gas', 'Central AC', 'Los Angeles-Long Beach-Anaheim', 21792),
('90401', 'Santa Monica', 'Los Angeles County', 'California', 'CA', 'Santa Monica', 34.0195, -118.4912, '172.58.0.22', 'Marine', 'Natural Gas', 'Heat Pump', 'Los Angeles-Long Beach-Anaheim', 93076),

-- Miami Area (High AC demand)
('33101', 'Miami', 'Miami-Dade County', 'Florida', 'FL', 'Downtown Miami', 25.7617, -80.1918, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Miami-Fort Lauderdale-West Palm Beach', 25310),
('33139', 'Miami Beach', 'Miami-Dade County', 'Florida', 'FL', 'South Beach', 25.7907, -80.1300, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Miami-Fort Lauderdale-West Palm Beach', 87779),
('33406', 'West Palm Beach', 'Palm Beach County', 'Florida', 'FL', 'West Palm Beach', 26.7153, -80.0534, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Miami-Fort Lauderdale-West Palm Beach', 111398),

-- Phoenix Area (Extreme AC demand)
('85001', 'Phoenix', 'Maricopa County', 'Arizona', 'AZ', 'Central Phoenix', 33.4484, -112.0740, '67.221.186.34', 'Hot-Dry', 'Heat Pump', 'Central AC', 'Phoenix-Mesa-Scottsdale', 1608139),
('85260', 'Scottsdale', 'Maricopa County', 'Arizona', 'AZ', 'North Scottsdale', 33.6119, -111.8906, '67.221.186.34', 'Hot-Dry', 'Heat Pump', 'Central AC', 'Phoenix-Mesa-Scottsdale', 258069),

-- Dallas Area
('75201', 'Dallas', 'Dallas County', 'Texas', 'TX', 'Downtown Dallas', 32.7767, -96.7970, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Dallas-Fort Worth-Arlington', 1304379),
('75034', 'Frisco', 'Collin County', 'Texas', 'TX', 'Frisco', 33.1507, -96.8236, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Dallas-Fort Worth-Arlington', 200509),

-- Atlanta Area
('30301', 'Atlanta', 'Fulton County', 'Georgia', 'GA', 'Downtown Atlanta', 33.7490, -84.3880, '67.191.100.22', 'Mixed-Humid', 'Heat Pump', 'Central AC', 'Atlanta-Sandy Springs-Roswell', 498715),
('30309', 'Atlanta', 'Fulton County', 'Georgia', 'GA', 'Midtown Atlanta', 33.7839, -84.3826, '67.191.100.22', 'Mixed-Humid', 'Heat Pump', 'Central AC', 'Atlanta-Sandy Springs-Roswell', 498715),

-- Houston Area (High AC demand)
('77001', 'Houston', 'Harris County', 'Texas', 'TX', 'Downtown Houston', 29.7604, -95.3698, '50.235.128.45', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Houston-The Woodlands-Sugar Land', 2316797),
('77019', 'Houston', 'Harris County', 'Texas', 'TX', 'River Oaks', 29.7604, -95.3698, '50.235.128.45', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Houston-The Woodlands-Sugar Land', 2316797),

-- Chicago Area (High heating demand)
('60601', 'Chicago', 'Cook County', 'Illinois', 'IL', 'The Loop', 41.8781, -87.6298, '98.213.0.77', 'Cold', 'Natural Gas', 'Central AC', 'Chicago-Naperville-Elgin', 2693976),
('60614', 'Chicago', 'Cook County', 'Illinois', 'IL', 'Lincoln Park', 41.9178, -87.6439, '98.213.0.77', 'Cold', 'Natural Gas', 'Central AC', 'Chicago-Naperville-Elgin', 2693976),

-- New York Area
('10001', 'New York', 'New York County', 'New York', 'NY', 'Midtown Manhattan', 40.7505, -73.9934, '96.30.120.1', 'Mixed-Humid', 'Natural Gas', 'Window AC', 'New York-Newark-Jersey City', 8336817),
('11201', 'Brooklyn', 'Kings County', 'New York', 'NY', 'Brooklyn Heights', 40.6962, -73.9904, '96.30.120.1', 'Mixed-Humid', 'Natural Gas', 'Window AC', 'New York-Newark-Jersey City', 2736074),

-- Las Vegas (High AC demand)
('89101', 'Las Vegas', 'Clark County', 'Nevada', 'NV', 'Downtown Las Vegas', 36.1699, -115.1398, '198.148.79.1', 'Hot-Dry', 'Natural Gas', 'Central AC', 'Las Vegas-Henderson-Paradise', 641903),
('89119', 'Las Vegas', 'Clark County', 'Nevada', 'NV', 'South Las Vegas', 36.1162, -115.1360, '198.148.79.1', 'Hot-Dry', 'Natural Gas', 'Central AC', 'Las Vegas-Henderson-Paradise', 641903),

-- Tampa Area (Year-round AC)
('33602', 'Tampa', 'Hillsborough County', 'Florida', 'FL', 'Downtown Tampa', 27.9506, -82.4572, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Tampa-St. Petersburg-Clearwater', 384959),

-- Orlando Area (High AC demand)
('32801', 'Orlando', 'Orange County', 'Florida', 'FL', 'Downtown Orlando', 28.5383, -81.3792, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Orlando-Kissimmee-Sanford', 307573),

-- San Antonio (Hot climate)
('78201', 'San Antonio', 'Bexar County', 'Texas', 'TX', 'Downtown San Antonio', 29.4241, -98.4936, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'San Antonio-New Braunfels', 1547253),

-- Charlotte Area
('28202', 'Charlotte', 'Mecklenburg County', 'North Carolina', 'NC', 'Uptown Charlotte', 35.2271, -80.8431, '67.191.100.22', 'Mixed-Humid', 'Heat Pump', 'Central AC', 'Charlotte-Concord-Gastonia', 885708),

-- Denver Area (Heating demand)
('80202', 'Denver', 'Denver County', 'Colorado', 'CO', 'Downtown Denver', 39.7392, -104.9903, '67.215.65.1', 'Cold', 'Natural Gas', 'Central AC', 'Denver-Aurora-Lakewood', 715522),

-- Nashville Area
('37201', 'Nashville', 'Davidson County', 'Tennessee', 'TN', 'Downtown Nashville', 36.1627, -86.7816, '67.191.100.22', 'Mixed-Humid', 'Heat Pump', 'Central AC', 'Nashville-Davidson--Murfreesboro--Franklin', 689447),

-- Austin Area
('78701', 'Austin', 'Travis County', 'Texas', 'TX', 'Downtown Austin', 30.2672, -97.7431, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Austin-Round Rock', 978908),

-- Jacksonville
('32202', 'Jacksonville', 'Duval County', 'Florida', 'FL', 'Downtown Jacksonville', 30.3322, -81.6557, '198.41.160.12', 'Hot-Humid', 'Heat Pump', 'Central AC', 'Jacksonville', 949611),

-- Smaller markets but high HVAC demand
-- Tucson (Desert climate)
('85701', 'Tucson', 'Pima County', 'Arizona', 'AZ', 'Downtown Tucson', 32.2226, -110.9747, '67.221.186.34', 'Hot-Dry', 'Heat Pump', 'Central AC', 'Tucson', 548073),

-- Fresno (Central Valley heat)
('93701', 'Fresno', 'Fresno County', 'California', 'CA', 'Downtown Fresno', 36.7378, -119.7871, '172.58.0.22', 'Hot-Dry', 'Natural Gas', 'Central AC', 'Fresno', 542107),

-- Sacramento
('95814', 'Sacramento', 'Sacramento County', 'California', 'CA', 'Downtown Sacramento', 38.5816, -121.4944, '172.58.0.22', 'Hot-Dry', 'Natural Gas', 'Central AC', 'Sacramento--Roseville--Arden-Arcade', 513624);

-- Additional climate zones and smaller cities (add more as needed)