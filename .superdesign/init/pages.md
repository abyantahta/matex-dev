# Page dependency trees

## /admin (Master Data Overview) — TARGET
Entry: resources/js/Pages/Admin/Dashboard.jsx (56 lines, single render branch, no responsive branching)
Dependencies:
- resources/js/Layouts/AdminLayout.jsx
  - resources/js/Layouts/AuthenticatedLayout.jsx
    - resources/js/Components/ApplicationLogo.jsx
    - resources/js/Components/Dropdown.jsx
    - resources/js/Components/FlashMessage.jsx
    - resources/js/Components/NavLink.jsx
    - resources/js/Components/ResponsiveNavLink.jsx
    - resources/js/Config/masterNav.js
  - resources/js/Config/masterNav.js
- resources/css/app.css, tailwind.config.js
Props: stats {items_qad, supplier_rm, supplier_ohp, purchase_orders, delivery_notes}, tables [{key,label,description,route,count}]

## /admin/discipline
Entry: resources/js/Pages/Admin/Discipline/Index.jsx → AdminLayout, Components/EmptyState.jsx

## /admin/qad-items
Entry: resources/js/Pages/Admin/QadItems/Index.jsx → AdminLayout, Components/Pagination.jsx, PrimaryButton.jsx, TextInput.jsx

## /admin/users
Entry: resources/js/Pages/Admin/Users/Index.jsx → AdminLayout, Pagination, StatusBadge

## /dashboard
Entry: resources/js/Pages/Dashboard.jsx → AuthenticatedLayout, StatusBadge, EmptyState

## /purchase-orders
Entry: resources/js/Pages/Po/Index.jsx → AuthenticatedLayout, StatusBadge, Pagination, EmptyState
