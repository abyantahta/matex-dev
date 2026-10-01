# Extractable components

## Layout
### TopNav
- Source: `resources/js/Layouts/AuthenticatedLayout.jsx` (lines 125–313, `<nav>`)
- Category: layout
- Description: Sticky white top bar: MATEX logo (green rounded square with white M) + "MATEX / MATERIAL EXCHANGE" wordmark, nav links (Dashboard, Forecast, Purchase Orders, Delivery Notes, Billing / Receiving, Master Data ▾) with green underline on active, right side role/company text + user name dropdown button.
- Extractable props: activeItem (string, default "master-data"), userName (string, default "Dita Purchasing"), roleLabel (string, default "Purchasing SDI"), companyName (string, default "PT. SDI")
- Hardcoded: logo SVG, nav labels, chevron icons, all CSS

### MasterDataHeader + Tabs
- Source: `resources/js/Layouts/AdminLayout.jsx`
- Category: layout
- Description: White header band with eyebrow "MASTER DATA", page title, description; below on canvas a pill tab bar (Overview, Item Master QAD, Suppliers, Kedisiplinan RM, Users), active pill solid green.
- Extractable props: title, description, activeTab (default "overview")

## Basic
- StatCard (inline in Pages/Admin/Dashboard.jsx): `.ui-panel p-5`, eyebrow label + 3xl bold number — the card the user wants redesigned.
- LinkCard (inline in Pages/Admin/Dashboard.jsx): title + description + count pill (bg-brand-muted text-brand-deep) + "Kelola →".
- StatusBadge `resources/js/Components/StatusBadge.jsx`, EmptyState, Pagination, PrimaryButton/SecondaryButton/DangerButton, TextInput, SearchableSelect — too simple to extract; inline in drafts.
