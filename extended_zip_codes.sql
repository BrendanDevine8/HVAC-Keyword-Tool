-- Extended ZIP Codes Data - Part 2
-- Additional ZIP codes for comprehensive HVAC market coverage

USE hvac_keywords;

-- Continue inserting more ZIP codes
INSERT INTO zip_codes (zip_code, city, county, state, state_code, area_description, latitude, longitude, suggested_ip, climate_zone, primary_heating, primary_cooling, metro_area, population) VALUES

-- More California markets (huge HVAC demand)
('92101', 'San Diego', 'San Diego County', 'CA', 'CA', 'Downtown San Diego', 32.7157, -117.1611, '172.58.0.22', 'Marine', 'Heat Pump', 'Central AC', 'San Diego-Carlsbad', 1423851),
('92602', 'Irvine', 'Orange County', 'CA', 'CA', 'Irvine', 33.6846, -117.8265, '172.58.0.22', 'Marine', 'Natural Gas', 'Central AC', 'Los Angeles-Long Beach-Anaheim', 307670),
('94102', 'San Francisco', 'San Francisco County', 'CA', 'CA', 'Downtown San Francisco', 37.7749, -122.4194, '172.58.0.22', 'Marine', 'Natural Gas', 'Heat Pump', 'San Francisco-Oakland-Hayward', 881549),
('94301', 'Palo Alto', 'Santa Clara County', 'CA', 'CA', 'Palo Alto', 37.4419, -122.1430, '172.58.0.22', 'Marine', 'Natural Gas', 'Heat Pump', 'San Francisco-Oakland-Hayward', 68572),
('95134', 'San Jose', 'Santa Clara County', 'CA', 'CA', 'North San Jose', 37.4088, -121.9233, '172.58.0.22', 'Hot-Dry', 'Natural Gas', 'Central AC', 'San Francisco-Oakland-Hayward', 1021795),

