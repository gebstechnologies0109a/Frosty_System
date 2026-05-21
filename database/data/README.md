# Supply & spare part catalog import

Items from these document sections import as **`supply`** or **`sparepart`** with **0 points**:

| Document section | Frosty category |
|------------------|-----------------|
| supplies, cones, cups, utensils, ramen, beverages (non-softserve), canned drinks, frozen toppings | `supply` |
| spareparts, machine parts | `sparepart` |

## JSON format

Add rows to `supply_sparepart_catalog.json`:

```json
{
  "name": "Product name",
  "document_category": "cups",
  "points": 0,
  "prices": { "luzon": 100.0, "davao": 110.0, "tacloban": 110.0 }
}
```

Optional: set `"category": "supply"` or `"sparepart"` directly to override auto-mapping.

## Import

```bash
php artisan frosty:import-supply-catalog
php artisan frosty:import-supply-catalog path/to/your-catalog.json
```

Seeder also runs this file after formula products (softserve, yogurt, etc.).
