# Changelog



## [vV1.2.0] - 2026-02-10
- (New) Product Variation, connected to product unit and kit
- (New) Add Product Variation view to Customer side product view
- (New) Implementation Product Variation to cart, confirmation, admin rentals, and pdf generation
- (Bug Fix) Rental validation logic fix when kit is lost or broken
- (Bug Fix) Admin customer save changes button now working as expected
- (Tweak) Admin customer validation button now always visible, admin can bypass customer document
- (Tweak) Admin Products Page better table view: default layout 48 product per page, better search engine, add filter by brand and categories

## [vV1.1.0] - 2026-02-09
- (New) CMS for Post and Pages with tag and category
- (New) Navigation menu configuration

## [vV1.0.0] - 2026-02-08
- First build of Gearent
- Rental feature with live unit stock detection
- Calendar with table and kanban view
- Customer registration group with document verification
- Inventory system, product with unit for quantity and unit kit
- Maintenance & QC for inventory management system
- Quotation and sales for every rental booked
- Discount voucher code type for customer discount
- Total admin control at setting. Apparance, document layout, WhatsApp, registration, and many more
- Customer frontend ordering system, connected live directly to rental admin panel
- Live unit availability at date pick, robust rental date checking, anti double booking

# Use command: php artisan make:version
# Use command: php artisan make:version #VERSION
# Use command: php artisan make:version --message="Fix bug login|Tambah fitur export PDF|Update tampilan dashboard"
