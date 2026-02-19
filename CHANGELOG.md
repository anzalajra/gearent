# Changelog














## [v1.3.6] - 2026-02-19
- (New) Product Unit duplicate function, make admin easeier to make duplicate unit with different serial number
- (Tweak) Add "Partial Return" status view at schedule calendar

## [v1.3.5] - 2026-02-18
- (New) Rental return operation now support "Partial Return"
- (New) When partial return happened, new delivery check-in will generated, validated product will marked as returned and other else will stay rented

## [v1.3.4] - 2026-02-16
- (New) Rental product buffer time, now admin can set product buffer time before next rental
- (New) Frontend order calendar can detect order return time. in return day, date will marked half circle. Customer can order on the same date as previous rental

## [v1.3.3] - 2026-02-13
- (Bug Fix) Backup & Restore now working and can be restored
- (Bug Fix) System checkup now can work
- (Tweak) Document layout setting now have document preview

## [v1.3.2] - 2026-02-13
- (New) Add function to edit active rental, but limited to edit discount and deposit
- (Bug Fix) Discount function between fixed and percentage discount now fixed
- (Tweak) Removed "cancel confirmed" at rentals table

## [v1.3.1] - 2026-02-13
- (New) Admin now can resolve product conflict by removing conflicted product at another rental schedule
- (New) Better logic to prevent double booking, add auto switch if there any product unit conflict
- (Bug Fix) Overall system now detect customer as user_id, changed from customer_id. Effected on all system code
- (Bug Fix) Confirmed order not detected on frontend customer product calendar caused by 'confirmed' status is not registered
- (Bug Fix) Admin panel problem at rental edit now removed 'user is_active' filter
- (Tweak) Removed old navigation setting

## [v1.3.0] - 2026-02-12
- (New) Add Admin & Roles system at admin. Now you can add admin with customized role permission
- (New) Customer can be added as admin. So now same user can interact as customer and admin in same account
- (Tweak) All system now use "user_id" to identify both customer and admin

## [v1.2.4] - 2026-02-11
- (Tweak) Add new filter at product catalog on frontend customer

## [v1.2.3] - 2026-02-11
- (Bug Fix) Product variation on customer frontend order showing not valid
- (Bug Fix) Customer can't edit quantity on cart

## [v1.2.2] - 2026-02-11
- (Bug Fix) Network Error on frontend product with variant
- (Bug Fix) Rental edit failed to get product unit serial number on admin
- (Tweak) Status on rental have more better colorway on calendar view and global
- (Tweak) Re-arrange admin new rental layout

## [v1.2.1] - 2026-02-10
- (Bug Fix) TypeError happend at product page when adding new product
- (Tweak) Change product setup layout at brands and categories tab
- (Tweak) Add edit function at rentals tab on confirmed order

## [v1.2.0] - 2026-02-10
- (New) Product Variation, connected to product unit and kit
- (New) Add Product Variation view to Customer side product view
- (New) Implementation Product Variation to cart, confirmation, admin rentals, and pdf generation
- (Bug Fix) Rental validation logic fix when kit is lost or broken
- (Bug Fix) Admin customer save changes button now working as expected
- (Tweak) Admin customer validation button now always visible, admin can bypass customer document
- (Tweak) Admin Products Page better table view: default layout 48 product per page, better search engine, add filter by brand and categories

## [v1.1.0] - 2026-02-09
- (New) CMS for Post and Pages with tag and category
- (New) Navigation menu configuration

## [v1.0.0] - 2026-02-08
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
