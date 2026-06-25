// Predefined service categories
export const CATEGORIES = [
  { id: 'laptop-desktop', label: 'Laptop / Desktop', icon: '💻' },
  { id: 'cctv', label: 'CCTV Installation', icon: '📹' },
  { id: 'amc', label: 'AMC Plans', icon: '🛡️' },
  { id: 'networking', label: 'Networking', icon: '🌐' },
  { id: 'cloud-email', label: 'Cloud / Email Setup', icon: '☁️' },
  { id: 'printer', label: 'Printer / Scanner', icon: '🖨️' },
  { id: 'software', label: 'Software Solutions', icon: '📦' },
  { id: 'other', label: 'Other Services', icon: '⚙️' },
];

// Common units of measurement
export const UNITS = [
  { id: 'nos', label: 'Nos' },
  { id: 'set', label: 'Set' },
  { id: 'pcs', label: 'Pcs' },
  { id: 'lot', label: 'Lot' },
  { id: 'mtr', label: 'Mtr' },
  { id: 'sqft', label: 'Sq.ft' },
  { id: 'box', label: 'Box' },
  { id: 'service', label: 'Service' },
  { id: 'license', label: 'License' },
  { id: 'month', label: 'Month' },
  { id: 'year', label: 'Year' },
];

// Common HSN/SAC codes for IT services
export const HSN_CODES = [
  { code: '8471', description: 'Computers & peripherals' },
  { code: '8528', description: 'CCTV cameras & monitors' },
  { code: '8517', description: 'Networking equipment' },
  { code: '8443', description: 'Printers & scanners' },
  { code: '998314', description: 'IT support & maintenance (SAC)' },
  { code: '998315', description: 'IT infrastructure provisioning (SAC)' },
  { code: '998316', description: 'IT infrastructure management (SAC)' },
  { code: '998319', description: 'Other IT services (SAC)' },
  { code: '4911', description: 'Toner / Cartridge' },
  { code: '8544', description: 'Cables & wiring' },
];

