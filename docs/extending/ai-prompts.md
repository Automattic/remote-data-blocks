# Prompts and guidance for using AI

AI assistants can significantly speed up the development process for extending the Remote Data Blocks WordPress plugin. This document provides a set of prompts and templates to help you create data sources and queries.

Before diving in, make sure you have a good understanding of the [core concepts](../concepts/index.md) of Remote Data Blocks as well as the fundamentals of how and why to [extend the plugin](index.md). This includes understanding how data sources and queries work as well as the overall architecture of the plugin.

## Context

When using AI to generate code, it's crucial to provide clear and detailed context. To assist you, we compile all of the documentation and examples into a single file: [`/docs/for-ai.md`](../for-ai.md). Make sure to include this file as context in your AI prompts.

This repo also supplies [Cursor rules](https://docs.cursor.com/context/rules) that can guide plugin contributors towards writing high-quality code. If you don't use Cursor, you might consider creating similar rules for your AI assistant of choice.

## Prompts

### REST API

Register a remote data block that displays weather data from an API. Please use the documentation in `/docs/for-ai.md` as context. Requirements:

- Connects to the weather REST API at `https://api.weather.com/v1/current`
- Uses bearer token authentication (the token should be stored in a constant called `WEATHER_API_TOKEN`)
- Accepts a location input (city name or zip code)
- Returns weather data including:
  - Current temperature in Celcius and Fahrenheit
  - Weather description
  - Humidity percentage
  - A calculated field called "Will it rain today?" (boolean, derived from precipitation probability > 30%)

The API response format looks like this:

```json
{
	"location": "London, UK",
	"temperature": 72,
	"description": "Partly cloudy",
	"humidity": 65,
	"precipitation_probability": 15
}
```

Please create a new file named `example/private/weather-block.php` with the complete PHP code and update `remote-data-blocks.php` to require this file.

### Airtable block

Register a remote data block that displays rows from an Airtable base. Please use the documentation in `/docs/for-ai.md` as context. Requirements:

- Connects to an Airtable base containing product information
- Uses the Airtable integration provided by the plugin
- Displays specific fields from a table called "Products":
  - Product Name (mapped to "Name" field in Airtable)
  - Price (mapped to "Price" field in Airtable)
  - Description (mapped to "Description" field in Airtable)
  - Product Image (mapped to "Image" field in Airtable)
  - Category (mapped to "Category" field in Airtable)
- Includes both single product display and list/search functionality
- Uses placeholders for the actual Airtable credentials (access token, base ID, table ID)

Please create a new file named `example/private/airtable-product-block.php` with the complete PHP code and update `remote-data-blocks.php` to require this file.

### Google Sheets block

Register a remote data block that displays rows from a Google Sheet. Please use the documentation in `/docs/for-ai.md` as context. Requirements:

Create a remote data block that:

- Connects to a Google Sheet containing employee directory information
- Uses the Google Sheets integration provided by the plugin
- Displays specific fields from a sheet called "Employees":
  - Full Name (mapped to "Name" column)
  - Job Title (mapped to "Title" column)
  - Department (mapped to "Department" column)
  - Email Address (mapped to "Email" column)
  - Office Location (mapped to "Location" column)
- Includes both individual employee display and list functionality for browsing all employees
- Uses placeholders for the actual Google credentials (service account JSON, spreadsheet ID, sheet ID)

Please create a new file named `example/private/employee-block.php` with the complete PHP code and update `remote-data-blocks.php` to require this file.
