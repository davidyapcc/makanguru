# Restaurant Scraper Web UI Guide

## Overview

The MakanGuru Restaurant Scraper now has a beautiful, mobile-first web interface built with Livewire 3! Access it at `/scraper` to import restaurants via your browser instead of the command line.

---

## Accessing the UI

### Local Development
```
http://127.0.0.1:8000/scraper
```

### Production
```
https://yourdomain.com/scraper
```

### Navigation
- From the **Chat Interface**: Click "🌐 Scraper" in the header
- From the **Scraper**: Click "← Back to Chat" to return

---

## UI Features

### 1. Left Panel: Scraping Controls

#### 📍 Area Selection
- **Dropdown menu** with 7 pre-configured Malaysian areas:
  - Kuala Lumpur
  - Petaling Jaya
  - Bangsar
  - KLCC
  - Damansara
  - Subang Jaya
  - Shah Alam

#### 📏 Radius Slider
- **Interactive range slider**: 1km to 15km
- **Real-time display**: Shows selected radius (e.g., "5.0km")
- **Recommended**:
  - Urban areas: 2-5km
  - Suburban areas: 5-10km
  - Wide searches: 10-15km

#### 🔢 Max Results Slider
- **Interactive range slider**: 10 to 200 results
- **Real-time display**: Shows selected limit (e.g., "50")
- **Recommended**:
  - Preview mode: 10-50 results
  - Production import: 50-100 results
  - Bulk import: 100-200 results

#### 🔍 Preview Mode Toggle
- **Checkbox**: Enable/disable preview mode
- **Default**: ON (safe for first-time users)
- **When ON**: Results are displayed but NOT saved to database
- **When OFF**: Results are automatically imported

#### Action Buttons
- **Primary Button**: Changes based on mode
  - Preview Mode ON: "🔍 Preview Restaurants"
  - Preview Mode OFF: "⬇️ Import Restaurants"
  - Shows loading spinner during scraping
- **Clear Results Button**: Appears after scraping, clears displayed results

### 2. Database Stats Panel

Real-time statistics from your current database:
- **Total Restaurants**: Count of all places
- **Halal Options**: Count of halal-certified restaurants
- **Areas Covered**: Number of unique areas

### 3. Right Panel: Results Display

#### Success/Error Messages
- **Success (Green)**: ✅ Displays scraping/import success with details
- **Error (Red)**: ❌ Shows error messages if scraping fails

#### Results Statistics (when found > 0)
Three stat cards showing:
1. **Found**: Number of restaurants scraped
2. **Saved**: Number successfully imported (import mode only)
3. **Duplicates**: Number skipped (import mode only)

#### Results Table
Beautiful, scrollable table with:
- **Restaurant Name** + description preview
- **Area**: Location
- **Cuisine**: Cuisine type
- **Price**: Badge-colored (green=budget, yellow=moderate, red=expensive)
- **Halal**: ✓ or ✗ indicator
- **Sticky header**: Stays visible while scrolling
- **Max height**: 600px with vertical scroll
- **Hover effects**: Row highlights on hover

#### Empty State
When no results yet:
- **Large icon**: 🍜
- **Helpful message**: Instructions to get started
- **Quick Tips card**:
  - Start with Preview Mode
  - Use smaller radius for urban areas
  - Limit to 50-100 results
  - Check Database Stats

---

## User Workflow

### First-Time User (Safe Exploration)

1. **Visit** `/scraper`
2. **Keep Preview Mode ON** (default)
3. **Select an area** (e.g., "Bangsar")
4. **Adjust radius** (e.g., 3km)
5. **Set limit** (e.g., 20 results)
6. **Click** "🔍 Preview Restaurants"
7. **Wait** for scraping (3-10 seconds)
8. **Review** the table results
9. **Toggle Preview Mode OFF** if satisfied
10. **Click** "⬇️ Import Restaurants"
11. **Check Database Stats** for updated count

### Experienced User (Direct Import)

1. **Visit** `/scraper`
2. **Toggle Preview Mode OFF**
3. **Select area** (e.g., "KLCC")
4. **Set radius** (e.g., 5km)
5. **Set limit** (e.g., 100)
6. **Click** "⬇️ Import Restaurants"
7. **Monitor progress** (loading spinner)
8. **View results** (Found/Saved/Duplicates)
9. **Check Database Stats**

