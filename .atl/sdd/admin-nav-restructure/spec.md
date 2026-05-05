# Delta Spec: Admin Nav Restructure

**Capability**: admin-nav-nesting (NEW)
**Status**: Draft

---

## ADDED Requirements

### RQ-001: BannerResource Navigation Group Assignment

BannerResource SHALL appear under a navigation group labeled "Configuración Web" in the Lunar admin panel sidebar. The group MUST be registered in the panel configuration via `LunarPanel::panel()`. BannerResource CRUD operations (list, create, edit, delete) MUST remain fully functional after this change.

#### Scenario: BannerResource grouped under Configuración Web

- GIVEN the admin panel sidebar renders
- WHEN navigation groups are displayed
- THEN BannerResource appears under the "Configuración Web" group
- AND BannerResource does NOT appear as an ungrouped item

#### Scenario: BannerResource CRUD unaffected

- GIVEN the navigation group assignment is applied
- WHEN a user navigates to /panel/banners
- THEN the BannerResource index, create, edit, and delete operations work without errors

#### Scenario: Configuración Web group with collapsed state

- GIVEN the sidebar renders with collapsible groups
- WHEN "Configuración Web" is configured as collapsible
- THEN the group collapses and expands correctly
- AND BannerResource is accessible after expanding

---

### RQ-002: ProductOptionResource Nested Under Product in Catalog

ProductOptionResource SHALL appear as a nested child item under the "Productos" parent (ProductResource) within the "Catálogo" navigation group. The vendor auto-registered ProductOptionResource navigation item (currently in "Configuraciones") MUST be hidden via `Closure::bind()` setting `$shouldRegisterNavigation = false`. The parent "Productos" item MUST remain clickable, navigating to `/panel/products`, and MUST display an expandable chevron revealing "Opciones de Producto" as a child. Clicking the child MUST navigate to `/panel/product-options`.

#### Scenario: ProductOption nested under Product

- GIVEN the admin panel sidebar renders with Catalógo group expanded
- WHEN a user views the "Catálogo" navigation group
- THEN "Productos" displays an expandable chevron indicator
- AND clicking the chevron reveals "Opciones de Producto" as a child item
- AND clicking "Opciones de Producto" navigates to `/panel/product-options`

#### Scenario: Product parent remains clickable

- GIVEN the nested navigation is configured
- WHEN a user clicks the "Productos" item label (not the chevron)
- THEN the browser navigates to `/panel/products`
- AND the product listing page loads and functions normally (list, create, edit, delete)

#### Scenario: No duplicate ProductOption navigation entry

- GIVEN ProductOptionResource's auto-registered item is hidden
- WHEN the sidebar renders
- THEN ProductOptionResource appears ONLY as a child of "Productos" in "Catálogo"
- AND NO standalone "Configuraciones" entry for ProductOptionResource exists

#### Scenario: ProductOptionResource CRUD pages work independently

- GIVEN a user navigates directly to `/panel/product-options`
- WHEN the ProductOptionResource index page loads
- THEN list, create, edit, and delete operations function without errors
- AND the sidebar highlights "Opciones de Producto" as active

#### Scenario: Catalog group retains existing items

- GIVEN the nested ProductOption item is added
- WHEN the "Catálogo" group renders
- THEN Brand, CollectionGroup, Product, and ProductType entries remain in their original positions
- AND "Opciones de Producto" appears as a child of "Productos" only

---

### RQ-003: CustomerGroupResource Nested Under Customer in Sales

CustomerGroupResource SHALL appear as a nested child item under the "Clientes" parent (CustomerResource) within the "Ventas" navigation group. The vendor auto-registered CustomerGroupResource navigation item (currently in "Configuraciones") MUST be hidden via `Closure::bind()` setting `$shouldRegisterNavigation = false`. The parent "Clientes" item MUST remain clickable, navigating to `/panel/customers`, and MUST display an expandable chevron revealing "Grupos de Clientes" as a child. Clicking the child MUST navigate to `/panel/customer-groups`.

#### Scenario: CustomerGroup nested under Customer

- GIVEN the admin panel sidebar renders with Ventas group expanded
- WHEN a user views the "Ventas" navigation group
- THEN "Clientes" displays an expandable chevron indicator
- AND clicking the chevron reveals "Grupos de Clientes" as a child item
- AND clicking "Grupos de Clientes" navigates to `/panel/customer-groups`

#### Scenario: Customer parent remains clickable

- GIVEN the nested navigation is configured
- WHEN a user clicks the "Clientes" item label (not the chevron)
- THEN the browser navigates to `/panel/customers`
- AND the customer listing page loads and functions normally (list, create, edit, delete)

#### Scenario: No duplicate CustomerGroup navigation entry

- GIVEN CustomerGroupResource's auto-registered item is hidden
- WHEN the sidebar renders
- THEN CustomerGroupResource appears ONLY as a child of "Clientes" in "Ventas"
- AND NO standalone "Configuraciones" entry for CustomerGroupResource exists

#### Scenario: CustomerGroupResource CRUD pages work independently

- GIVEN a user navigates directly to `/panel/customer-groups`
- WHEN the CustomerGroupResource index page loads
- THEN list, create, edit, and delete operations function without errors
- AND the sidebar highlights "Grupos de Clientes" as active

#### Scenario: Sales group retains existing items

- GIVEN the nested CustomerGroup item is added
- WHEN the "Ventas" group renders
- THEN Customer, Discount, and Order entries remain in their original positions
- AND "Grupos de Clientes" appears as a child of "Clientes" only

---

### RQ-004: Settings Group Items Reduction

After hiding ProductOptionResource and CustomerGroupResource from their auto-registered "Configuraciones" group, that group MUST still display its remaining items (Activity, AttributeGroup, Channel, Currency, Language, Staff, Tag) without disruption.

#### Scenario: Settings group preserves remaining items

- GIVEN ProductOptionResource and CustomerGroupResource are hidden from navigation
- WHEN the "Configuraciones" group renders
- THEN Activity, AttributeGroup, Channel, Currency, Language, Staff, and Tag items remain visible and functional
- AND NO empty or broken navigation group appears

#### Scenario: Settings group hidden if all items removed by permissions

- GIVEN a user lacks permission for ALL remaining Settings items
- WHEN the sidebar renders
- THEN Filament's default behavior hides the empty "Configuraciones" group
- AND no empty group heading appears