// Predefined templates for quick quotation
export const TEMPLATES = [
  {
    id: 'cctv-basic',
    name: 'CCTV Basic Package',
    description: '4 Camera HD CCTV Setup',
    items: [
      { name: 'HD CCTV Camera (2MP)', description: 'Hikvision 2MP Bullet Camera with Night Vision', category: 'cctv', quantity: 4, price: 1800, unit: 'nos', hsnCode: '8528' },
      { name: '4 Channel DVR', description: 'Hikvision 4Ch DVR with 1TB HDD', category: 'cctv', quantity: 1, price: 4500, unit: 'nos', hsnCode: '8528' },
      { name: 'CCTV Cable & Accessories', description: '90m cable, connectors, adapters, BNC', category: 'cctv', quantity: 1, price: 2500, unit: 'lot', hsnCode: '8544' },
      { name: 'Installation & Setup', description: 'Professional installation with mobile app config', category: 'cctv', quantity: 1, price: 3000, unit: 'service', hsnCode: '998314' },
    ],
  },
  {
    id: 'cctv-premium',
    name: 'CCTV Premium Package',
    description: '8 Camera IP CCTV Setup',
    items: [
      { name: 'IP Camera (4MP)', description: 'Hikvision 4MP IP Dome Camera with Audio', category: 'cctv', quantity: 8, price: 3500, unit: 'nos', hsnCode: '8528' },
      { name: '8 Channel NVR', description: 'Hikvision 8Ch NVR with 2TB HDD', category: 'cctv', quantity: 1, price: 8500, unit: 'nos', hsnCode: '8528' },
      { name: 'Network Switch (8 Port PoE)', description: '8 Port PoE Switch for IP cameras', category: 'cctv', quantity: 1, price: 4500, unit: 'nos', hsnCode: '8517' },
      { name: 'CAT6 Cable & Accessories', description: 'CAT6 cable, RJ45, patch cords', category: 'cctv', quantity: 1, price: 5000, unit: 'lot', hsnCode: '8544' },
      { name: 'Installation & Configuration', description: 'Full installation, NVR setup, remote access config', category: 'cctv', quantity: 1, price: 5000, unit: 'service', hsnCode: '998314' },
    ],
  },
  {
    id: 'amc-basic',
    name: 'AMC Basic Plan',
    description: 'Annual Maintenance for up to 5 systems',
    items: [
      { name: 'Annual Maintenance Contract', description: 'Covers 5 desktops/laptops - Monthly visits, OS support, virus removal', category: 'amc', quantity: 1, price: 15000, unit: 'year', hsnCode: '998314' },
      { name: 'Network Maintenance', description: 'Router, switch, LAN troubleshooting included', category: 'amc', quantity: 1, price: 5000, unit: 'year', hsnCode: '998315' },
    ],
  },
  {
    id: 'amc-premium',
    name: 'AMC Premium Plan',
    description: 'Annual Maintenance for up to 15 systems',
    items: [
      { name: 'Annual Maintenance Contract', description: 'Covers 15 desktops/laptops - Weekly visits, priority support', category: 'amc', quantity: 1, price: 36000, unit: 'year', hsnCode: '998314' },
      { name: 'Server Maintenance', description: 'Server health check, backup verification, updates', category: 'amc', quantity: 1, price: 12000, unit: 'year', hsnCode: '998316' },
      { name: 'Network Infrastructure', description: 'Complete network monitoring and maintenance', category: 'amc', quantity: 1, price: 8000, unit: 'year', hsnCode: '998315' },
    ],
  },
  {
    id: 'networking-office',
    name: 'Office Network Setup',
    description: 'Complete office networking solution',
    items: [
      { name: 'Network Switch (24 Port)', description: 'Managed 24 Port Gigabit Switch', category: 'networking', quantity: 1, price: 8500, unit: 'nos', hsnCode: '8517' },
      { name: 'Wi-Fi Access Point', description: 'Enterprise-grade dual-band access point', category: 'networking', quantity: 2, price: 4500, unit: 'nos', hsnCode: '8517' },
      { name: 'CAT6 Cabling', description: 'Structured cabling with points (per point)', category: 'networking', quantity: 15, price: 650, unit: 'nos', hsnCode: '8544' },
      { name: 'Rack & Patch Panel', description: '6U rack with patch panel and cable management', category: 'networking', quantity: 1, price: 5500, unit: 'set', hsnCode: '8517' },
      { name: 'Installation & Testing', description: 'Complete setup, crimping, testing & labeling', category: 'networking', quantity: 1, price: 5000, unit: 'service', hsnCode: '998314' },
    ],
  },
  {
    id: 'cloud-basic',
    name: 'Cloud & Email Setup',
    description: 'Business email & cloud workspace',
    items: [
      { name: 'Google Workspace Business', description: 'Google Workspace license (per user/year)', category: 'cloud-email', quantity: 5, price: 1800, unit: 'license', hsnCode: '998319' },
      { name: 'Domain Configuration', description: 'DNS setup, MX records, SPF/DKIM config', category: 'cloud-email', quantity: 1, price: 2000, unit: 'service', hsnCode: '998319' },
      { name: 'Data Migration', description: 'Email & data migration from existing setup', category: 'cloud-email', quantity: 1, price: 3000, unit: 'service', hsnCode: '998319' },
    ],
  },
];

// Validity period options
export const VALIDITY_OPTIONS = [
  { value: 7, label: '7 Days' },
  { value: 15, label: '15 Days' },
  { value: 30, label: '30 Days' },
  { value: 45, label: '45 Days' },
  { value: 60, label: '60 Days' },
];

// GST type options
export const GST_TYPES = [
  { id: 'igst', label: 'IGST (Inter-state)' },
  { id: 'cgst_sgst', label: 'CGST + SGST (Intra-state)' },
];