### Bulk Import Workflow

1. **Import Kuala Lumpur** (radius: 5km, limit: 100)
2. **Click "Clear Results"**
3. **Import KLCC** (radius: 3km, limit: 50)
4. **Click "Clear Results"**
5. **Import Bangsar** (radius: 3km, limit: 50)
6. **Check Database Stats**: Should show ~200 total

---

## UI/UX Features

### Mobile-First Design
- ✅ **Responsive grid**: 1 column on mobile, 3 columns on desktop
- ✅ **Touch-friendly sliders**: Easy to adjust on mobile
- ✅ **Readable table**: Horizontal scroll on small screens
- ✅ **Large tap targets**: All buttons and controls

### Visual Feedback
- ✅ **Loading spinner**: Animated during scraping
- ✅ **Disabled states**: Buttons disabled while loading
- ✅ **Hover effects**: Visual feedback on interactive elements
- ✅ **Color-coded badges**: Instant price range recognition
- ✅ **Success/Error alerts**: Clear messaging

### Performance
- ✅ **Livewire 3**: Fast reactive updates
- ✅ **Wire:loading**: Instant loading states
- ✅ **No page refresh**: SPA-like experience
- ✅ **Optimistic UI**: Immediate feedback

### Accessibility
- ✅ **Semantic HTML**: Proper table structure
- ✅ **ARIA labels**: Screen reader friendly
- ✅ **Keyboard navigation**: Tab through controls
- ✅ **High contrast**: Readable text

---

## Component Architecture

### Livewire Component
**File**: `app/Livewire/ScraperInterface.php`

**Properties**:
```php
public string $selectedArea = 'Kuala Lumpur'
public int $radius = 5000
public int $limit = 50
public bool $previewMode = true
public bool $isScrapingNow = false
public array $scrapedRestaurants = []
public array $stats = ['found' => 0, 'saved' => 0, 'duplicates' => 0]
```

**Methods**:
- `startScraping()`: Main scraping logic
- `saveRestaurants()`: Database import logic
- `clearResults()`: Reset state
- `getAvailableAreas()`: Return area list
- `getDatabaseStats()`: Real-time DB stats

### Blade View
**File**: `resources/views/livewire/scraper-interface.blade.php`

**Structure**:
- Header with navigation
- Left panel: Controls + Stats
- Right panel: Results + Messages

**Styling**:
- Malaysian color palette (matching chat UI)
- Tailwind CSS v4
- Gradient backgrounds
- Card-based layout

---

## Integration with Existing Features

### 1. Uses Existing Service Layer
```php
// Same service as CLI command
app/Services/RestaurantScraperService.php
```

### 2. Saves to Same Database
```php
// Place model with all scopes
App\Models\Place
```

### 3. Works with Chat Interface
After importing restaurants:
```
1. Go to Chat (click "← Back to Chat")
2. Ask: "Where to eat in KLCC?"
3. AI will use newly scraped restaurants
```

### 4. Compatible with Filters
Scraped restaurants support:
- ✅ Halal filter
- ✅ Price filter
- ✅ Area filter
- ✅ Tag search

---

## Comparison: CLI vs Web UI

| Feature | CLI Command | Web UI |
|---------|------------|--------|
| **Access** | Terminal | Browser |
| **Preview Mode** | `--dry-run` flag | Toggle checkbox |
| **Area Selection** | `--area="KLCC"` | Dropdown menu |
| **Radius** | `--radius=5000` | Interactive slider |
| **Limit** | `--limit=50` | Interactive slider |
| **Progress** | Text progress bar | Loading spinner |
| **Results** | Terminal table | HTML table |
| **Stats** | Text output | Visual cards |
| **Mobile Support** | ❌ | ✅ |
| **Visual Feedback** | Limited | Rich |
| **Ease of Use** | Advanced users | All users |

**Recommendation**:
- **CLI**: For automation, scripts, cron jobs
- **Web UI**: For manual imports, exploration, non-technical users

---

## Troubleshooting

### Issue: "No Results Yet" stays displayed

**Symptoms**:
- Clicked "Preview Restaurants"
- Loading spinner appears
- Returns to "No Results Yet"

