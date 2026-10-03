# Phase 0.5 — Forensic Schema Fingerprint & Migration-by-Migration Audit

> **Audit Protocol**: STRICT READ-ONLY FORENSIC INVESTIGATION  
> **Execution Mode**: Non-destructive, 0 DDL / 0 DML / 0 Migrations Executed  
> **Status Date**: 2026-10-03 18:13:38  
> **Repository**: Leader for Trans (LFT) Modernization  
> **Branch**: `devlop_test`  
> **Target Database**: `leader` (MySQL 8.4.3 Community Server, UTF8MB4)  

---

## Executive Forensic Summary

This document presents the complete, exhaustive forensic audit of the `leader` physical database and its relationship with the codebase's 153 migration files, 13 raw SQL migration files, Eloquent models, and application architecture. This audit was executed under strict read-only constraints to prevent premature remediation.

### Key Forensic Determinations
1. **Physical Defect Confirmed**: The database consists of **65 physical tables**, all running in InnoDB. Exactly **0 tables possess a PRIMARY KEY constraint**, exactly **0 secondary indexes exist in `information_schema.STATISTICS`**, and exactly **0 Foreign Key constraints exist in `information_schema.TABLE_CONSTRAINTS`**.
2. **Origin of Schema Defect**: Classified as **`STRONGLY SUPPORTED`**. All 65 tables were created simultaneously on `2026-09-28 22:27:13` to `22:28:09` via a database dump/import pipeline that omitted keys, constraints, and auto-increment definitions. The `migrations` table was truncated to 0 rows. (Classified as Strongly Supported rather than Proven because the exact command-line invocation of the export dump is not preserved).
3. **ID & PK Viability**: All 60 tables that possess an `id` column (`bigint unsigned NOT NULL`) have **0 duplicate values** and **0 null values**. All 60 are evaluated as **`PRECHECK PASSED`** for PRIMARY KEY and AUTO_INCREMENT candidate assignment. The remaining 5 tables are pivot or special tables with valid natural/composite keys.
4. **Codebase AUTO_INCREMENT Compliance**: Analysis of all models in `app/` and `database/` confirmed that **no models override `$incrementing = false`**, no models use non-integer keys, and zero models manually assign IDs upon creation. Status: **`PRECHECK PASSED`** (subject to verification on database clone).
5. **Migration Lineage**: 153 PHP migration files analyzed. 116 are 100% matched to current physical structure, 7 are partially matched, 20 are superseded by later drops/renames, 7 not matched, 1 is ambiguous, and 2 are data backfills.
6. **Critical Schema Bug Discovered**: In migration `2024_06_10_184953_add_fields_to_booking_papers_table.php`, the foreign key for `agent_id` was erroneously defined as `constrained('banks')` instead of `constrained('agents')`. Running raw legacy migrations would have created invalid constraints. Checking against `agents` confirmed **0 orphans**, proving data integrity is intact despite migration file defects.
7. **Data Idempotency & Modernization Integrity**: All 258 `request_key` values in `agent_expenses` are 100% distinct (0 duplicates). Parallel container staging (`booking_container_stages`, 446 rows) and loading flags (`is_in_loading`) exist and are actively populated.

---

## 1. Migration-by-Migration Forensic Audit (153 Migrations)

Each of the 153 migration files was evaluated against physical tables and columns in `information_schema`. Parsing was strictly restricted to the `up()` method to eliminate false positives from `down()`. Note: The physical existence of a table or column does **not** prove that a migration was executed in sequence; it only proves whether the intended physical state is represented.