-- More Florida (Year-round AC market)
('33301', 'Fort Lauderdale', 'Broward County', 'FL', 'FL', 'Downtown Fort Lauderdale', 26.1224, -80.1373, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Miami-Fort Lauderdale-West Palm Beach', 182760),
('34102', 'Naples', 'Collier County', 'FL', 'FL', 'Downtown Naples', 26.1420, -81.7948, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Naples-Immokalee-Marco Island', 22088),
('34236', 'Sarasota', 'Sarasota County', 'FL', 'FL', 'Sarasota', 27.3364, -82.5307, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'North Port-Sarasota-Bradenton', 57738),

-- More Texas markets
('77098', 'Houston', 'Harris County', 'TX', 'TX', 'West University', 29.7604, -95.3698, '50.235.128.45', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Houston-The Woodlands-Sugar Land', 2316797),
('75225', 'Dallas', 'Dallas County', 'TX', 'TX', 'University Park', 32.7767, -96.7970, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Dallas-Fort Worth-Arlington', 1304379),
('76102', 'Fort Worth', 'Tarrant County', 'TX', 'TX', 'Downtown Fort Worth', 32.7555, -97.3308, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Dallas-Fort Worth-Arlington', 918915),

-- Southeast markets (Mixed heating/cooling)
('30309', 'Atlanta', 'Fulton County', 'GA', 'GA', 'Midtown Atlanta', 33.7839, -84.3826, '67.191.100.22', 'Mixed-Humid', 'Heat Pump', 'Central AC', 'Atlanta-Sandy Springs-Roswell', 498715),
('29401', 'Charleston', 'Charleston County', 'SC', 'SC', 'Downtown Charleston', 32.7765, -79.9311, '67.191.100.22', 'Hot-Humid', 'Heat Pump', 'Central AC', 'Charleston-North Charleston', 150227),
('27601', 'Raleigh', 'Wake County', 'NC', 'NC', 'Downtown Raleigh', 35.7796, -78.6382, '67.191.100.22', 'Mixed-Humid', 'Heat Pump', 'Central AC', 'Raleigh', 474069),

-- Northeast markets (High heating demand)
('02101', 'Boston', 'Suffolk County', 'MA', 'MA', 'Downtown Boston', 42.3601, -71.0589, '96.30.120.1', 'Cold', 'Natural Gas', 'Central AC', 'Boston-Cambridge-Newton', 685094),
('19102', 'Philadelphia', 'Philadelphia County', 'PA', 'PA', 'Center City', 39.9526, -75.1652, '96.30.120.1', 'Mixed-Humid', 'Natural Gas', 'Central AC', 'Philadelphia-Camden-Wilmington', 1584064),
('20001', 'Washington', 'District of Columbia', 'DC', 'DC', 'Downtown DC', 38.9072, -77.0369, '96.30.120.1', 'Mixed-Humid', 'Natural Gas', 'Central AC', 'Washington-Arlington-Alexandria', 705749),

-- More Midwest markets
('48201', 'Detroit', 'Wayne County', 'MI', 'MI', 'Downtown Detroit', 42.3314, -83.0458, '98.213.0.77', 'Cold', 'Natural Gas', 'Central AC', 'Detroit-Warren-Dearborn', 670031),
('55101', 'Saint Paul', 'Ramsey County', 'MN', 'MN', 'Downtown Saint Paul', 44.9537, -93.0900, '98.213.0.77', 'Very-Cold', 'Natural Gas', 'Central AC', 'Minneapolis-St. Paul-Bloomington', 308096),
('53202', 'Milwaukee', 'Milwaukee County', 'WI', 'WI', 'Downtown Milwaukee', 43.0389, -87.9065, '98.213.0.77', 'Cold', 'Natural Gas', 'Central AC', 'Milwaukee-Waukesha-West Allis', 594833),

-- Mountain/Desert markets (Extreme temperatures)
('84101', 'Salt Lake City', 'Salt Lake County', 'UT', 'UT', 'Downtown Salt Lake City', 40.7608, -111.8910, '67.215.65.1', 'Cold', 'Natural Gas', 'Central AC', 'Salt Lake City', 200544),
('87101', 'Albuquerque', 'Bernalillo County', 'NM', 'NM', 'Downtown Albuquerque', 35.0844, -106.6504, '67.215.65.1', 'Hot-Dry', 'Natural Gas', 'Evap Cooling', 'Albuquerque', 564559),
('83702', 'Boise', 'Ada County', 'ID', 'ID', 'Downtown Boise', 43.6150, -116.2023, '67.215.65.1', 'Cold', 'Natural Gas', 'Central AC', 'Boise City', 235684),

-- Pacific Northwest (Mild but growing AC market)
('98101', 'Seattle', 'King County', 'WA', 'WA', 'Downtown Seattle', 47.6062, -122.3321, '50.235.128.45', 'Marine', 'Natural Gas', 'Heat Pump', 'Seattle-Tacoma-Bellevue', 737015),
('97201', 'Portland', 'Multnomah County', 'OR', 'OR', 'Downtown Portland', 45.5152, -122.6784, '50.235.128.45', 'Marine', 'Natural Gas', 'Heat Pump', 'Portland-Vancouver-Hillsboro', 652503),

-- Additional high-growth suburbs and markets
('75024', 'Plano', 'Collin County', 'TX', 'TX', 'West Plano', 33.0198, -96.6989, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Dallas-Fort Worth-Arlington', 288061),
('90210', 'Beverly Hills', 'Los Angeles County', 'CA', 'CA', 'Beverly Hills', 34.0901, -118.4065, '172.58.0.22', 'Hot-Dry', 'Natural Gas', 'Central AC', 'Los Angeles-Long Beach-Anaheim', 32701),
('33480', 'Palm Beach', 'Palm Beach County', 'FL', 'FL', 'Palm Beach', 26.7056, -80.0364, '198.41.160.12', 'Very-Hot-Humid', 'Heat Pump', 'Central AC', 'Miami-Fort Lauderdale-West Palm Beach', 8348),

-- Secondary markets with growth potential
('65101', 'Jefferson City', 'Cole County', 'MO', 'MO', 'Jefferson City', 38.5767, -92.1735, '98.213.0.77', 'Mixed-Humid', 'Natural Gas', 'Central AC', 'Jefferson City', 43228),
('70112', 'New Orleans', 'Orleans Parish', 'LA', 'LA', 'French Quarter', 29.9511, -90.0715, '50.235.128.45', 'Very-Hot-Humid', 'Natural Gas', 'Central AC', 'New Orleans-Metairie', 390144),
('40202', 'Louisville', 'Jefferson County', 'KY', 'KY', 'Downtown Louisville', 38.2527, -85.7585, '67.191.100.22', 'Mixed-Humid', 'Natural Gas', 'Central AC', 'Louisville', 617638),

-- Small cities but important HVAC markets
('44101', 'Cleveland', 'Cuyahoga County', 'OH', 'OH', 'Downtown Cleveland', 41.4993, -81.6944, '98.213.0.77', 'Cold', 'Natural Gas', 'Central AC', 'Cleveland-Elyria', 383793),
('23220', 'Richmond', 'Richmond City', 'VA', 'VA', 'Downtown Richmond', 37.5407, -77.4360, '67.191.100.22', 'Mixed-Humid', 'Heat Pump', 'Central AC', 'Richmond', 230436),
('72201', 'Little Rock', 'Pulaski County', 'AR', 'AR', 'Downtown Little Rock', 34.7465, -92.2896, '50.235.128.45', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Little Rock-North Little Rock-Conway', 198606),

-- Additional desert/hot markets
('89801', 'Elko', 'Elko County', 'NV', 'NV', 'Elko', 40.8324, -115.7631, '198.148.79.1', 'Cold', 'Natural Gas', 'Central AC', 'Elko', 20564),
('73301', 'Austin', 'Travis County', 'TX', 'TX', 'South Austin', 30.2672, -97.7431, '104.32.0.11', 'Hot-Humid', 'Natural Gas', 'Central AC', 'Austin-Round Rock', 978908);

-- Add more ZIP codes as needed for specific markets