# Website Updates & Shop Refinement Plan

Address issue fixes and enhancements requested for Shreeji Infotech:
1. Fix home page navigation URL redirect showing `shreejiinfo.in/index.html` instead of clean `shreejiinfo.in/`.
2. Remove "Shop Online" button from the main top navigation bar and relocate it to a dedicated section on the homepage so users first see the portfolio.
3. Streamline shop products to a focused niche selection (RAM, SSD, HDD, and key peripherals) and ensure seamless layout consistency between Home and Shop pages.
4. Add company logo favicon across all pages.

## User Review Required

> [!IMPORTANT]
> - **Top Navbar Change**: The top navigation bar on the homepage will focus on portfolio and core IT services (`Home`, `Products`, `Services`, `Enterprise`, `Enquiry`, `Contact`). A prominent new "Hardware & Upgrades Store" section and banner will be added to the homepage body to guide buyers into the shop.
> - **Shop Product Streamlining**: Shop catalog will be updated from general laptops/printers/CCTV to a curated, niche inventory of RAM, SSDs, HDDs, and key peripherals (keyboards, mice, external storage, adapters).

## Open Questions

> [!NOTE]
> None. All requirements are clear and can be executed immediately upon approval.

## Proposed Changes

### Clean URLs & Favicon (`index.html` & All Pages)

#### [MODIFY] [index.html](file:///c:/Users/Admin/Desktop/SI%20WEB/index.html)
- Add logo favicon links `<link rel="icon" type="image/png" href="assets/images/logo.png">` in `<head>`.
- Remove "Shop Online" item from `#navMenu` and `#mobileDrawer`.
- Add a new high-converting "Niche Hardware & Upgrades Store" card/banner section (featuring RAM, SSD, HDD & Peripherals) with a direct link to `store/`.
- Ensure all relative links use clean paths without `index.html`.

#### [MODIFY] [admin.html](file:///c:/Users/Admin/Desktop/SI%20WEB/admin.html) & [404.html](file:///c:/Users/Admin/Desktop/SI%20WEB/404.html)
- Add logo favicon links to `<head>`.

---

### Store Pages & Products (`store/`)

#### [MODIFY] [store/index.html](file:///c:/Users/Admin/Desktop/SI%20WEB/store/index.html)
- Add logo favicon links (`../assets/images/logo.png`) in `<head>`.
- Change logo and navbar links from `../index.html` to `../` (and `./` for shop root) so clicking Home leads cleanly to `shreejiinfo.in/` without appending `/index.html`.
- Match header structure, heights, fonts, container constraints, and mobile drawer styling with `index.html` for 1:1 layout consistency when switching pages.
- Update hero title & banner text to reflect the niche hardware focus (RAM, SSD, HDD & Peripherals).
- Replace category pills with: `All Products`, `RAM`, `SSD`, `HDD`, `Peripherals & Accessories`.
- Replace `DEMO_PRODUCTS` with niche items:
  - **RAM**: Crucial 8GB/16GB DDR4/DDR5 Laptop & Desktop RAM, Corsair Vengeance 16GB.
  - **SSD**: Samsung 980 Pro 1TB NVMe, Kingston NV2 512GB, Crucial BX500 480GB SATA.
  - **HDD**: Seagate Barracuda 1TB/2TB Desktop Hard Drive, WD Elements 1TB External Hard Drive.
  - **Peripherals**: Logitech MX Master 3S, Keychron K2 Mechanical Keyboard, Dell 24" FHD Monitor, TP-Link USB Wi-Fi Adapter.

#### [MODIFY] [store/cart.html](file:///c:/Users/Admin/Desktop/SI%20WEB/store/cart.html)
- Add logo favicon link in `<head>`.
- Update home/store links in navbar and breadcrumb from `../index.html` to `../`.
- Align header styling with `styles.css` for layout consistency.

#### [MODIFY] [store/checkout.html](file:///c:/Users/Admin/Desktop/SI%20WEB/store/checkout.html)
- Add logo favicon link in `<head>`.
- Update navbar home/store links from `../index.html` to `../`.

#### [MODIFY] [store/product.html](file:///c:/Users/Admin/Desktop/SI%20WEB/store/product.html)
- Add logo favicon link in `<head>`.
- Update navbar and breadcrumb home/store links from `../index.html` to `../`.
- Align product fallback data with the niche SKUs.

#### [MODIFY] [store/wishlist.html](file:///c:/Users/Admin/Desktop/SI%20WEB/store/wishlist.html), [store/compare.html](file:///c:/Users/Admin/Desktop/SI%20WEB/store/compare.html), [store/order-success.html](file:///c:/Users/Admin/Desktop/SI%20WEB/store/order-success.html)
- Add logo favicon link in `<head>`.
- Update links pointing to `../index.html` to `../`.

---

### Styles & Layout Consistency

#### [MODIFY] [store/store.css](file:///c:/Users/Admin/Desktop/SI%20WEB/store/store.css)
- Enforce navbar container height, padding, font sizing, and flex parameters to match `styles.css` identically so transitions between main website and store suffer zero layout jump.

## Verification Plan

### Automated / Manual Verification
1. **URL Inspection**: Open home page, click into store, click back to Home, and verify URL is `shreejiinfo.in/` (or relative `./` / `../`) without `/index.html`.
2. **Navbar Inspection**: Verify top navbar in `index.html` has no "Shop Online" button, and verify portfolio/services links are present. Check mobile drawer as well.
3. **Homepage Banner**: Verify new Hardware Store section on `index.html` is visually striking and links smoothly to `store/`.
4. **Shop SKUs**: Open `store/`, verify products display RAM, SSD, HDD, and Peripherals only, and category filters filter correctly.
5. **Layout Shift Test**: Switch back and forth between Home (`index.html`) and Shop (`store/index.html`) in browser view to confirm header/footer positioning and widths match with zero layout shift.
6. **Favicon**: Verify logo favicon appears in tab title bar across all pages.