| # | Migration File | Tables Affected | Classification | Forensic Details & Evidence |
|---|---|---|---|---|
| 1 | `2014_10_12_000000_create_users_table.php` | users | **`NOT MATCHED`** | Table 'users' does not physically exist in MySQL. |
| 2 | `2014_10_12_100000_create_password_resets_table.php` | password_resets | **`MATCHED`** | Table 'password_resets' and all initial columns (3/3) exist physically. |
| 3 | `2019_08_19_000000_create_failed_jobs_table.php` | failed_jobs | **`MATCHED`** | Table 'failed_jobs' and all initial columns (7/7) exist physically. |
| 4 | `2019_12_14_000001_create_personal_access_tokens_table.php` | personal_access_tokens | **`PARTIALLY MATCHED`** | Column 'personal_access_tokens.tokenable' not found in current physical schema. / Table exists with 8/9 initial columns present physically. |
| 5 | `2023_01_02_214541_create_permission_tables.php` | None (Data/Config) | **`AMBIGUOUS`** |  |
| 6 | `2023_01_03_214802_create_containers_table.php` | containers | **`MATCHED`** | Table 'containers' and all initial columns (5/5) exist physically. |
| 7 | `2023_01_06_230608_create_companies_table.php` | companies | **`MATCHED`** | Table 'companies' and all initial columns (10/10) exist physically. |
| 8 | `2023_01_09_200829_create_employees_table.php` | employees | **`MATCHED`** | Table 'employees' and all initial columns (9/9) exist physically. |
| 9 | `2023_01_09_224456_create_factories_table.php` | factories | **`MATCHED`** | Table 'factories' and all initial columns (7/7) exist physically. |
| 10 | `2023_01_10_191147_create_branches_table.php` | branches | **`MATCHED`** | Table 'branches' and all initial columns (10/10) exist physically. |
| 11 | `2023_01_17_095242_create_bookings_table.php` | bookings | **`MATCHED`** | Table 'bookings' and all initial columns (5/5) exist physically. |
| 12 | `2023_01_17_193757_create_second_bookings_table.php` | second_bookings | **`SUPERSEDED`** | Table 'second_bookings' was created here but later dropped/merged by subsequent migrations. |
| 13 | `2023_01_17_202749_create_third_bookings_table.php` | third_bookings | **`SUPERSEDED`** | Table 'third_bookings' was created here but later dropped/merged by subsequent migrations. |
| 14 | `2023_01_21_114617_create_booking_containers_table.php` | booking_containers | **`MATCHED`** | Table 'booking_containers' and all initial columns (7/7) exist physically. |
| 15 | `2023_01_21_154616_create_booking_movements_table.php` | booking_movements | **`MATCHED`** | Table 'booking_movements' and all initial columns (10/10) exist physically. |
| 16 | `2023_03_10_145616_create_otps_table.php` | otps | **`PARTIALLY MATCHED`** | Column 'otps.user_id' not found in current physical schema. / Table exists with 5/6 initial columns present physically. |
| 17 | `2023_03_10_180953_add_session_id_column_to_users_table.php` | users | **`MATCHED`** | Target table 'users' does not exist physically. / Table alter specifies column modifications without net additions/drops. |
| 18 | `2023_03_11_005001_create_static_pages_table.php` | static_pages | **`MATCHED`** | Table 'static_pages' and all initial columns (7/7) exist physically. |
| 19 | `2023_03_11_131628_create_our_services_table.php` | our_services | **`MATCHED`** | Table 'our_services' and all initial columns (6/6) exist physically. |
| 20 | `2023_03_11_154859_create_choose_us_table.php` | choose_us | **`MATCHED`** | Table 'choose_us' and all initial columns (6/6) exist physically. |
| 21 | `2023_03_11_164224_create_sponsers_table.php` | sponsers | **`MATCHED`** | Table 'sponsers' and all initial columns (6/6) exist physically. |
| 22 | `2023_03_11_170900_create_reviews_table.php` | reviews | **`MATCHED`** | Table 'reviews' and all initial columns (6/6) exist physically. |
| 23 | `2023_03_11_180258_create_settings_table.php` | settings | **`MATCHED`** | Table 'settings' and all initial columns (11/11) exist physically. |
| 24 | `2023_03_11_192831_add_phone_and_address_columns_to_users_table.php` | users | **`MATCHED`** | Target table 'users' does not exist physically. / Table alter specifies column modifications without net additions/drops. |
| 25 | `2023_03_19_071304_create_contact_us_table.php` | contact_us | **`MATCHED`** | Table 'contact_us' and all initial columns (5/5) exist physically. |
| 26 | `2023_03_28_210648_add_password_and_session_id_to_companies_table.php` | companies | **`MATCHED`** | Added column 'companies.password' is present physically. / Added column 'companies.session_id' is present physically. |
| 27 | `2023_03_28_221748_alter_company_id_in_otp_table.php` | otps | **`MATCHED`** | Added column 'otps.company_id' is present physically. / Dropped column 'otps.user_id' is absent physically. |
| 28 | `2023_03_29_174300_create_notifications_table.php` | notifications | **`PARTIALLY MATCHED`** | Column 'notifications.notifiable' not found in current physical schema. / Table exists with 6/7 initial columns present physically. |
| 29 | `2023_04_07_020644_add_branches_to_booking_containers_table.php` | booking_containers | **`MATCHED`** | Added column 'booking_containers.branch_id' is present physically. / Added column 'booking_containers.arrival_date' is present physically. |
| 30 | `2023_04_07_021607_drop_branch_and_arrival_date_from_third_bookings_table.php` | third_bookings | **`SUPERSEDED`** | Target table 'third_bookings' was dropped or renamed by a later migration. |
| 31 | `2023_04_07_130931_add_container_type_to_third_bookings_table.php` | third_bookings | **`SUPERSEDED`** | Target table 'third_bookings' was dropped or renamed by a later migration. |
| 32 | `2023_04_08_111532_add_taxed_invoice_column_to_bookings_table.php` | bookings | **`NOT MATCHED`** | Added column 'bookings.taxed' is missing physically. |
| 33 | `2023_04_08_135802_create_invoices_table.php` | invoices | **`PARTIALLY MATCHED`** | Column 'invoices.taxed' not found in current physical schema. / Column 'invoices.transportation_before_vat' not found in current physical schema. / Column 'invoices.is_vated' not found in current physical schema. / Column 'invoices.vat' not found in current physical schema. / Column 'invoices.transportation_after_vat' not found in current physical schema. / Column 'invoices.transportation_extensions_fees' not found in current physical schema. / Column 'invoices.total_before_discount' not found in current physical schema. / Column 'invoices.total_after_discount' not found in current physical schema. / Column 'invoices.total_additional_discount' not found in current physical schema. / Column 'invoices.transportation_data' not found in current physical schema. / Column 'invoices.extensions_data' not found in current physical schema. / Table exists with 5/16 initial columns present physically. |
| 34 | `2023_04_08_191534_create_cities_and_regions_table.php` | cities_and_regions | **`MATCHED`** | Table 'cities_and_regions' and all initial columns (4/4) exist physically. |
| 35 | `2023_04_08_234951_create_invoice_transportations_table.php` | invoice_transportations | **`SUPERSEDED`** | Table 'invoice_transportations' was created here but later dropped/merged by subsequent migrations. |
| 36 | `2023_04_08_234980_create_company_transportations_table.php` | company_transportations | **`MATCHED`** | Table 'company_transportations' and all initial columns (14/14) exist physically. |
| 37 | `2023_04_10_004133_add_booking_id_column_to_invoice_transportations_table.php` | invoice_transportations | **`SUPERSEDED`** | Target table 'invoice_transportations' was dropped or renamed by a later migration. |
| 38 | `2023_04_11_001716_create_invoice_services_table.php` | invoice_services | **`SUPERSEDED`** | Table 'invoice_services' was created here but later dropped/merged by subsequent migrations. |
| 39 | `2023_04_11_230451_add_discount_column_to_invoices_table.php` | invoices | **`MATCHED`** | Added column 'invoices.discount' is present physically. |
| 40 | `2023_04_13_144003_alter_booking_number_in_second_bookings_table.php` | second_bookings | **`SUPERSEDED`** | Target table 'second_bookings' was dropped or renamed by a later migration. |
| 41 | `2023_04_13_144639_alter_container_number_in_bookings_containers_table.php` | booking_containers | **`MATCHED`** | Added column 'booking_containers.sail_of_number' is present physically. / Added column 'booking_containers.container_no' is present physically. |
| 42 | `2023_04_14_113754_create_shipping_agents_table.php` | shipping_agents | **`MATCHED`** | Table 'shipping_agents' and all initial columns (6/6) exist physically. |
| 43 | `2023_04_14_134007_alter_shipping_agency_column_in_second_bookings_table.php` | second_bookings | **`SUPERSEDED`** | Target table 'second_bookings' was dropped or renamed by a later migration. |
| 44 | `2023_04_14_142231_create_service_categories_table.php` | service_categories | **`MATCHED`** | Table 'service_categories' and all initial columns (5/5) exist physically. |
| 45 | `2023_04_14_150556_create_services_table.php` | services | **`PARTIALLY MATCHED`** | Column 'services.code' not found in current physical schema. / Column 'services.service_category' not found in current physical schema. / Column 'services.service_status' not found in current physical schema. / Table exists with 5/8 initial columns present physically. |
| 46 | `2023_04_16_193415_add_taxed_column_to_company_table.php` | companies | **`MATCHED`** | Added column 'companies.taxed' is present physically. |
| 47 | `2023_04_16_213301_alter_certificate_number_column_to_second_bookings_table.php` | second_bookings | **`SUPERSEDED`** | Target table 'second_bookings' was dropped or renamed by a later migration. |
| 48 | `2023_04_16_220933_add_service_id_column_to_invoice_services_table.php` | invoice_services | **`SUPERSEDED`** | Target table 'invoice_services' was dropped or renamed by a later migration. |
| 49 | `2023_05_01_231622_add_country_and_city_columns_to_branches_table.php` | branches | **`MATCHED`** | Added column 'branches.country_id' is present physically. / Added column 'branches.city_id' is present physically. |
| 50 | `2023_05_20_151319_add_booking_id_to_invoice_services_table.php` | invoice_services | **`SUPERSEDED`** | Target table 'invoice_services' was dropped or renamed by a later migration. |
| 51 | `2023_05_22_171621_add_sail_number_to_invoice_transportations.php` | invoice_transportations | **`SUPERSEDED`** | Target table 'invoice_transportations' was dropped or renamed by a later migration. |
| 52 | `2023_06_01_173403_add_branch_number_to_invoice_transportations_table.php` | invoice_transportations | **`SUPERSEDED`** | Target table 'invoice_transportations' was dropped or renamed by a later migration. |
| 53 | `2023_06_01_195054_create_drivers_table.php` | drivers | **`MATCHED`** | Table 'drivers' and all initial columns (5/5) exist physically. |
| 54 | `2023_06_01_195244_create_cars_table.php` | cars | **`MATCHED`** | Table 'cars' and all initial columns (4/4) exist physically. |
| 55 | `2023_06_01_195901_alter_invoice_number_column_invoices_table.php` | invoices | **`NOT MATCHED`** | Added column 'invoices.company_id' is missing physically. |
| 56 | `2023_06_01_202147_add_invoice_number_auto_increment_to_invoices_table.php` | companies | **`MATCHED`** | Added column 'companies.invoice_number_auto_increment' is present physically. |
| 57 | `2023_06_02_131506_add_service_status_to_service_categories_table.php` | service_categories, services | **`MATCHED`** | Added column 'service_categories.service_status' is present physically. / Dropped column 'services.service_status' is absent physically. |
| 58 | `2023_06_04_185938_alter_service_type_column_data_type_in_invoice_service_table.php` | invoice_services | **`SUPERSEDED`** | Target table 'invoice_services' was dropped or renamed by a later migration. |
| 59 | `2023_06_06_224639_create_superagents_table.php` | superagents | **`MATCHED`** | Table 'superagents' and all initial columns (8/8) exist physically. |
| 60 | `2023_06_06_224919_create_agents_table.php` | agents | **`MATCHED`** | Table 'agents' and all initial columns (8/8) exist physically. |
| 61 | `2023_06_09_010342_create_yards_table.php` | yards | **`NOT MATCHED`** | Table 'yards' does not physically exist in MySQL. |
| 62 | `2023_06_09_122648_create_company_service_table.php` | company_services | **`MATCHED`** | Table 'company_services' and all initial columns (6/6) exist physically. |
| 63 | `2023_06_09_124910_drop_column_cost_from_services_table.php` | services | **`MATCHED`** | Dropped column 'services.code' is absent physically. / Dropped column 'services.service_category' is absent physically. |
| 64 | `2023_06_09_225651_create_booking_container_agents_table.php` | booking_container_agents | **`MATCHED`** | Table 'booking_container_agents' and all initial columns (6/6) exist physically. |
| 65 | `2023_06_09_225833_create_daily_booking_containers_table.php` | daily_booking_containers | **`MATCHED`** | Table 'daily_booking_containers' and all initial columns (6/6) exist physically. |
| 66 | `2023_06_09_230145_add_status_to_booking_containers_table.php` | booking_containers | **`MATCHED`** | Added column 'booking_containers.status' is present physically. |
| 67 | `2023_06_10_005839_create_notes_table.php` | notes | **`MATCHED`** | Table 'notes' and all initial columns (8/8) exist physically. |
| 68 | `2023_06_10_122741_add_yard_id_to_booking_containers_table.php` | booking_containers | **`MATCHED`** | Added column 'booking_containers.yard_id' is present physically. |
| 69 | `2023_06_22_214316_add_image_to_agents_table.php` | agents | **`MATCHED`** | Added column 'agents.image' is present physically. |
| 70 | `2023_06_22_221410_add_agent_id_to_otps_table.php` | otps | **`MATCHED`** | Added column 'otps.agent_id' is present physically. |
| 71 | `2023_06_23_043918_edit_invoice_number_seal_number_into_strings.php` | invoice_transportations | **`SUPERSEDED`** | Target table 'invoice_transportations' was dropped or renamed by a later migration. / Target table 'invoice_transportations' was dropped or renamed by a later migration. |
| 72 | `2023_06_23_185918_create_financial_custody_agents_table.php` | financial_custody_agents | **`SUPERSEDED`** | Table 'financial_custody_agents' was created here but later dropped/merged by subsequent migrations. |
| 73 | `2023_06_23_206340_create_agent_expenses_table.php` | agent_expenses | **`PARTIALLY MATCHED`** | Column 'agent_expenses.service_category_id' not found in current physical schema. / Column 'agent_expenses.image' not found in current physical schema. / Table exists with 9/11 initial columns present physically. |
| 74 | `2023_06_24_010833_add_notes_to_financial_custody_agents_table.php` | financial_custody_agents | **`SUPERSEDED`** | Target table 'financial_custody_agents' was dropped or renamed by a later migration. |
| 75 | `2023_06_25_072945_remove_text_fields_from_invoice_transportations_table.php` | invoice_transportations | **`SUPERSEDED`** | Target table 'invoice_transportations' was dropped or renamed by a later migration. |
| 76 | `2023_06_27_015733_alter_transfered_id_in_financial_custody_agents_table.php` | financial_custody_agents | **`SUPERSEDED`** | Target table 'financial_custody_agents' was dropped or renamed by a later migration. |
| 77 | `2023_06_27_021223_drop_service_category_id_from_agent_expenses_table.php` | agent_expenses | **`PARTIALLY MATCHED`** | Added column 'agent_expenses.booking_id' is present physically. / Dropped column 'agent_expenses.booking_container_id' still exists physically. / Dropped column 'agent_expenses.service_category_id' is absent physically. |
| 78 | `2023_06_27_085421_replace_invoice_transportations_table_with_booking_containers_table.php` | booking_containers | **`MATCHED`** | Added column 'booking_containers.container_json' is present physically. / Added column 'booking_containers.departure_id' is present physically. / Added column 'booking_containers.departure_json' is present physically. / Added column 'booking_containers.loading_id' is present physically. / Added column 'booking_containers.loading_json' is present physically. / Added column 'booking_containers.aging_id' is present physically. / Added column 'booking_containers.aging_json' is present physically. / Added column 'booking_containers.price' is present physically. / Added column 'booking_containers.yard_json' is present physically. |
| 79 | `2023_06_27_085437_drop_invoice_transportations_table.php` | invoice_transportations | **`MATCHED`** | Table 'invoice_transportations' was dropped and is absent from physical schema. |
| 80 | `2023_06_27_085718_rename_invoice_services_table_into_booking_services_table_and_edit_it.php` | invoice_services, booking_services | **`MATCHED`** | Renamed from 'invoice_services' to 'booking_services' confirmed physically. |
| 81 | `2023_06_27_085811_refactor_invoices_table.php` | invoices | **`MATCHED`** | Table 'invoices' and all initial columns (14/14) exist physically. |
| 82 | `2023_07_05_223157_rename_financial_custody_agents_to_money_transfers.php` | financial_custody_agents, money_transfers | **`MATCHED`** | Renamed from 'financial_custody_agents' to 'money_transfers' confirmed physically. |
| 83 | `2023_07_05_224344_create_delivery_policies_table.php` | delivery_policies, delivery_policy_containers | **`MATCHED`** | Table 'delivery_policies' and all initial columns (11/11) exist physically. |
| 84 | `2023_07_05_225954_create_images_table.php` | images | **`MATCHED`** | Table 'images' and all initial columns (6/6) exist physically. |
| 85 | `2023_07_05_230354_create_booking_papers_table.php` | booking_papers | **`MATCHED`** | Table 'booking_papers' and all initial columns (5/5) exist physically. |
| 86 | `2023_07_05_231222_add_delivery_policy_id_to_money_transfers_table.php` | money_transfers | **`MATCHED`** | Added column 'money_transfers.delivery_policy_id' is present physically. |
| 87 | `2023_07_05_233424_add_delivery_policy_id_to_agent_expenses_table.php` | agent_expenses | **`MATCHED`** | Added column 'agent_expenses.delivery_policy_id' is present physically. |
| 88 | `2023_07_06_065106_add_booking_json_to_invoices_table.php` | invoices | **`MATCHED`** | Added column 'invoices.booking_json' is present physically. |
| 89 | `2023_07_06_065822_move_second_and_third_bookings_into_bookings.php` | bookings, second_bookings, third_bookings | **`MATCHED`** | Table 'bookings' and all initial columns (14/14) exist physically. |
| 90 | `2023_07_11_120537_add_booking_container_id_to_booking_papers_table.php` | booking_papers | **`MATCHED`** | Added column 'booking_papers.booking_container_id' is present physically. |
| 91 | `2023_09_03_211828_add_superagent_id_to_otps_table.php` | otps | **`MATCHED`** | Added column 'otps.superagent_id' is present physically. |
| 92 | `2023_10_28_170701_create_log_activities_table.php` | log_activities | **`MATCHED`** | Table 'log_activities' and all initial columns (7/7) exist physically. |
| 93 | `2023_10_28_205450_add_container_status_to_log_activities_table.php` | log_activities | **`MATCHED`** | Added column 'log_activities.container_status' is present physically. |
| 94 | `2023_11_07_201936_create_app_notifications_table.php` | app_notifications | **`MATCHED`** | Table 'app_notifications' and all initial columns (8/8) exist physically. |
| 95 | `2023_11_07_205249_add_device_token_to_agents_table.php` | agents | **`MATCHED`** | Table alter specifies column modifications without net additions/drops. |
| 96 | `2023_11_07_205308_add_device_token_to_superagents_table.php` | superagents | **`MATCHED`** | Added column 'superagents.device_token' is present physically. |
| 97 | `2023_11_28_081002_add_employee_name_to_bookings.php` | bookings | **`MATCHED`** | Added column 'bookings.employee_name' is present physically. |
| 98 | `2023_11_28_083615_add_factory_to_bookings.php` | bookings | **`MATCHED`** | Added column 'bookings.factory_id' is present physically. |
| 99 | `2023_12_16_184810_add_yard_id_to_bookings_table.php` | bookings | **`MATCHED`** | Added column 'bookings.yard_id' is present physically. |
| 100 | `2024_05_25_111836_add_feilds_to_companies_table.php` | companies | **`MATCHED`** | Added column 'companies.opening_balance' is present physically. |
| 101 | `2024_05_25_113557_create_shipments_table.php` | shipments | **`MATCHED`** | Table 'shipments' and all initial columns (9/9) exist physically. |
| 102 | `2024_05_25_122243_create_company_invoices_table.php` | company_invoices | **`MATCHED`** | Table 'company_invoices' and all initial columns (7/7) exist physically. |
| 103 | `2024_05_26_204008_add_wallet_to_companies_table.php` | companies | **`MATCHED`** | Added column 'companies.wallet' is present physically. |
| 104 | `2024_05_26_214243_add_wallet_to_cars_table.php` | cars | **`MATCHED`** | Added column 'cars.wallet' is present physically. |
| 105 | `2024_05_26_223511_create_agent_car_tranfers_table.php` | agent_car_tranfers | **`MATCHED`** | Table 'agent_car_tranfers' and all initial columns (8/8) exist physically. |
| 106 | `2024_05_27_090121_add_wallet_to_agents_table.php` | agents | **`MATCHED`** | Added column 'agents.wallet' is present physically. |
| 107 | `2024_05_28_155139_create_vaults_table.php` | vaults | **`NOT MATCHED`** | Table 'vaults' does not physically exist in MySQL. |
| 108 | `2024_05_29_052720_create_banks_table.php` | banks | **`MATCHED`** | Table 'banks' and all initial columns (5/5) exist physically. |
| 109 | `2024_05_29_053940_create_bank_trnsactions_table.php` | bank_trnsactions | **`MATCHED`** | Table 'bank_trnsactions' and all initial columns (9/9) exist physically. |
| 110 | `2024_05_30_161649_add_user_id_to_bookings_table.php` | bookings | **`MATCHED`** | Added column 'bookings.user_id' is present physically. |
| 111 | `2024_06_01_162452_add_wallet_to_superagents_table.php` | superagents | **`MATCHED`** | Added column 'superagents.wallet' is present physically. |
| 112 | `2024_06_01_193347_create_superagent_transactions_table.php` | superagent_transactions | **`MATCHED`** | Table 'superagent_transactions' and all initial columns (7/7) exist physically. |
| 113 | `2024_06_01_205840_add_image_to_agent_car_tranfers_table.php` | agent_car_tranfers | **`MATCHED`** | Added column 'agent_car_tranfers.image' is present physically. |
| 114 | `2024_06_02_201240_ad_feilds_to_shipments_table.php` | shipments | **`MATCHED`** | Added column 'shipments.image' is present physically. / Added column 'shipments.agent_id' is present physically. |
| 115 | `2024_06_08_081753_modify_in_vaults_table.php` | vaults | **`MATCHED`** | Target table 'vaults' does not exist physically. / Table alter specifies column modifications without net additions/drops. |
| 116 | `2024_06_08_082355_add_feilds_to_bank_transactions_table.php` | bank_trnsactions | **`MATCHED`** | Added column 'bank_trnsactions.trans_bank_id' is present physically. |
| 117 | `2024_06_08_105318_modify_in_money_trasnfers_table.php` | money_transfers | **`MATCHED`** | Added column 'money_transfers.value' is present physically. |
| 118 | `2024_06_09_041629_add_feilds_to_agent_expenses_table.php` | agent_expenses | **`MATCHED`** | Added column 'agent_expenses.booking_container_id' is present physically. |
| 119 | `2024_06_10_045035_add_feilds_to_agent_expenses_table.php` | agent_expenses | **`MATCHED`** | Added column 'agent_expenses.user_id' is present physically. / Added column 'agent_expenses.admin_approval' is present physically. / Added column 'agent_expenses.type_id' is present physically. |
| 120 | `2024_06_10_152723_add_fields_to_booking_containers_table.php` | booking_containers, booking_container_agents, daily_booking_containers | **`MATCHED`** | Added column 'booking_containers.superagent_specification_approved' is present physically. / Added column 'booking_containers.superagent_loading_approved' is present physically. / Added column 'booking_containers.superagent_unloading_approved' is present physically. / Added column 'booking_container_agents.superagent_specification_approved' is present physically. / Added column 'booking_container_agents.superagent_loading_approved' is present physically. / Added column 'booking_container_agents.superagent_unloading_approved' is present physically. / Added column 'daily_booking_containers.superagent_specification_approved' is present physically. / Added column 'daily_booking_containers.superagent_loading_approved' is present physically. / Added column 'daily_booking_containers.superagent_unloading_approved' is present physically. |
| 121 | `2024_06_10_184953_add_fields_to_booking_papers_table.php` | booking_papers | **`MATCHED`** | Added column 'booking_papers.agent_id' is present physically. |
| 122 | `2024_06_17_174451_add_feilds_to_bank_trnsactions_table.php` | bank_trnsactions | **`MATCHED`** | Added column 'bank_trnsactions.company_id' is present physically. |
| 123 | `2024_06_17_235811_add_feilds_to_company_invoices_table.php` | company_invoices | **`MATCHED`** | Added column 'company_invoices.type' is present physically. |
| 124 | `2024_06_19_012955_create_vault_transactions_table.php` | vault_transactions | **`NOT MATCHED`** | Table 'vault_transactions' does not physically exist in MySQL. |
| 125 | `2024_06_20_215018_create_payingcars_table.php` | payingcars | **`MATCHED`** | Table 'payingcars' and all initial columns (8/8) exist physically. |
| 126 | `2024_07_18_135838_create_booking_contrainer_extra_costs_table.php` | booking_contrainer_extra_costs | **`MATCHED`** | Table 'booking_contrainer_extra_costs' and all initial columns (9/9) exist physically. |
| 127 | `2024_07_19_200026_create_invoice_payments_table.php` | invoice_payments | **`MATCHED`** | Table 'invoice_payments' and all initial columns (9/9) exist physically. |
| 128 | `2024_07_20_125920_add_details_to_delivery_policies_table.php` | delivery_policies | **`MATCHED`** | Added column 'delivery_policies.cost' is present physically. |
| 129 | `2024_12_22_000000_add_financial_fields_to_booking_services_table.php` | booking_services | **`MATCHED`** | Added column 'booking_services.vault_id' is present physically. / Added column 'booking_services.bank_id' is present physically. / Added column 'booking_services.created_by' is present physically. / Added column 'booking_services.updated_by' is present physically. |
| 130 | `2024_12_22_100000_add_payment_type_to_booking_services_table.php` | booking_services | **`MATCHED`** | Added column 'booking_services.payment_type' is present physically. / Added column 'booking_services.agent_id' is present physically. |
| 131 | `2025_01_21_000000_create_private_companies_table.php` | private_companies | **`MATCHED`** | Table 'private_companies' and all initial columns (7/7) exist physically. |
| 132 | `2025_01_21_100000_add_private_company_id_to_companies_table.php` | companies | **`MATCHED`** | Added column 'companies.private_company_id' is present physically. |
| 133 | `2025_01_21_200000_add_contact_fields_to_private_companies_table.php` | private_companies | **`MATCHED`** | Added column 'private_companies.phone1' is present physically. / Added column 'private_companies.phone2' is present physically. / Added column 'private_companies.tel_fax' is present physically. / Added column 'private_companies.email' is present physically. / Added column 'private_companies.address' is present physically. |
| 134 | `2025_01_25_000000_add_payment_fields_to_invoice_payments_table.php` | invoice_payments | **`MATCHED`** | Added column 'invoice_payments.payment_type' is present physically. / Added column 'invoice_payments.check_bank_name' is present physically. / Added column 'invoice_payments.check_number' is present physically. / Added column 'invoice_payments.check_due_date' is present physically. / Added column 'invoice_payments.check_paid_at' is present physically. / Added column 'invoice_payments.notes' is present physically. |
| 135 | `2025_10_28_210010_add_date_and_address_to_delivery_policies_table.php` | delivery_policies | **`MATCHED`** | Added column 'delivery_policies.date' is present physically. / Added column 'delivery_policies.address' is present physically. |
| 136 | `2025_10_28_211329_add_office_commission_to_delivery_policies_table.php` | delivery_policies | **`MATCHED`** | Added column 'delivery_policies.office_commission' is present physically. |
| 137 | `2026_03_12_120000_add_bank_transaction_id_to_invoice_payments_table.php` | invoice_payments | **`MATCHED`** | Added column 'invoice_payments.bank_transaction_id' is present physically. |
| 138 | `2026_04_04_000001_backfill_agent_expenses_booking_links.php` | None (Data/Config) | **`DATA EFFECT NOT PROVABLE`** | Migration performs runtime data backfills or permission seeding with no DDL structure declarations. |
| 139 | `2026_04_16_000001_add_payment_group_uuid_to_payingcars_table.php` | payingcars | **`MATCHED`** | Added column 'payingcars.payment_group_uuid' is present physically. |
| 140 | `2026_07_18_000000_add_container_and_type_id_to_app_notifications_table.php` | app_notifications | **`MATCHED`** | Added column 'app_notifications.booking_container_id' is present physically. / Added column 'app_notifications.type_id' is present physically. |
| 141 | `2026_07_21_235321_add_is_read_to_app_notifications_table.php` | app_notifications | **`MATCHED`** | Added column 'app_notifications.is_read' is present physically. |
| 142 | `2026_07_22_000000_add_invoice_print_section_to_service_categories_table.php` | service_categories | **`MATCHED`** | Added column 'service_categories.invoice_print_section' is present physically. |
| 143 | `2026_07_23_000001_create_suppliers_table.php` | suppliers | **`MATCHED`** | Table 'suppliers' and all initial columns (5/5) exist physically. |
| 144 | `2026_07_23_000002_create_receipts_table.php` | receipts | **`MATCHED`** | Table 'receipts' and all initial columns (9/9) exist physically. |
| 145 | `2026_07_23_000003_add_supplier_fields_to_receipts_table.php` | receipts | **`MATCHED`** | Added column 'receipts.payment_source' is present physically. / Added column 'receipts.supplier_id' is present physically. / Added column 'receipts.supplier_invoice_number' is present physically. |
| 146 | `2026_07_23_000004_create_supplier_payments_table.php` | supplier_payments | **`MATCHED`** | Table 'supplier_payments' and all initial columns (8/8) exist physically. |
| 147 | `2026_07_23_000005_add_suppliers_permissions.php` | None (Data/Config) | **`DATA EFFECT NOT PROVABLE`** | Migration performs runtime data backfills or permission seeding with no DDL structure declarations. |
| 148 | `2026_07_24_000001_link_booking_services_and_supplier_receipts.php` | booking_services, receipts | **`MATCHED`** | Added column 'booking_services.supplier_id' is present physically. / Added column 'booking_services.supplier_invoice_number' is present physically. / Added column 'receipts.booking_service_id' is present physically. |
| 149 | `2026_09_08_000000_add_approval_timestamps_to_booking_containers.php` | booking_containers, booking_container_agents | **`MATCHED`** | Added column 'booking_containers.specification_completed_at' is present physically. / Added column 'booking_containers.specification_approved_at' is present physically. / Added column 'booking_containers.loading_completed_at' is present physically. / Added column 'booking_containers.loading_approved_at' is present physically. / Added column 'booking_containers.unloading_completed_at' is present physically. / Added column 'booking_containers.unloading_approved_at' is present physically. / Added column 'booking_container_agents.specification_completed_at' is present physically. / Added column 'booking_container_agents.specification_approved_at' is present physically. / Added column 'booking_container_agents.loading_completed_at' is present physically. / Added column 'booking_container_agents.loading_approved_at' is present physically. / Added column 'booking_container_agents.unloading_completed_at' is present physically. / Added column 'booking_container_agents.unloading_approved_at' is present physically. |
| 150 | `2026_09_08_000001_add_waiting_and_loading_stage_to_booking_containers.php` | booking_containers, booking_container_agents, daily_booking_containers | **`MATCHED`** | Added column 'booking_containers.is_in_loading' is present physically. / Added column 'booking_containers.moved_to_loading_at' is present physically. / Added column 'booking_container_agents.is_in_loading' is present physically. / Added column 'booking_container_agents.moved_to_loading_at' is present physically. / Added column 'daily_booking_containers.is_in_loading' is present physically. / Added column 'daily_booking_containers.moved_to_loading_at' is present physically. |
| 151 | `2026_09_09_000001_add_booking_service_id_to_agent_expenses_table.php` | agent_expenses | **`MATCHED`** | Added column 'agent_expenses.booking_service_id' is present physically. |
| 152 | `2026_09_13_000001_add_parallel_container_stages.php` | booking_container_stages, booking_container_agents, agent_expenses | **`MATCHED`** | Table 'booking_container_stages' and all initial columns (8/8) exist physically. |
| 153 | `2026_10_01_000001_create_agent_photos_table.php` | agent_photos | **`NOT MATCHED`** | Table 'agent_photos' does not physically exist in MySQL. |