**Solutions**:
1. Check browser console for errors (F12)
2. Verify Overpass API is reachable
3. Try a different area (e.g., "Kuala Lumpur")
4. Reduce radius to 3km
5. Reduce limit to 10

### Issue: "Scraping failed" error

**Symptoms**:
Red error message appears

**Solutions**:
1. Check internet connection
2. Wait 60 seconds (Overpass API cooldown)
3. Try smaller limit (e.g., 20)
4. Check logs: `storage/logs/laravel.log`

### Issue: All restaurants show as duplicates

**Symptoms**:
- Import completes
- "Saved: 0, Duplicates: 50"

**Solutions**:
1. This is expected if you've already imported these restaurants
2. Try a different area
3. Increase radius to find new restaurants
4. Clear results and try preview mode to verify

### Issue: Sliders not moving

**Symptoms**:
Range sliders appear stuck

**Solutions**:
1. Hard refresh browser (Ctrl+F5 / Cmd+Shift+R)
2. Clear browser cache
3. Try different browser
4. Check if JavaScript is enabled

---

## Screenshots (Description)

Since we can't embed images in markdown, here's what the UI looks like:

### Desktop View (1920x1080)
```
┌─────────────────────────────────────────────────────────────┐
│ 🌐 Restaurant Scraper    [← Back to Chat]                  │
│ Import real restaurant data from OpenStreetMap              │
├────────────────────┬────────────────────────────────────────┤
│ Scraping Settings  │ Results Display                         │
│                    │                                         │
│ [Area Dropdown]    │ ┌─────────────────────────────────────┐│
│ [Radius Slider]    │ │ Success! Found 50 restaurants       ││
│ [Limit Slider]     │ └─────────────────────────────────────┘│
│ [Preview Toggle]   │                                         │
│ [Preview Button]   │ ┌──────┬──────┬──────┐                 │
│                    │ │ 50   │ 45   │  5   │                 │
│ Database Stats     │ │Found │Saved │Dupes │                 │
│ Total: 100         │ └──────┴──────┴──────┘                 │
│ Halal: 20          │                                         │
│ Areas: 5           │ [Table with restaurants...]             │
└────────────────────┴────────────────────────────────────────┘
```

### Mobile View (375x667)
```
┌──────────────────────┐
│ 🌐 Restaurant Scraper│
│ [← Back]             │
├──────────────────────┤
│ Scraping Settings    │
│ [Area Dropdown]      │
│ [Radius: 5.0km]      │
│ [───────o────]       │
│ [Limit: 50]          │
│ [───────o────]       │
│ ☑ Preview Mode       │
│ [Preview Restaurants]│
│                      │
│ Database Stats       │
│ Total: 100           │
│ Halal: 20            │
├──────────────────────┤
│ Results              │
│ [Success message]    │
│ [Stats cards]        │
│ [Results table]      │
└──────────────────────┘
```

---

## Best Practices

### For Users

1. **Always start with Preview Mode** to verify results
2. **Use smaller radius** (2-5km) for faster scraping
3. **Limit to 50-100** for reasonable response times
4. **Check Database Stats** before and after import
5. **Clear results** between different area imports

### For Developers

1. **Service injection**: UI uses same service as CLI
2. **Validation**: Client-side (HTML5) + server-side (Livewire)
3. **Error handling**: Try-catch with user-friendly messages
4. **Logging**: All errors logged to Laravel log
5. **Type safety**: Full PHP 8.4 type hints

---

## Future Enhancements

Potential improvements for v2:

1. **Map View**: Display scraped restaurants on interactive map
2. **Filters**: Pre-filter by halal, cuisine, price before scraping
3. **Batch Import**: Queue multiple areas for sequential scraping
4. **Export**: Download results as CSV/JSON
5. **History**: Track past scraping sessions
6. **Scheduling**: Set up automated weekly imports
7. **Analytics**: Charts showing import trends
8. **Bulk Edit**: Edit multiple restaurants after import

---

## Support

For issues or questions:
1. Check [Troubleshooting](#troubleshooting) section
2. Review logs: `storage/logs/laravel.log`
3. Check browser console (F12)
4. Test with CLI: `php artisan makanguru:scrape --dry-run`
5. Open GitHub issue with error details

---

*Last Updated: 2025-12-19*
*Part of MakanGuru Phase 5: OpenStreetMap Integration*