// Default company details (update from Settings page)
export const COMPANY_INFO = {
  name: 'Shreeji Infotech',
  tagline: 'Your Trusted IT Partner',
  address: 'Ahmedabad, Gujarat, India',
  phone: '',
  email: 'info@shreejiinfotech.com',
  website: 'www.shreejiinfotech.com',
  gstin: '',
  footerNote: 'Thank you for your business! We look forward to working with you.',
  bankName: '',
  bankAccountNo: '',
  bankIfsc: '',
  bankBranch: '',
  signatureDataUrl: null,
};

// Sort options for dashboard
export const SORT_OPTIONS = [
  { id: 'newest', label: 'Newest First' },
  { id: 'oldest', label: 'Oldest First' },
  { id: 'value-high', label: 'Value: High → Low' },
  { id: 'value-low', label: 'Value: Low → High' },
  { id: 'name-az', label: 'Client: A → Z' },
  { id: 'name-za', label: 'Client: Z → A' },
];

// Default terms & conditions templates
export const TERMS_TEMPLATES = [
  {
    id: 'standard',
    label: 'Standard',
    text: `Payment Terms: 50% advance, 50% on completion.\nWarranty as per manufacturer terms.\nPrices are subject to change without prior notice.\nDelivery within 5-7 working days after confirmation.`,
  },
  {
    id: 'service',
    label: 'Service / AMC',
    text: `Payment Terms: 100% advance before commencement.\nService period as mentioned in quotation.\nSupport available Mon-Sat, 10 AM - 7 PM.\nTravel charges applicable for sites beyond 25 km.`,
  },
  {
    id: 'project',
    label: 'Project Based',
    text: `Payment Terms: 30% advance, 40% on delivery, 30% on completion.\nProject timeline as per mutual agreement.\nChange requests may incur additional charges.\nWarranty: 1 year on workmanship, manufacturer warranty on products.`,
  },
];

// Generate quotation number
export function generateQuotationNumber() {
  const date = new Date();
  const year = date.getFullYear().toString().slice(-2);
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const random = Math.floor(Math.random() * 9000 + 1000);
  return `SI-${year}${month}-${random}`;
}

// Format date
export function formatDate(date) {
  return new Date(date).toLocaleDateString('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

// Format currency
export function formatCurrency(amount) {
  const formatted = new Intl.NumberFormat('en-IN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount);
  return '₹' + formatted;
}

// Create empty item
export function createEmptyItem() {
  return {
    id: crypto.randomUUID ? crypto.randomUUID() : Date.now().toString() + Math.random(),
    name: '',
    description: '',
    category: 'other',
    quantity: 1,
    price: 0,
    unit: 'nos',
    hsnCode: '',
  };
}

// Create empty quotation
export function createEmptyQuotation() {
  return {
    id: crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(),
    quotationNumber: generateQuotationNumber(),
    date: new Date().toISOString().split('T')[0],
    validityDays: 15,
    client: {
      name: '',
      company: '',
      phone: '',
      email: '',
      address: '',
      gstin: '',
    },
    items: [createEmptyItem()],
    gstEnabled: true,
    gstRate: 18,
    gstType: 'cgst_sgst', // 'igst' or 'cgst_sgst'
    discount: 0,
    discountType: 'amount', // 'amount' or 'percent'
    notes: '',
    termsAndConditions: 'Payment Terms: 50% advance, 50% on completion.\nWarranty as per manufacturer terms.\nPrices are subject to change without prior notice.\nDelivery within 5-7 working days after confirmation.',
    status: 'draft',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  };
}

// Get all unique item names from past quotations (for autocomplete)
export function getItemSuggestions() {
  try {
    const data = localStorage.getItem('shreeji_quotations');
    if (!data) return [];
    const quotations = JSON.parse(data);
    const itemMap = new Map();
    quotations.forEach(q => {
      (q.items || []).forEach(item => {
        if (item.name && !itemMap.has(item.name)) {
          itemMap.set(item.name, {
            name: item.name,
            description: item.description || '',
            category: item.category || 'other',
            price: item.price || 0,
            unit: item.unit || 'nos',
            hsnCode: item.hsnCode || '',
          });
        }
      });
    });
    return Array.from(itemMap.values());
  } catch {
    return [];
  }
}