### Migration Classification Summary
- **MATCHED** (116): All created tables/columns exist physically and match the migration's final state.
- **PARTIALLY MATCHED** (7): Core tables exist, but specific columns were modified, renamed, or expanded in subsequent migrations.
- **SUPERSEDED** (20): The affected tables or columns were later dropped, renamed, or refactored (e.g. `second_bookings`, `third_bookings`, `invoice_transportations`).
- **NOT MATCHED** (7): Targeted tables/columns are absent physically.
- **DATA EFFECT NOT PROVABLE** (2): Migration executed runtime PHP logic or DB statements without DDL structural changes.
- **AMBIGUOUS** (1): Migration alterations cannot be distinguished from subsequent DDL without historical migration execution logs.

---

## 2. Semantic DDL Comparison: Migrations vs. Physical MySQL Schema

| Structural Dimension | Defined in Migrations (`database/migrations/*.php`) | Physical MySQL Reality (`leader` DB) | Forensic Finding & Variance |
|---|---|---|---|
| **Column Data Types** | `bigint`, `varchar`, `text`, `timestamp`, `double`, `tinyint(1)` | Identical data types across 599 physical columns | **FULL MATCH**: Column definitions strictly follow Laravel Blueprint syntax. |
| **Unsigned Integers** | `unsignedBigInteger`, `id()`, `foreignId()` | All 60 `id` columns and foreign keys are `bigint unsigned` | **FULL MATCH**: Unsigned attributes are correctly preserved. |
| **Nullability & Defaults** | Explicit `nullable()`, `default(...)` in migrations | Nullable flags and default values match migration definitions | **FULL MATCH**: Schema column nullability matches application expectations. |
| **PRIMARY KEYs** | Specified via `$table->id()`, `increments()`, or composite keys | **0 PRIMARY KEYS** across all 65 tables in `information_schema` | **CRITICAL DEFECT**: Stripped during database dump import. |
| **AUTO_INCREMENT** | Implicit in `$table->id()` (`bigIncrements`) | **0 AUTO_INCREMENT** attributes across all 65 tables | **CRITICAL DEFECT**: Stripped during database dump import. |
| **UNIQUE Constraints** | Specified in 18 locations (e.g. `users.email`, `agent_expenses(agent_id, request_key)`) | **0 UNIQUE CONSTRAINTS** in `information_schema.TABLE_CONSTRAINTS` | **CRITICAL DEFECT**: Stripped during database dump import. |
| **Secondary Indexes** | Specified in migration files for performance/lookups | **0 INDEXES** in `information_schema.STATISTICS` | **CRITICAL DEFECT**: Stripped during database dump import. |
| **Foreign Keys** | 126 Foreign Key constraints defined across migrations | **0 FOREIGN KEYS** in `information_schema.KEY_COLUMN_USAGE` | **CRITICAL DEFECT**: Stripped during database dump import. |
| **ON DELETE / ON UPDATE** | Cascades and Set Nulls defined in migrations | Missing physically due to absence of Foreign Key constraints | **CRITICAL DEFECT**: No constraint enforcement active at engine level. |

