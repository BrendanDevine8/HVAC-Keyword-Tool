# API Integration Setup Guide

Your ZIP code database has been enhanced and is ready for API integration! Here are the **FREE** APIs you can integrate:

## 🌡️ **NOAA Climate Data API** (FREE)
**Best for**: Heating/cooling degree days, official climate data
- **Sign up**: https://www.ncdc.noaa.gov/cdo-web/webservices/v2
- **Free tier**: 1000 requests/day
- **Data**: Official US government climate data
- **Setup**: Get free token, add to `enrich_zip_codes.php`

```php
// In enrich_zip_codes.php, replace:
'token: YOUR_NOAA_TOKEN' 
// With your actual token:
'token: abcd1234yourtoken'
```

## 🏠 **US Census Bureau API** (FREE)  
**Best for**: Demographics, housing data, income levels
- **Sign up**: https://api.census.gov/data/key_signup.html
- **Free tier**: Unlimited (government API)
- **Data**: Population, income, housing age, home values
- **Setup**: Optional key (works without it)

## ⚡ **Energy Information Administration API** (FREE)
**Best for**: Energy costs, heating fuel preferences  
- **Sign up**: https://www.eia.gov/opendata/register.php
- **Free tier**: Unlimited (government API)
- **Data**: Electricity rates, natural gas prices, energy consumption
- **Setup**: Get free key, add to `enrich_zip_codes.php`

```php
// Replace:
$apiKey = "YOUR_EIA_API_KEY";
// With:
$apiKey = "your_actual_eia_key";
```

## 🌤️ **OpenWeatherMap API** (Freemium)
**Best for**: Current weather patterns, humidity data
- **Sign up**: https://openweathermap.org/api
- **Free tier**: 1000 calls/day
- **Data**: Temperature, humidity, weather conditions  
- **Setup**: Add key to constructor

```php
// When calling the enricher:
$enricher = new ZipCodeEnricher($pdo, 'your_openweather_key');
```

## 📊 **Enhanced Data You'll Get:**

### Climate Intelligence
- **Heating Degree Days**: Quantify heating demand
- **Cooling Degree Days**: Quantify cooling demand  
- **Temperature Averages**: Winter/summer temps
- **Humidity Index**: Regional moisture levels

### Market Intelligence  
- **Demographics**: Income, population, housing density
- **Housing Data**: Home age, values, rental rates
- **Energy Market**: Electricity/gas rates by region
- **HVAC Opportunity Score**: AI-calculated market potential

### Automated Benefits
- **Climate-Aware Keywords**: Better targeting by weather patterns
- **Economic Targeting**: Focus on high-income areas
- **Seasonal Optimization**: Boost campaigns during peak seasons
- **Market Sizing**: Identify high-opportunity ZIP codes

## 🚀 **Quick Start:**

1. **Get API Keys** (5 minutes):
   - NOAA: https://www.ncdc.noaa.gov/cdo-web/webservices/v2
   - EIA: https://www.eia.gov/opendata/register.php
   - OpenWeather: https://openweathermap.org/api

2. **Add Keys to Code**:
   - Edit `enrich_zip_codes.php` 
   - Replace placeholder tokens with real ones

3. **Run Enrichment**:
   ```
   http://localhost/hvac-tool/enrich_zip_codes.php?action=enrich&limit=100
   ```

4. **Monitor Progress**:
   - Check logs for API responses
   - Verify data quality in database
   - Scale up batch sizes

## 💡 **Pro Tips:**

- **Start Small**: Test with 10-20 ZIP codes first
- **Rate Limiting**: APIs have limits, respect them
- **Batch Processing**: Process in chunks to avoid timeouts
- **Error Handling**: Monitor API responses for issues
- **Data Validation**: Verify data quality before scaling

The system is **already configured** to estimate data when APIs aren't available, so your keyword tool will work immediately while you set up the integrations!