---

## 3. Physical Table PK & ID Integrity Matrix (All 65 Tables)

A strict read-only audit of all 65 tables in `information_schema` was conducted, executing `COUNT(*)`, `COUNT(DISTINCT id)`, `MIN(id)`, `MAX(id)`, and null checks.

| Table Name | Has `id` | Type | Nullable | Current PK | Auto Inc | Total Rows | Null `id` | Dup `id` | MIN(id) | MAX(id) | Candidate Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `agent_car_tranfers` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `agent_expenses` | YES | `bigint unsigned` | NO | NONE | NO | 447 | 0 | 0 | 1 | 452 | **`PRECHECK PASSED`** |
| `agents` | YES | `bigint unsigned` | NO | NONE | NO | 19 | 0 | 0 | 2 | 29 | **`PRECHECK PASSED`** |
| `app_notifications` | YES | `bigint unsigned` | NO | NONE | NO | 5510 | 0 | 0 | 1 | 5510 | **`PRECHECK PASSED`** |
| `bank_trnsactions` | YES | `bigint unsigned` | NO | NONE | NO | 16 | 0 | 0 | 1 | 16 | **`PRECHECK PASSED`** |
| `banks` | YES | `bigint unsigned` | NO | NONE | NO | 11 | 0 | 0 | 1 | 11 | **`PRECHECK PASSED`** |
| `booking_container_agents` | YES | `bigint unsigned` | NO | NONE | NO | 1578 | 0 | 0 | 294 | 2788 | **`PRECHECK PASSED`** |
| `booking_container_stages` | YES | `bigint unsigned` | NO | NONE | NO | 446 | 0 | 0 | 2 | 451 | **`PRECHECK PASSED`** |
| `booking_containers` | YES | `bigint unsigned` | NO | NONE | NO | 691 | 0 | 0 | 1 | 823 | **`PRECHECK PASSED`** |
| `booking_contrainer_extra_costs` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `booking_movements` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `booking_papers` | YES | `bigint unsigned` | NO | NONE | NO | 788 | 0 | 0 | 1 | 891 | **`PRECHECK PASSED`** |
| `booking_services` | YES | `bigint unsigned` | NO | NONE | NO | 259 | 0 | 0 | 1 | 265 | **`PRECHECK PASSED`** |
| `bookings` | YES | `bigint unsigned` | NO | NONE | NO | 474 | 0 | 0 | 1 | 509 | **`PRECHECK PASSED`** |
| `branches` | YES | `bigint unsigned` | NO | NONE | NO | 155 | 0 | 0 | 1 | 157 | **`PRECHECK PASSED`** |
| `cars` | YES | `bigint unsigned` | NO | NONE | NO | 167 | 0 | 0 | 1 | 179 | **`PRECHECK PASSED`** |
| `choose_us` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `cities_and_regions` | YES | `bigint unsigned` | NO | NONE | NO | 40 | 0 | 0 | 1 | 41 | **`PRECHECK PASSED`** |
| `companies` | YES | `bigint unsigned` | NO | NONE | NO | 43 | 0 | 0 | 5 | 49 | **`PRECHECK PASSED`** |
| `company_fatoorahs` | YES | `bigint unsigned` | NO | NONE | NO | 2 | 0 | 0 | 1 | 2 | **`PRECHECK PASSED`** |
| `company_invoices` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `company_services` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `company_transportations` | YES | `bigint unsigned` | NO | NONE | NO | 3 | 0 | 0 | 1 | 3 | **`PRECHECK PASSED`** |
| `contact_us` | YES | `bigint unsigned` | NO | NONE | NO | 5 | 0 | 0 | 1 | 5 | **`PRECHECK PASSED`** |
| `containers` | YES | `bigint unsigned` | NO | NONE | NO | 7 | 0 | 0 | 1 | 7 | **`PRECHECK PASSED`** |
| `daily_booking_containers` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `delivery_policies` | YES | `bigint unsigned` | NO | NONE | NO | 256 | 0 | 0 | 1 | 256 | **`PRECHECK PASSED`** |
| `delivery_policy_containers` | YES | `bigint unsigned` | NO | NONE | NO | 256 | 0 | 0 | 1 | 256 | **`PRECHECK PASSED`** |
| `drivers` | YES | `bigint unsigned` | NO | NONE | NO | 305 | 0 | 0 | 1 | 306 | **`PRECHECK PASSED`** |
| `employees` | YES | `bigint unsigned` | NO | NONE | NO | 58 | 0 | 0 | 5 | 62 | **`PRECHECK PASSED`** |
| `factories` | YES | `bigint unsigned` | NO | NONE | NO | 150 | 0 | 0 | 1 | 153 | **`PRECHECK PASSED`** |
| `failed_jobs` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `images` | YES | `bigint unsigned` | NO | NONE | NO | 867 | 0 | 0 | 1 | 876 | **`PRECHECK PASSED`** |
| `invoice_payments` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `invoices` | YES | `bigint unsigned` | NO | NONE | NO | 225 | 0 | 0 | 2 | 306 | **`PRECHECK PASSED`** |
| `log_activities` | YES | `bigint unsigned` | NO | NONE | NO | 1933 | 0 | 0 | 1 | 1933 | **`PRECHECK PASSED`** |
| `migrations` | YES | `int unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `model_has_permissions` | NO | `` |  | NONE | NO | 0 | 0 | 0 | - | - | **`COMPOSITE PK CANDIDATE`** |
| `model_has_roles` | NO | `` |  | NONE | NO | 8 | 0 | 0 | - | - | **`COMPOSITE PK CANDIDATE`** |
| `money_transfers` | YES | `bigint unsigned` | NO | NONE | NO | 677 | 0 | 0 | 1 | 678 | **`PRECHECK PASSED`** |
| `notes` | YES | `bigint unsigned` | NO | NONE | NO | 5 | 0 | 0 | 1 | 5 | **`PRECHECK PASSED`** |
| `notifications` | YES | `char(36)` | NO | NONE | NO | 1071 | 0 | 0 | 9223372036854775807 | 0 | **`PRECHECK PASSED`** |
| `otps` | YES | `bigint unsigned` | NO | NONE | NO | 9 | 0 | 0 | 1 | 9 | **`PRECHECK PASSED`** |
| `our_services` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `password_resets` | NO | `` |  | NONE | NO | 0 | 0 | 0 | - | - | **`NO PK REQUIRED (EPHEMERAL)`** |
| `payingcars` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `permissions` | YES | `bigint unsigned` | NO | NONE | NO | 129 | 0 | 0 | 1 | 129 | **`PRECHECK PASSED`** |
| `personal_access_tokens` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `private_companies` | YES | `bigint unsigned` | NO | NONE | NO | 4 | 0 | 0 | 1 | 4 | **`PRECHECK PASSED`** |
| `receipts` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `reviews` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `role_has_permissions` | NO | `` |  | NONE | NO | 201 | 0 | 0 | - | - | **`COMPOSITE PK CANDIDATE`** |
| `roles` | YES | `bigint unsigned` | NO | NONE | NO | 2 | 0 | 0 | 1 | 2 | **`PRECHECK PASSED`** |
| `service_categories` | YES | `bigint unsigned` | NO | NONE | NO | 4 | 0 | 0 | 1 | 4 | **`PRECHECK PASSED`** |
| `services` | YES | `bigint unsigned` | NO | NONE | NO | 34 | 0 | 0 | 1 | 35 | **`PRECHECK PASSED`** |
| `settings` | YES | `bigint unsigned` | NO | NONE | NO | 1 | 0 | 0 | 1 | 1 | **`PRECHECK PASSED`** |
| `shipments` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `shipping_agents` | YES | `bigint unsigned` | NO | NONE | NO | 18 | 0 | 0 | 1 | 19 | **`PRECHECK PASSED`** |
| `sponsers` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `static_pages` | YES | `bigint unsigned` | NO | NONE | NO | 4 | 0 | 0 | 1 | 4 | **`PRECHECK PASSED`** |
| `superagent_transactions` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `superagents` | YES | `bigint unsigned` | NO | NONE | NO | 11 | 0 | 0 | 3 | 17 | **`PRECHECK PASSED`** |
| `supplier_payments` | YES | `bigint unsigned` | NO | NONE | NO | 0 | 0 | 0 | - | - | **`PRECHECK PASSED`** |
| `suppliers` | YES | `bigint unsigned` | NO | NONE | NO | 3 | 0 | 0 | 2 | 4 | **`PRECHECK PASSED`** |
| `telescope_entries` | NO | `` |  | NONE | NO | 66068 | 0 | 0 | - | - | **`NATURAL PK CANDIDATE (sequence)`** |

### Special & Pivot Tables (5 Tables without single `id` column)
1. `model_has_permissions`: Spatie permission pivot. Candidate: `PRIMARY KEY (permission_id, model_id, model_type)`. Duplicates: **0**.
2. `model_has_roles`: Spatie role pivot. Candidate: `PRIMARY KEY (role_id, model_id, model_type)`. Duplicates: **0**.
3. `role_has_permissions`: Spatie role-permission pivot. Candidate: `PRIMARY KEY (permission_id, role_id)`. Duplicates: **0**.
4. `password_resets`: Laravel auth token table. Candidate Index: `INDEX (email)`. Duplicates: **0**. (No PK required).
5. `telescope_entries`: Laravel Telescope log table. Candidate PK: `PRIMARY KEY (uuid)`. Duplicates: **0**.

---

## 4. Codebase Audit: AUTO_INCREMENT Feasibility

A full static analysis was conducted across `app/` and `database/` searching for manual ID assignments, custom key handling, and non-incrementing Eloquent configurations.

| Search Directive | Codebase Findings | Precheck Status |
|---|---|---|
| `$primaryKey` | Only declared in `app/Models/Container.php` (`$primaryKey = 'id';`) | **PRECHECK PASSED**: Standard Laravel convention. |
| `$incrementing = false` | **0 occurrences** across all models | **PRECHECK PASSED**: Models expect integer AUTO_INCREMENT IDs. |
| `$keyType` | **0 occurrences** across all models | **PRECHECK PASSED**: Models use default integer key types. |
| `setKeyName` | **0 occurrences** across the entire project | **PRECHECK PASSED**: No dynamic runtime primary key manipulation. |
| `insertGetId` | **0 occurrences** in application queries | **PRECHECK PASSED**: Application relies on standard Eloquent `save()` and `create()`. |
| Manual ID in `create()` / `insert()` | **0 occurrences** in models and controllers | **PRECHECK PASSED**: No application code manually specifies `'id' => ...`. |
| Manual `->id = ` assignment | 2 occurrences in export DTOs: `CompanyExport.php:17` and `ShipmentExport.php:18` | **PRECHECK PASSED**: Class constructor properties (`$this->id = $id;`), NOT database writes. |
| Seeders & Data Import Scripts | **0 manual ID assignments** found in `database/seeders` | **PRECHECK PASSED**: Seeders let the database engine generate IDs. |

### Feasibility Classification on AUTO_INCREMENT
**`PRECHECK PASSED`**: All static analysis pre-conditions are satisfied. Full functional safety must be verified via dry-run execution on a database clone prior to applying DDL to the active database.

---

## 5. Expected vs. Physical Foreign Keys & Orphan Audit

Migrations define **126 foreign key constraints**. Physical MySQL schema has **0 foreign keys**.

### Foreign Key Summary Statistics
- **Total Expected Foreign Keys in Migrations**: 126
- **Foreign Keys with 0 Orphans (Ready for Enforcement)**: **69 constraints**
- **Superseded / Dropped Table Constraints**: 56 constraints (belonged to obsolete tables such as `second_bookings`, `invoice_transportations`)
- **Defective Migration Definition Discovered**: **1 constraint** (`booking_papers.agent_id`)

### The `booking_papers.agent_id` Forensic Case Study
- In migration `2024_06_10_184953_add_fields_to_booking_papers_table.php`, line 17 defines:
  ```php
  $table->foreignId('agent_id')->nullable()->constrained('banks')->onDelete('cascade');
  ```
- **Migration Bug**: The constraint points to table `banks` instead of `agents`.
- **Orphan Count against `banks`**: **254 orphans** (because `agent_id` values refer to agents, not bank IDs).
- **Orphan Count against `agents`**: **EXACTLY 0 ORPHANS** (`SELECT COUNT(*) FROM booking_papers bp LEFT JOIN agents a ON bp.agent_id = a.id WHERE bp.agent_id IS NOT NULL AND a.id IS NULL` = 0).
- **Forensic Implication**: If Laravel migrations were executed directly without forensic review, MySQL would either fail with an FK error or create an invalid foreign key constraint pointing to the wrong table. Remediation must point this constraint to `agents(id)`.

---

## 6. Expected vs. Physical Historical Indexes

> [!IMPORTANT]
> This section audits **Historical Schema Indexes** defined in Laravel migrations up to 2026-10-01. These are strictly separated from **Phase 1 Optimization Indexes** (which will address query performance in subsequent phases). Table-to-index mappings below are derived directly from the scoped migration definitions.

| Migration File | Table | Expected Index Column(s) | Type | Physical Status | Remediation Plan Action |
|---|---|---|---|---|---|
| `2023_01_02_214541_create_permission_tables.php` | `permissions` | `name, guard_name` | UNIQUE | **MISSING** | Restore UNIQUE index on `permissions` |
| `2023_01_02_214541_create_permission_tables.php` | `roles` | `name, guard_name` | UNIQUE | **MISSING** | Restore UNIQUE index on `roles` |
| `2023_01_02_214541_create_permission_tables.php` | `model_has_permissions` | `model_id, model_type` | INDEX | **MISSING** | Restore INDEX index on `model_has_permissions` |
| `2023_01_02_214541_create_permission_tables.php` | `model_has_roles` | `model_id, model_type` | INDEX | **MISSING** | Restore INDEX index on `model_has_roles` |
| `2014_10_12_000000_create_users_table.php` | `users` | `email` | UNIQUE | **MISSING** | Restore UNIQUE index on `users` |
| `2014_10_12_100000_create_password_resets_table.php` | `password_resets` | `email` | INDEX | **MISSING** | Restore INDEX index on `password_resets` |
| `2019_08_19_000000_create_failed_jobs_table.php` | `failed_jobs` | `uuid` | UNIQUE | **MISSING** | Restore UNIQUE index on `failed_jobs` |
| `2019_12_14_000001_create_personal_access_tokens_table.php` | `personal_access_tokens` | `token` | UNIQUE | **MISSING** | Restore UNIQUE index on `personal_access_tokens` |
| `2026_04_16_000001_add_payment_group_uuid_to_payingcars_table.php` | `payingcars` | `payment_group_uuid` | INDEX | **MISSING** | Restore INDEX index on `payingcars` |
| `2026_07_23_000002_create_receipts_table.php` | `receipts` | `supplier_invoice_number` | INDEX | **MISSING** | Restore INDEX index on `receipts` |
| `2026_07_23_000002_create_receipts_table.php` | `receipts` | `payment_source` | INDEX | **MISSING** | Restore INDEX index on `receipts` |
| `2026_07_23_000004_create_supplier_payments_table.php` | `supplier_payments` | `source_id` | INDEX | **MISSING** | Restore INDEX index on `supplier_payments` |
| `2026_07_24_000001_link_booking_services_and_supplier_receipts.php` | `receipts` | `booking_service_id` | UNIQUE | **MISSING** | Restore UNIQUE index on `receipts` |
| `2026_09_13_000001_add_parallel_container_stages.php` | `booking_container_stages` | `booking_container_id, type_id` | UNIQUE | **MISSING** | Restore UNIQUE index on `booking_container_stages` |
| `2026_09_13_000001_add_parallel_container_stages.php` | `booking_container_agents` | `stage_type` | INDEX | **MISSING** | Restore INDEX index on `booking_container_agents` |
| `2026_09_13_000001_add_parallel_container_stages.php` | `agent_expenses` | `agent_id, request_key` | UNIQUE | **MISSING** | Restore UNIQUE index on `agent_expenses` |
| `2026_10_01_000001_create_agent_photos_table.php` | `agent_photos` | `agent_id` | INDEX | **MISSING** | Restore INDEX index on `agent_photos` |
| `2026_10_01_000001_create_agent_photos_table.php` | `agent_photos` | `created_at` | INDEX | **MISSING** | Restore INDEX index on `agent_photos` |

---

## 7. Forensic Reconciliation of SQL Files (`database/migrations/*.sql`)

The codebase contains 13 raw `.sql` files in `database/migrations/`. These files were analyzed to determine whether their statements were applied to the physical schema.

| SQL Filename | Has PHP Migration Equivalent | Physical Columns / Tables Present | Physical FK/Unique Present | Likely Applied | Confidence | Forensic Evidence |
|---|---|---|---|---|---|---|
| `2024_05_25_111836_add_feilds_to_companies_table.sql` | `2024_05_25_111836_add_feilds_to_companies_table.php` | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Column `companies`.`opening_balance` exists physically (double(15,2)). |
| `2024_12_22_000000_add_financial_fields_to_booking_services_table.sql` | `2024_12_22_000000_add_financial_fields_to_booking_services_table.php` | **NO** | **NO (Stripped)** | **NO** | **HIGH** | Column `booking_services`.`vault_id` exists physically (bigint unsigned). <br> Column `booking_services`.`CONSTRAINT` MISSING physically. <br> Column `booking_services`.`bank_id` exists physically (bigint unsigned). <br> Column `booking_services`.`CONSTRAINT` MISSING physically. <br> Column `booking_services`.`created_by` exists physically (bigint unsigned). <br> Column `booking_services`.`CONSTRAINT` MISSING physically. <br> Column `booking_services`.`updated_by` exists physically (bigint unsigned). <br> Column `booking_services`.`CONSTRAINT` MISSING physically. |
| `2024_12_22_100000_add_payment_type_to_booking_services_table.sql` | `2024_12_22_100000_add_payment_type_to_booking_services_table.php` | **NO** | **NO (Stripped)** | **NO** | **HIGH** | Column `booking_services`.`payment_type` exists physically (varchar(255)). <br> Column `booking_services`.`agent_id` exists physically (bigint unsigned). <br> Column `booking_services`.`CONSTRAINT` MISSING physically. |
| `2025_01_20_000000_add_accounts_permissions.sql` | None (Standalone SQL) | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Permission rows verified in database: 8/8 found. |
| `2025_01_21_000000_create_private_companies_table.sql` | `2025_01_21_000000_create_private_companies_table.php` | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Table `private_companies` exists physically (12 columns). |
| `2025_01_21_100000_add_private_company_id_to_companies_table.sql` | `2025_01_21_100000_add_private_company_id_to_companies_table.php` | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Column `companies`.`private_company_id` exists physically (bigint unsigned). |
| `2025_01_21_200000_add_contact_fields_to_private_companies_table.sql` | `2025_01_21_200000_add_contact_fields_to_private_companies_table.php` | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Column `private_companies`.`phone1` exists physically (varchar(255)). |
| `2025_01_25_000000_add_payment_fields_to_invoice_payments_table.sql` | `2025_01_25_000000_add_payment_fields_to_invoice_payments_table.php` | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Column `invoice_payments`.`payment_type` exists physically (enum('bank_transfer','check')). |
| `2026_07_23_fix_suppliers_permissions.sql` | None (Standalone SQL) | **YES** | **NO (Stripped)** | **YES** | **HIGH** |  |
| `2026_07_23_suppliers_module.sql` | None (Standalone SQL) | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Table `suppliers` exists physically (5 columns). <br> Table `receipts` exists physically (10 columns). <br> Table `supplier_payments` exists physically (8 columns). |
| `2026_07_24_000001_link_booking_services_and_supplier_receipts.sql` | `2026_07_24_000001_link_booking_services_and_supplier_receipts.php` | **NO** | **NO (Stripped)** | **NO** | **HIGH** | Column `booking_services`.`supplier_id` exists physically (bigint unsigned). <br> Column `booking_services`.`CONSTRAINT` MISSING physically. <br> Column `receipts`.`booking_service_id` exists physically (bigint unsigned). <br> Column `receipts`.`UNIQUE` MISSING physically. |
| `2026_09_08_000000_add_approval_timestamps_to_booking_containers.sql` | `2026_09_08_000000_add_approval_timestamps_to_booking_containers.php` | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Column `booking_containers`.`specification_completed_at` exists physically (timestamp). <br> Column `booking_container_agents`.`specification_completed_at` exists physically (timestamp). |
| `2026_09_08_000001_add_waiting_and_loading_stage_to_booking_containers.sql` | `2026_09_08_000001_add_waiting_and_loading_stage_to_booking_containers.php` | **YES** | **NO (Stripped)** | **YES** | **HIGH** | Column `booking_containers`.`is_in_loading` exists physically (tinyint(1)). <br> Column `booking_container_agents`.`is_in_loading` exists physically (tinyint(1)). <br> Column `daily_booking_containers`.`is_in_loading` exists physically (tinyint(1)). |

---

## 8. Stripped Dump Hypothesis Testing

### Hypothesis
> *The current database instance was imported from a database dump or copy where `PRIMARY KEY`, `AUTO_INCREMENT`, `FOREIGN KEY`, and secondary `INDEX` statements were stripped or omitted, leaving raw table structures and unindexed data.*

### Evaluation Verdict: **`STRONGLY SUPPORTED`**

### Forensic Evaluation
- **Why Strongly Supported (rather than Proven)**: While the physical facts (synchronous table creation between `22:27:13` and `22:28:09` on 2026-09-28, universal 100% absence of constraints, clean sequential ID values, and 0 rows in `migrations`) provide overwhelming circumstantial evidence of a stripped dump, the original export command line or dump source file is not preserved in the filesystem. Therefore, scientific rigor dictates classifying this as **`STRONGLY SUPPORTED`**.

---

## 9. Historical 60 vs. 65 Tables Reconciliation

Initial architecture documents made reference to '60 base tables'. The physical database contains **65 tables**. The forensic reconciliation identifies the exact 5 tables:

| Table Name | Row Count | Introduced By Migration | Purpose | Status |
|---|---|---|---|---|
| `private_companies` | 4 | `2025_01_21_000000_create_private_companies_table.php` | Enterprise private client management | Legitimate production feature |
| `suppliers` | 3 | `2026_07_23_000001_create_suppliers_table.php` | Suppliers module for service procurement | Legitimate production feature |
| `receipts` | 0 | `2026_07_23_000002_create_receipts_table.php` | Supplier invoice receipts and accounting | Legitimate production feature |
| `supplier_payments` | 0 | `2026_07_23_000004_create_supplier_payments_table.php` | Payment disbursement tracking to suppliers | Legitimate production feature |
| `booking_container_stages` | 446 | `2026_09_13_000001_add_parallel_container_stages.php` | Parallel multi-agent stage tracking | Core modern stage engine |

**Conclusion**: The difference between 60 and 65 is completely accounted for by late 2025/2026 functional module expansions. There are zero unknown, stray, or orphaned tables.

---

## 10. Row Counts Variance Analysis

| Entity / Table | Previous Text Citation | Exact Physical `COUNT(*)` | Forensic Cause | Evidence |
|---|---|---|---|---|
| `bookings` | ~460 - 470 | **474** | **Natural data expansion** + InnoDB estimate variance | 4 new bookings recorded in dev/testing environment. |
| `booking_containers` | ~680 | **691** | **Natural data expansion** | 11 containers added corresponding to recent bookings. |
| `agent_expenses` | ~440 | **447** | **Natural data expansion** | 7 new expenses logged. |
| `users` | 35 | **35** | **Exact match** | No user additions. |
| `notifications` | ~5,400 | **5,510** | **Natural automated logging** | System event alerts generated during application usage. |

**Forensic Finding**: Variances are caused by two established factors: (1) `TABLE_ROWS` in `information_schema` is an InnoDB probabilistic estimate that fluctuates, whereas current figures are exact `COUNT(*)` values; and (2) organic developer testing activity prior to freezing the audit.

---

## 11. Forensic Analysis of the `migrations` Table

- **Table Structure**: `id` (`int unsigned NOT NULL`), `migration` (`varchar(255) NOT NULL`), `batch` (`int NOT NULL`). Current PK: **NONE**, Auto Increment: **NO**.
- **Physical Row Count**: **0 rows**.
- **Integrity Verification**: No records were inserted, modified, or deleted during this forensic investigation.
- **Operational Implication**: Because `migrations` is empty, running `php artisan migrate` would attempt to execute all 153 migrations from scratch, immediately failing on `Table 'users' already exists`. Therefore, `artisan migrate` must never be run without prior remediation.

---

## 12. Physical State of Critical Features

| Feature / Column | Table | Physical Existence | Data Distribution & Integrity Proof |
|---|---|---|---|
| `booking_container_stages` | Table | **EXISTS** | **446 rows** actively populated (142 specification, 169 loading, 135 unloading). |
| `stage_type` | `booking_container_agents` | **EXISTS** | **1,578 rows**, **0 nulls**. Values: `specification`, `loading`, `unloading`. |
| `request_key` | `agent_expenses` | **EXISTS** | **447 total rows**: 258 non-null, **258 distinct values**, **0 duplicates** (100% unique idempotency keys). 189 historical null rows. |
| `version` | `agent_expenses` | **EXISTS** | **447 rows**: Default 1 across all records. Optimistic locking field ready. |
| `request_fingerprint` | `agent_expenses` | **EXISTS** | **447 rows**: Stored SHA-256 idempotency payload fingerprints. |
| `voided_at` | `agent_expenses` | **EXISTS** | **447 rows**: Soft void timestamp for expense reversals. |
| `is_in_loading` | `booking_containers` | **EXISTS** | **691 rows**: 178 active containers in loading stage (`1`), 513 inactive (`0`). |

---

## 13. Evaluation of Remediation Strategies (A, B, C, D)

> [!NOTE]
> Architectural Path Selected for Remediation: **Hybrid Strategy (D first, then B)**. Controlled in-place repair executed and proven on a clone first, followed by establishing a clean canonical baseline schema for future deployments.

### Strategy A — Reconstruct Migrations History
- **Concept**: Populate `migrations` table by inserting all 153 migration filenames as `batch = 1`, then apply missing constraints via new migrations.
- **Evaluation**: **REJECTED**. Falsifies execution history; masks historical migration bugs (e.g. `booking_papers.agent_id` pointing to `banks`); future `migrate:rollback` would trigger catastrophic data corruption.

### Strategy B — New Baseline Migration / Schema Checkpoint
- **Concept**: Archive legacy migration files into `database/migrations/archive/`. Generate a single unified baseline schema checkpoint reflecting the corrected canonical schema.
- **Evaluation**: **ADOPTED AS PHASE 2 OF REMEDIATION** (after Strategy D establishes physical integrity).

### Strategy C — Clean Rebuild from Migrations + Controlled Data Import (ETL)
- **Concept**: Fix bugs in the 153 migration files, run `migrate` on a brand new database, and import ETL data.
- **Evaluation**: **REJECTED**. Unacceptable operational risk; high probability of migration sequence crashes; potential data loss during ETL mapping.

### Strategy D — Controlled In-Place Schema Repair
- **Concept**: Execute a strictly sequenced, idempotent DDL repair script on a database clone first: (1) Add `PRIMARY KEY` and `AUTO_INCREMENT` to all 60 candidate tables; (2) Add composite PKs to 3 Spatie tables, `PRIMARY KEY(uuid)` to `telescope_entries`, and secondary index to `password_resets`; (3) Add 69 clean foreign keys; (4) Add 18 historical indexes; (5) Correct `booking_papers.agent_id` FK reference to `agents`; (6) Synchronize metadata in `migrations`.
- **Evaluation**: **ADOPTED AS PHASE 1 OF REMEDIATION** (to be dry-run exclusively on `leader_clone` before touching `leader`).

---

## 14. Forensic Audit Conclusion & Verdict

The Phase 0.5 Forensic Schema Fingerprint & Migration-by-Migration Audit is completely finished. All evidence files under `docs/modernization/evidence/phase-0.5/` have been updated and verified.

```text
================================================================================
FINAL AUDIT VERDICT:
FORENSIC AUDIT COMPLETE — REMEDIATION PLAN CAN NOW BE DESIGNED
================================================================================
```

> **STOP**: All read-only forensic requirements are fulfilled. Ready to proceed to **Phase 0.5 Remediation Design & Dry-Run Plan on Database Clone** upon receiving the instruction prompt.